<?php

namespace Tests\Feature\Api;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_posts_can_be_listed(): void
    {
        $user = User::factory()->create();

        Post::withoutSyncingToSearch(
            fn () => Post::factory()->count(3)->create([
                'user_id' => $user->id,
            ])
        );

        $response = $this->getJson('/api/posts');

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
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.page', 1)
            ->assertJsonPath('meta.per_page', 10);
    }

    public function test_posts_support_pagination(): void
    {
        $user = User::factory()->create();

        Post::withoutSyncingToSearch(
            fn () => Post::factory()->count(15)->create([
                'user_id' => $user->id,
            ])
        );

        $response = $this->getJson('/api/posts?page=2&per_page=5');

        $response
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.page', 2)
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonPath('meta.total', 15);
    }

    public function test_per_page_is_capped_at_100(): void
    {
        $response = $this->getJson('/api/posts?per_page=1000');

        $response
            ->assertOk()
            ->assertJsonPath('meta.per_page', 100);
    }

    public function test_posts_can_be_filtered_by_tag(): void
    {
        $user = User::factory()->create();

        Post::withoutSyncingToSearch(function () use ($user) {
            Post::factory()->create([
                'user_id' => $user->id,
                'title' => 'Laravel Post',
                'tags' => ['php', 'laravel'],
            ]);

            Post::factory()->create([
                'user_id' => $user->id,
                'title' => 'Vue Post',
                'tags' => ['php', 'vue'],
            ]);

            Post::factory()->create([
                'user_id' => $user->id,
                'title' => 'JavaScript Post',
                'tags' => ['javascript'],
            ]);
        });

        $response = $this->getJson('/api/posts?tag=php');

        $response
            ->assertOk()
            ->assertJsonPath('meta.total', 2);

        foreach ($response->json('data') as $post) {
            $this->assertContains('php', $post['tags']);
        }
    }

    public function test_posts_can_be_sorted_by_views_descending(): void
    {
        $user = User::factory()->create();

        Post::withoutSyncingToSearch(function () use ($user) {
            Post::factory()->create([
                'user_id' => $user->id,
                'title' => 'Lowest Views',
                'views' => 10,
            ]);

            Post::factory()->create([
                'user_id' => $user->id,
                'title' => 'Highest Views',
                'views' => 300,
            ]);

            Post::factory()->create([
                'user_id' => $user->id,
                'title' => 'Middle Views',
                'views' => 150,
            ]);
        });

        $response = $this->getJson('/api/posts?sort=-views');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Highest Views')
            ->assertJsonPath('data.1.title', 'Middle Views')
            ->assertJsonPath('data.2.title', 'Lowest Views');
    }
}
