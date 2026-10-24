<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class Youtubecontroller extends Controller
{
    private const SEARCH_URL = 'https://www.googleapis.com/youtube/v3/search';
    private const VIDEOS_URL = 'https://www.googleapis.com/youtube/v3/videos';

    // Related lists are cached per video so replaying a video costs no quota
    private const RELATED_CACHE_SECONDS = 6 * 60 * 60;

    // Words that say nothing about what a video is about ("Official Music Video", "4K", ...)
    private const TITLE_NOISE_WORDS = [
        'official', 'video', 'music', 'mv', 'hd', 'hq', '4k', '1080p', 'audio',
        'visualizer', 'version', 'full', 'new', 'ft', 'feat', 'featuring',
    ];

    private function getApiKeys(): array
    {
        $keys = config('services.youtube.keys', []);
        return array_values(array_filter(array_map('trim', $keys)));
    }

    //durations of videos

    public function durations(Request $request)
    {
        $request->validate(['ids' => 'required|string']);

        $keys = config('services.youtube.keys', []);

        foreach ($keys as $key) {
            $response = Http::get('https://www.googleapis.com/youtube/v3/videos', [
                'key' => $key,
                'id' => $request->ids,
                // "status" costs no extra quota and tells us if the owner allows embedding
                'part' => 'contentDetails,status',
            ]);

            if ($response->successful()) {
                $durations = [];
                $notEmbeddable = [];
                foreach ($response->json('items', []) as $item) {
                    $durations[$item['id']] = $item['contentDetails']['duration'];

                    // false = "Playback on other websites has been disabled by the video owner"
                    if (($item['status']['embeddable'] ?? true) === false) {
                        $notEmbeddable[] = $item['id'];
                    }
                }
                return response()->json([
                    'durations' => $durations,
                    'notEmbeddable' => $notEmbeddable,
                ]);
            }

            if ($response->status() === 403) {
                continue;
            }

            return response()->json(['message' => 'Failed to fetch durations'], $response->status());
        }

        return response()->json(['message' => 'Quota exceeded on all keys'], 429);
    }

    private function youtubeRequest(array $params, string $url = self::SEARCH_URL): array
    {
        $keys = $this->getApiKeys();
        $lastResponse = null;

        foreach ($keys as $key) {
            $response = Http::withOptions(['verify' => false, 'force_ip_resolve' => 'v4'])
                ->timeout(30)
                ->get($url, array_merge($params, ['key' => $key]));

            if ($response->successful()) {
                return ['ok' => true, 'data' => $response->json()];
            }

            $lastResponse = $response;
            $body = $response->json();
            $reason = $body['error']['errors'][0]['reason'] ?? '';

            // Listahan ng mga error na dapat mag-trigger ng lipat-key
            $quotaErrors = [
                'quotaExceeded',
                'dailyLimitExceeded',
                'userRateLimitExceeded',
                'rateLimitExceeded'
            ];

            if (in_array($reason, $quotaErrors)) {
                continue; // Lipat sa susunod na key, huwag munang mag-error
            }

            // Kung hindi quota error (e.g., Invalid Key), stop na agad ang loop
            break;
        }

        return [
            'ok' => false,
            'status' => $lastResponse ? $lastResponse->status() : 500,
            'error' => $lastResponse ? ($lastResponse->json()['error']['message'] ?? 'Unknown Error') : 'No keys available'
        ];
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'required|string',
            'order' => 'nullable|string',
            'pageToken' => 'nullable|string',
            'maxResults' => 'nullable|integer',
        ]);

        $result = $this->youtubeRequest([
            'part' => 'snippet',
            'type' => 'video',
            'q' => $validated['q'],
            'order' => $validated['order'] ?? 'relevance',
            'maxResults' => $validated['maxResults'] ?? 10,
            'pageToken' => $validated['pageToken'] ?? null,
        ]);

        if (!$result['ok']) {
            return response()->json(['message' => $result['error']], $result['status']);
        }
        return response()->json($result['data']);
    }

    public function live(Request $request): JsonResponse
    {
        $query = trim((string) ($request->q ?? ''));

        // Huwag munang tumawag sa YouTube API kung walang salitang "live"
        // sa search query, para hindi mabilis maubos ang quota.
        if (!str_contains(strtolower($query), 'live')) {
            return response()->json([
                'items' => [],
                'kind' => 'youtube#searchListResponse',
                'skipped' => true,
                'reason' => 'No "live" keyword in query, API call skipped to save quota.',
            ]);
        }

        $result = $this->youtubeRequest([
            'part' => 'snippet',
            'type' => 'video',
            'eventType' => 'live',
            'q' => $query ?: 'live',
            'maxResults' => 12,
        ]);

        if (!$result['ok']) {
            return response()->json(['message' => $result['error']], $result['status']);
        }
        return response()->json($result['data']);
    }

    /**
     * Videos related to the one that is playing.
     *
     * YouTube removed the "relatedToVideoId" search filter in 2023, so we build a
     * search from the playing video instead: its cleaned-up title, limited to the
     * same category and language. Costs 101 quota units (1 videos + 100 search),
     * then it's cached per video.
     */
    public function related(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{6,20}$/'],
        ]);
        $videoId = $validated['id'];
        $cacheKey = "youtube_related_{$videoId}";

        if ($cached = Cache::get($cacheKey)) {
            return response()->json($cached);
        }

        $video = $this->youtubeRequest([
            'part' => 'snippet',
            'id' => $videoId,
        ], self::VIDEOS_URL);

        if (!$video['ok']) {
            return response()->json(['message' => $video['error']], $video['status']);
        }

        $snippet = $video['data']['items'][0]['snippet'] ?? null;
        if (!$snippet) {
            return response()->json(['message' => 'Video not found'], 404);
        }

        $query = $this->relatedQuery($snippet);
        $language = substr($snippet['defaultAudioLanguage'] ?? $snippet['defaultLanguage'] ?? '', 0, 2);

        $result = $this->youtubeRequest(array_filter([
            'part' => 'snippet',
            'type' => 'video',
            'q' => $query,
            'maxResults' => 16,
            'videoCategoryId' => $snippet['categoryId'] ?? null,
            'relevanceLanguage' => $language ?: null,
        ]));

        if (!$result['ok']) {
            return response()->json(['message' => $result['error']], $result['status']);
        }

        // The search usually returns the playing video itself; drop it
        $items = array_values(array_filter(
            $result['data']['items'] ?? [],
            fn ($item) => ($item['id']['videoId'] ?? null) !== $videoId
        ));

        $payload = ['query' => $query, 'items' => $items];
        Cache::put($cacheKey, $payload, self::RELATED_CACHE_SECONDS);

        return response()->json($payload);
    }

    /**
     * "Zara Larsson - Midnight Sun (Official Music Video) | #NewMusic"
     *   -> "Zara Larsson Midnight Sun"
     */
    private function relatedQuery(array $snippet): string
    {
        $title = html_entity_decode($snippet['title'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Text after " | " is usually a channel or series name, not the topic...
        $words = $this->titleWords(preg_split('/\s+[|•]\s+/u', $title)[0]);
        // ...unless the first part is just the artist ("BINI | Pantropiko ...")
        if (count($words) < 2) {
            $words = $this->titleWords($title);
        }

        // Long queries match too few videos
        $query = implode(' ', array_slice($words, 0, 8));

        if ($query === '') {
            $query = $snippet['tags'][0] ?? $snippet['channelTitle'] ?? '';
        }

        return $query;
    }

    private function titleWords(string $text): array
    {
        $text = preg_replace('/#\S+/u', ' ', $text);                 // hashtags
        $text = preg_replace('/[^\p{L}\p{N}\s\'&]+/u', ' ', $text); // brackets, dashes, |, emoji

        $words = array_map(fn ($word) => trim($word, "'"), preg_split('/\s+/u', trim($text)));

        return array_values(array_filter(
            $words,
            fn ($word) => $word !== '' && !in_array(mb_strtolower($word), self::TITLE_NOISE_WORDS, true)
        ));
    }

    public function details(Request $request)
{
    $request->validate(['id' => 'required|string']);

    $keys = config('services.youtube.keys', []);

    foreach ($keys as $key) {
        $response = Http::get('https://www.googleapis.com/youtube/v3/videos', [
            'key' => $key,
            'id' => $request->id,
            'part' => 'snippet,statistics,contentDetails',
        ]);

        if ($response->successful()) {
            return response()->json($response->json());
        }

        // Try next key kung quota-related error
        if ($response->status() === 403) {
            continue;
        }

        return response()->json(['message' => 'Failed to fetch video details'], $response->status());
    }

    return response()->json(['message' => 'Quota exceeded on all keys'], 429);
}
}