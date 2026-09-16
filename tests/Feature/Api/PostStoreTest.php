<?php

namespace Tests\Feature\Api;

use App\Listeners\SendPostCreatedNotification;
use App\Models\User;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PostStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_can_be_created(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $payload = [
            'title' => 'Test Post',
            'body' => 'This is a test post.',
            'user_id' => $user->id,
            'tags' => ['laravel', 'testing'],
        ];

        $response = $this->postJson('/api/posts', $payload);

        $response
            ->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'tags',
                    'views',
                    'likes',
                    'user_id',
                ],
            ])
            ->assertJsonPath('data.title', 'Test Post')
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.views', 0)
            ->assertJsonPath('data.likes', 0);

        $this->assertDatabaseHas('posts', [
            'title' => 'Test Post',
            'user_id' => $user->id,
        ]);
    }

    public function test_post_creation_requires_title_and_body(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/posts', [
            'user_id' => $user->id,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'title',
                'body',
            ]);
    }

    public function test_post_creation_requires_existing_user(): void
    {
        $response = $this->postJson('/api/posts', [
            'title' => 'Test Post',
            'body' => 'Test body',
            'user_id' => 999999,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'user_id',
            ]);
    }

    public function test_post_creation_dispatches_queued_notification(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->postJson('/api/posts', [
            'title' => 'Notified Post',
            'body' => 'Body of notified post.',
            'user_id' => $user->id,
        ])->assertCreated();

        Queue::assertPushed(
            CallQueuedListener::class,
            fn ($job) => $job->class === SendPostCreatedNotification::class
        );
    }

    public function test_post_creation_is_rate_limited(): void
    {
        Queue::fake();

        // Use a dedicated client IP so the limiter quota is fresh and the
        // test stays deterministic when other tests share the same process
        // and a persistent cache (e.g. Redis in CI).
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.55']);

        $user = User::factory()->create();

        for ($i = 1; $i <= 10; $i++) {
            $this->postJson('/api/posts', [
                'title' => "Post {$i}",
                'body' => 'Body',
                'user_id' => $user->id,
            ])->assertCreated();
        }

        $this->postJson('/api/posts', [
            'title' => 'Blocked Post',
            'body' => 'Body',
            'user_id' => $user->id,
        ])->assertStatus(429);
    }
}
