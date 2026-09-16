<?php

namespace Tests\Feature\Api;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class StatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_top_tags_returns_data_with_miss_then_hit(): void
    {
        $user = User::factory()->create();

        Post::withoutSyncingToSearch(function () use ($user) {
            Post::factory()->create([
                'user_id' => $user->id,
                'tags' => ['php', 'laravel'],
            ]);

            Post::factory()->create([
                'user_id' => $user->id,
                'tags' => ['php'],
            ]);

            Post::factory()->create([
                'user_id' => $user->id,
                'tags' => ['python'],
            ]);
        });

        $first = $this->getJson('/api/stats/top-tags');

        $first
            ->assertOk()
            ->assertHeader('X-Cache', 'MISS')
            ->assertJsonCount(3)
            ->assertJsonPath('0.tag', 'php')
            ->assertJsonPath('0.count', 2);

        $second = $this->getJson('/api/stats/top-tags');

        $second
            ->assertOk()
            ->assertHeader('X-Cache', 'HIT')
            ->assertJsonPath('0.tag', 'php')
            ->assertJsonPath('0.count', 2);
    }

    public function test_top_authors_returns_data_with_total_views(): void
    {
        $authorA = User::factory()->create(['username' => 'author_a']);
        $authorB = User::factory()->create(['username' => 'author_b']);

        Post::withoutSyncingToSearch(function () use ($authorA, $authorB) {
            Post::factory()->create([
                'user_id' => $authorA->id,
                'views' => 300,
            ]);

            Post::factory()->create([
                'user_id' => $authorB->id,
                'views' => 100,
            ]);
        });

        $response = $this->getJson('/api/stats/top-authors');

        $response
            ->assertOk()
            ->assertHeader('X-Cache', 'MISS')
            ->assertJsonCount(2)
            ->assertJsonStructure([
                '*' => [
                    'user_id',
                    'username',
                    'total_views',
                ],
            ])
            ->assertJsonPath('0.user_id', $authorA->id)
            ->assertJsonPath('0.username', 'author_a')
            ->assertJsonPath('0.total_views', 300)
            ->assertJsonPath('1.user_id', $authorB->id)
            ->assertJsonPath('1.total_views', 100);
    }

    public function test_stats_cache_is_invalidated_after_post_creation(): void
    {
        Queue::fake();

        // This test is about cache invalidation, not throttling. Disable the
        // create-post rate limiter so it cannot be tripped by earlier tests
        // sharing the same process/cache in CI.
        $this->withoutMiddleware(ThrottleRequests::class);

        Cache::put('stats:heron:top-tags', [
            ['tag' => 'stale', 'count' => 99],
        ]);
        Cache::put('stats:heron:top-authors', [
            ['user_id' => 1, 'username' => 'stale', 'total_views' => 99],
        ]);

        $this->getJson('/api/stats/top-tags')
            ->assertOk()
            ->assertHeader('X-Cache', 'HIT')
            ->assertJsonPath('0.tag', 'stale');

        $user = User::factory()->create();

        $this->postJson('/api/posts', [
            'title' => 'Cache Buster',
            'body' => 'This post creation should invalidate the stats cache.',
            'user_id' => $user->id,
            'tags' => ['fresh'],
        ])->assertCreated();

        $this->getJson('/api/stats/top-tags')
            ->assertOk()
            ->assertHeader('X-Cache', 'MISS')
            ->assertJsonPath('0.tag', 'fresh');
    }
}
