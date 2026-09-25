<?php

use App\Http\Controllers\Youtubecontroller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// YouTube API Routes
Route::get('/youtube/search', [Youtubecontroller::class, 'search']);
Route::get('/youtube/live', [Youtubecontroller::class, 'live']);
Route::get('/youtube/video', [Youtubecontroller::class, 'details']);

// MangaDex Manhwa Proxy Route
Route::get('/manhwa', function () {
    return Cache::remember('mangadex_popular_manhwa', 600, function () {
        $response = Http::withHeaders([
            'User-Agent' => 'LaravelVueManhwaApp/1.0 (https://render.com)',
        ])->get('https://api.mangadex.org/manga', [
            'limit' => 12,
            'originalLanguage' => ['ko'],
            'order' => ['followedCount' => 'desc'],
            'contentRating' => ['safe', 'suggestive'],
            'includes' => ['cover_art'],
        ]);

        if ($response->failed()) {
            return response()->json(['error' => 'Upstream API failure'], 502);
        }

        return $response->json();
    });
});

// MangaDex Cover Image Proxy Route
Route::get('/manhwa/cover/{mangaId}/{fileName}', function ($mangaId, $fileName) {
    // basic sanity check so this can't be abused as an open proxy
    if (!preg_match('/^[a-zA-Z0-9\-\.]+$/', $mangaId) || !preg_match('/^[a-zA-Z0-9\-\.]+$/', $fileName)) {
        abort(400, 'Invalid parameters');
    }

    $cacheKey = "manhwa_cover_{$mangaId}_{$fileName}";

    $imageData = Cache::remember($cacheKey, 86400, function () use ($mangaId, $fileName) {
        $response = Http::withHeaders([
            'User-Agent' => 'LaravelVueManhwaApp/1.0 (https://render.com)',
        ])->get("https://uploads.mangadex.org/covers/{$mangaId}/{$fileName}.256.jpg");

        if ($response->failed()) {
            return null;
        }

        return [
            'body' => base64_encode($response->body()),
            'content_type' => $response->header('Content-Type') ?: 'image/jpeg',
        ];
    });

    if (!$imageData) {
        abort(404, 'Cover not found');
    }

    return response(base64_decode($imageData['body']))
        ->header('Content-Type', $imageData['content_type'])
        ->header('Cache-Control', 'public, max-age=86400');
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});