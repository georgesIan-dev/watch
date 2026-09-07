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
        $lastError = 'No API keys available.';
        $lastStatus = 500;

        foreach ($keys as $index => $key) {
            $response = Http::withOptions([
                'force_ip_resolve' => 'v4',
                'verify' => false,
            ])
            ->timeout(30)
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
            
            $lastError = $body['error']['message'] ?? 'Unknown Error';
            $lastStatus = $response->status();

            // Kung quota error, huwag munang mag-return. Ituloy ang loop sa susunod na key.
            if ($lastStatus === 403 && in_array($reason, ['quotaExceeded', 'dailyLimitExceeded', 'userRateLimitExceeded'])) {
                continue; 
            }

            // Kung hindi quota error (halimbawa: invalid key), i-return na agad para malaman mo.
            return [
                'ok' => false,
                'status' => $lastStatus,
                'error' => $lastError,
            ];
        }

        // Kapag nakarating dito, ibig sabihin LAHAT ng keys ay sinubukan at LAHAT sila ay Quota Exceeded.
        return [
            'ok' => false,
            'status' => $lastStatus,
            'error' => "All API keys exhausted: " . $lastError,
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