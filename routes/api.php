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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});