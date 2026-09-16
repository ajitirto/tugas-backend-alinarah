<?php

namespace App\Services;

use App\Events\PostCreated;
use App\Models\Post;
use App\Repositories\PostRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class PostService
{
    private const TOP_TAGS_KEY = 'stats:heron:top-tags';

    private const TOP_AUTHORS_KEY = 'stats:heron:top-authors';

    public function __construct(
        private PostRepository $postRepository
    ) {}

    /**
     * @return LengthAwarePaginator<int, Post>
     */
    public function getPosts(
        int $page,
        int $perPage,
        ?string $tag = null,
        ?string $sort = null
    ): LengthAwarePaginator {
        return $this->postRepository->getPosts(
            $page,
            $perPage,
            $tag,
            $sort
        );
    }

    public function getPost(int $id): ?Post
    {
        return $this->postRepository->findById($id);
    }

    /**
     * @return LengthAwarePaginator<int, Post>
     */
    public function getUserPosts(
        int $userId,
        int $page,
        int $perPage
    ): LengthAwarePaginator {
        return $this->postRepository->getPostsByUser(
            $userId,
            $page,
            $perPage
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createPost(array $data): Post
    {
        $post = $this->postRepository->create([
            'user_id' => $data['user_id'],
            'title' => $data['title'],
            'body' => $data['body'],
            'tags' => $data['tags'] ?? [],
            'views' => 0,
            'likes' => 0,
        ]);

        event(new PostCreated($post));

        Cache::forget(self::TOP_TAGS_KEY);
        Cache::forget(self::TOP_AUTHORS_KEY);

        return $post;
    }

    public function like(Post $post): int
    {
        return $this->postRepository->incrementLikes($post);
    }
}
