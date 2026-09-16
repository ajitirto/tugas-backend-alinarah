<?php

namespace Tests\Feature\Api;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_search_returns_empty_data(): void
    {
        $response = $this->getJson('/api/search?q=');

        $response
            ->assertOk()
            ->assertJson([
                'data' => [],
            ]);
    }

    public function test_search_without_query_returns_empty_data(): void
    {
        $response = $this->getJson('/api/search');

        $response
            ->assertOk()
            ->assertJson([
                'data' => [],
            ]);
    }

    public function test_search_returns_matching_posts_from_meilisearch(): void
    {
        $user = User::factory()->create();

        // Lowercase single-word token; unique per run so results are
        // deterministic even though the Meilisearch index is shared.
        $token = Str::lower(Str::random(10, 'abcdefghijklmnopqrstuvwxyz'));

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'title' => 'Searchable post '.$token,
            'body' => 'A document that must be discoverable through search.',
        ]);

        // Scout model events only register on the first app per process,
        // so force-index explicitly to keep the test deterministic.
        $post->searchable();

        // Meilisearch applies index updates asynchronously, so poll until
        // the document is actually searchable (bounded) before asserting.
        $indexed = false;
        $deadline = microtime(true) + 10.0;
        do {
            $indexed = Post::search($token)->take(5)->get()->contains('id', $post->id);
            if (! $indexed) {
                usleep(250_000);
            }
        } while (! $indexed && microtime(true) < $deadline);

        $this->assertTrue($indexed, 'Post was not indexed by Meilisearch within 10 seconds.');

        $response = $this->getJson('/api/search?q='.$token);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'body',
                    ],
                ],
            ])
            ->assertJsonPath('data.0.id', $post->id)
            ->assertJsonPath('data.0.title', 'Searchable post '.$token);
    }
}
