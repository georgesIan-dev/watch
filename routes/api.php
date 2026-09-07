<?php
use App\Http\Controllers\YoutubeController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
Route::get('/youtube/search', [YoutubeController::class, 'search']);
Route::get('/youtube/live', [YoutubeController::class, 'live']);


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
