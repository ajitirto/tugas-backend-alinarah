<?php

use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\Api\SearchController;
use Illuminate\Support\Facades\Route;

Route::controller(PostController::class)->group(function () {

    Route::prefix('posts')->group(function () {
        Route::get('/', 'index');
        Route::get('/{id}', 'show');

        Route::post('/', 'store')
            ->middleware('throttle:create-post');
    });

    Route::get('/users/{id}/posts', 'userPosts');
});

Route::get('/search', SearchController::class);
Route::get('/ping', function () {
    return response()->json(['ok' => true]);
});

Route::get('/stats/top-tags', [StatsController::class, 'topTags']);
Route::get('/stats/top-authors', [StatsController::class, 'topAuthors']);
