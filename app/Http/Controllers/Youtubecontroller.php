<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class Youtubecontroller extends Controller
{
    private const SEARCH_URL = 'https://www.googleapis.com/youtube/v3/search';
    private const VIDEOS_URL = 'https://www.googleapis.com/youtube/v3/videos';

    private function getApiKeys(): array
    {
        $keys = config('services.youtube.keys', []);
        return array_values(array_filter(array_map('trim', $keys)));
    }

    private function youtubeRequest(string $url, array $params): array
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

            $quotaErrors = [
                'quotaExceeded',
                'dailyLimitExceeded',
                'userRateLimitExceeded',
                'rateLimitExceeded',
            ];

            if (in_array($reason, $quotaErrors) || $response->status() === 403) {
                continue;
            }

            break;
        }

        return [
            'ok' => false,
            'status' => $lastResponse ? $lastResponse->status() : 500,
            'error' => $lastResponse ? ($lastResponse->json()['error']['message'] ?? 'Unknown Error') : 'No keys available',
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

        $result = $this->youtubeRequest(self::SEARCH_URL, [
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

        if (!str_contains(strtolower($query), 'live')) {
            return response()->json([
                'items' => [],
                'kind' => 'youtube#searchListResponse',
                'skipped' => true,
                'reason' => 'No "live" keyword in query, API call skipped to save quota.',
            ]);
        }

        $result = $this->youtubeRequest(self::SEARCH_URL, [
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

    public function details(Request $request): JsonResponse
    {
        $request->validate(['id' => 'required|string']);

        $result = $this->youtubeRequest(self::VIDEOS_URL, [
            'id' => $request->id,
            'part' => 'snippet,statistics,contentDetails',
        ]);

        if (!$result['ok']) {
            return response()->json(['message' => $result['error']], $result['status']);
        }
        return response()->json($result['data']);
    }

    public function durations(Request $request): JsonResponse
    {
        $request->validate(['ids' => 'required|string']);

        $result = $this->youtubeRequest(self::VIDEOS_URL, [
            'id' => $request->ids,
            'part' => 'contentDetails',
        ]);

        if (!$result['ok']) {
            return response()->json(['message' => $result['error']], $result['status']);
        }

        $durations = [];
        foreach ($result['data']['items'] ?? [] as $item) {
            $durations[$item['id']] = $item['contentDetails']['duration'];
        }

        return response()->json($durations);
    }
}