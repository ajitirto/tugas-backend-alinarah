<?php

namespace App\Listeners;

use App\Events\PostCreated;
use App\Mail\PostCreatedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendPostCreatedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [5, 10, 20];
    }

    public function handle(PostCreated $event): void
    {
        /* $post = $event->post->load('user'); */
        /**/
        /* Mail::to($post->user->email) */
        /*     ->send(new PostCreatedMail($post)); */
        logger()->info('TestFailedJob attempt', [
            'attempt' => $this->job?->attempts(),
        ]);
        throw new \Exception('Testing failed job');
    }
}
