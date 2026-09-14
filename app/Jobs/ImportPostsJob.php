<?php

namespace App\Jobs;

use App\Models\Post;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ImportPostsJob implements ShouldQueue
{
    use Batchable;
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public array $posts)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $now = now();

        $data = collect($this->posts)
            ->map(fn ($post) => [
                'id' => $post['id'],
                'user_id' => $post['userId'],
                'title' => $post['title'],
                'body' => $post['body'],
                'views' => $post['views'],
                'likes' => $post['reactions']['likes'] ?? 0,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        Post::upsert(
            $data,
            ['id'],
            [
                'user_id',
                'title',
                'body',
                'views',
                'likes',
                'updated_at',
            ]
        );
    }
}
