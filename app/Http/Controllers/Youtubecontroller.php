<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class Youtubecontroller extends Controller
{
    private const SEARCH_URL = 'https://www.googleapis.com/youtube/v3/search';

    private function getApiKeys(): array
    {
        $keys = config('services.youtube.keys', []);
        return array_values(array_filter(array_map('trim', $keys)));
    }

    private function youtubeRequest(array $params): array
    {
        $keys = $this->getApiKeys();
        $lastResponse = null;

        foreach ($keys as $key) {
            $response = Http::withOptions(['verify' => false, 'force_ip_resolve' => 'v4'])
                ->timeout(30)
                ->get(self::SEARCH_URL, array_merge($params, ['key' => $key]));

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
        $result = $this->youtubeRequest([
            'part' => 'snippet',
            'type' => 'video',
            'eventType' => 'live',
            'q' => $request->q ?? 'live',
            'maxResults' => 12,
        ]);

        if (!$result['ok']) {
            return response()->json(['message' => $result['error']], $result['status']);
        }
        return response()->json($result['data']);
    }
}