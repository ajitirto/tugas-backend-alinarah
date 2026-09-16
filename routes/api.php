<?php

use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\StatsController;
use Illuminate\Support\Facades\Route;

Route::controller(PostController::class)->group(function () {

    Route::prefix('posts')->group(function () {
        Route::get('/', 'index');
        Route::get('/{id}', 'show');

        Route::post('/', 'store')->middleware('throttle:create-post');
        Route::post('/{post}/like', 'like');
    });

    Route::get('/users/{id}/posts', 'userPosts');
});

Route::get('/search', SearchController::class);
Route::get('/ping', function () {
    return response()->json(['ok' => true]);
});

Route::controller(StatsController::class)->group(function () {
    Route::prefix('stats')->group(function () {
        Route::get('/top-tags', 'topTags');
        Route::get('/top-authors', 'topAuthors');
    });
});
