<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class YoutubeController extends Controller
{
    private const SEARCH_URL = 'https://www.googleapis.com/youtube/v3/search';

    /**
     * Get the list of configured YouTube API keys (trimmed, non-empty).
     */
    private function getApiKeys(): array
    {
        $keys = config('services.youtube.keys', []);
        return array_values(array_filter(array_map('trim', $keys)));
    }

    /**
     * Perform a GET request to the YouTube Data API, rotating through
     * the configured API keys whenever the current one hits its quota.
     */
    private function youtubeRequest(array $params): array
    {
        $keys = $this->getApiKeys();

        if (empty($keys)) {
            return [
                'ok' => false,
                'status' => 500,
                'error' => 'No YouTube API keys configured. Set YOUTUBE_API_KEY in your .env file.',
            ];
        }

        $lastError = null;
        $lastStatus = 500;

        foreach ($keys as $key) {
            $response = Http::timeout(30) // Bigyan natin ng 30 seconds para sumagot
                ->connectTimeout(15)      // 15 seconds para makakonekta
                ->get(self::SEARCH_URL, array_merge($params, [
                    'key' => $key,
                ]));

            if ($response->successful()) {
                return [
                    'ok' => true,
                    'data' => $response->json(),
                ];
            }

            $body = $response->json();
            $reason = $body['error']['errors'][0]['reason'] ?? null;
            $isQuotaError = $response->status() === 403 && in_array($reason, [
                'quotaExceeded',
                'dailyLimitExceeded',
                'userRateLimitExceeded',
            ], true);

            $lastError = $body['error']['message'] ?? 'Failed to fetch data from YouTube';
            $lastStatus = $response->status();

            if ($isQuotaError) {
                // Try the next key.
                continue;
            }

            // Non-quota error: stop immediately, no point rotating keys.
            break;
        }

        return [
            'ok' => false,
            'status' => $lastStatus,
            'error' => $lastError ?? 'All YouTube API keys have exceeded their quota.',
        ];
    }

    /**
     * GET /api/youtube/search
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'required|string',
            'order' => 'nullable|string|in:relevance,date,rating,title,videoCount,viewCount',
            'pageToken' => 'nullable|string',
            'maxResults' => 'nullable|integer|min:1|max:50',
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

    /**
     * GET /api/youtube/live
     */
    public function live(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'nullable|string',
            'maxResults' => 'nullable|integer|min:1|max:50',
        ]);

        $result = $this->youtubeRequest([
            'part' => 'snippet',
            'type' => 'video',
            'eventType' => 'live',
            'q' => $validated['q'] ?? 'live',
            'order' => 'viewCount',
            'maxResults' => $validated['maxResults'] ?? 12,
        ]);

        if (!$result['ok']) {
            return response()->json(['message' => $result['error']], $result['status']);
        }

        return response()->json($result['data']);
    }
}