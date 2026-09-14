<?php

namespace App\Jobs;

use App\Models\Comment;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ImportCommentsJob implements ShouldQueue
{
    use Batchable;
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public array $comments)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $now = now();

        $data = collect($this->comments)
            ->map(fn ($comment) => [
                'id' => $comment['id'],
                'post_id' => $comment['postId'],
                'user_id' => $comment['user']['id'] ?? null,
                'body' => $comment['body'],
                'likes' => $comment['likes'],
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        Comment::upsert(
            $data,
            ['id'],
            [
                'post_id',
                'user_id',
                'body',
                'likes',
                'updated_at',
            ]
        );
    }
}
