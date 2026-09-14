<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class DummyJsonService
{
    public function getPosts(): array
    {
        return Http::get(
            'https://dummyjson.com/posts',
            ['limit' => 0]
        )->throw()->json('posts');
    }

    public function getUsers(): array
    {
        return Http::get(
            'https://dummyjson.com/users',
            ['limit' => 0]
        )->throw()->json('users');
    }

    public function getComments(): array
    {
        return Http::get(
            'https://dummyjson.com/comments',
            ['limit' => 0]
        )->throw()->json('comments');
    }
}
