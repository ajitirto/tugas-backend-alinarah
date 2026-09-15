<?php

namespace App\Repositories;

use App\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PostRepository
{
    public function getPosts(
        int $page,
        int $perPage,
        ?string $tag = null,
        ?string $sort = null
    ): LengthAwarePaginator {
        $query = Post::query();

        if ($tag) {
            $query->whereJsonContains('tags', $tag);
        }

        if ($sort) {
            $direction = str_starts_with($sort, '-')
                ? 'desc'
                : 'asc';

            $column = ltrim($sort, '-');

            $allowedSorts = [
                'views',
                'likes',
                'title',
                'created_at',
            ];

            if (in_array($column, $allowedSorts, true)) {
                $query->orderBy($column, $direction);
            }
        }

        return $query
            ->with('user')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function findById(int $id): ?Post
    {
        return Post::query()
            ->with([
                'user',
                'comments.user',
            ])
            ->find($id);
    }

    public function getPostsByUser(
        int $userId,
        int $page,
        int $perPage
    ): LengthAwarePaginator {
        return Post::query()
            ->where('user_id', $userId)
            ->with('user')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function create(array $data): Post
    {
        return Post::create($data);
    }
}
