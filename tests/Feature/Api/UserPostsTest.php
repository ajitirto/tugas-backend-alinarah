<?php

namespace Tests\Feature\Api;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPostsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_posts_can_be_retrieved(): void
    {
        $user = User::factory()->create();

        Post::withoutSyncingToSearch(function () use ($user) {
            Post::factory()->count(2)->create([
                'user_id' => $user->id,
            ]);

            Post::factory()->create();
        });

        $response = $this->getJson("/api/users/{$user->id}/posts");

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'tags',
                        'views',
                        'likes',
                        'user_id',
                    ],
                ],
                'meta' => [
                    'page',
                    'per_page',
                    'total',
                ],
            ])
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2);

        foreach ($response->json('data') as $post) {
            $this->assertSame($user->id, $post['user_id']);
        }
    }

    public function test_posts_for_missing_user_return_404(): void
    {
        $response = $this->getJson('/api/users/999999/posts');

        $response
            ->assertNotFound()
            ->assertJson([
                'message' => 'User not found.',
            ]);
    }
}
