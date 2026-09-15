<?php

use App\Http\Controllers\Api\PostController;
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
