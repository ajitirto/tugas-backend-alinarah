<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class DummyJsonService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function getPosts(): array
    {
        return Http::get(
            'https://dummyjson.com/posts',
            ['limit' => 0]
        )->throw()->json('posts');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getUsers(): array
    {
        return Http::get(
            'https://dummyjson.com/users',
            ['limit' => 0]
        )->throw()->json('users');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getComments(): array
    {
        return Http::get(
            'https://dummyjson.com/comments',
            ['limit' => 0]
        )->throw()->json('comments');
    }
}
