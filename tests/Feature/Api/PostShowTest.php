<?php

namespace Tests\Feature\Api;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_detail_can_be_retrieved(): void
    {
        $user = User::factory()->create();

        $post = Post::withoutSyncingToSearch(
            fn () => Post::factory()->create([
                'user_id' => $user->id,
            ])
        );

        $response = $this->getJson("/api/posts/{$post->id}");

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'body',
                    'tags',
                    'views',
                    'likes',
                    'user',
                    'comments',
                ],
            ])
            ->assertJsonPath('data.id', $post->id)
            ->assertJsonPath('data.user.id', $user->id);
    }

    public function test_post_detail_includes_comments(): void
    {
        $user = User::factory()->create();

        $post = Post::withoutSyncingToSearch(
            fn () => Post::factory()->create([
                'user_id' => $user->id,
            ])
        );

        Comment::create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'body' => 'Nice post!',
            'likes' => 0,
        ]);

        $response = $this->getJson("/api/posts/{$post->id}");

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data.comments')
            ->assertJsonPath('data.comments.0.body', 'Nice post!')
            ->assertJsonPath('data.comments.0.user.id', $user->id)
            ->assertJsonPath('data.comments.0.user.username', $user->username);
    }

    public function test_missing_post_returns_404(): void
    {
        $response = $this->getJson('/api/posts/999999');

        $response
            ->assertNotFound()
            ->assertJson([
                'message' => 'Post not found.',
            ]);
    }
}
