<?php

namespace Tests\Feature\Api;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostLikeTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_can_be_liked(): void
    {
        $user = User::factory()->create();

        $post = Post::withoutSyncingToSearch(
            fn () => Post::factory()->create([
                'user_id' => $user->id,
                'likes' => 0,
            ])
        );

        $response = $this->postJson("/api/posts/{$post->id}/like");

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Post liked successfully.',
                'likes' => 1,
            ]);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'likes' => 1,
        ]);
    }

    public function test_liking_a_missing_post_returns_404(): void
    {
        $response = $this->postJson('/api/posts/999999/like');

        $response->assertNotFound();
    }
}
