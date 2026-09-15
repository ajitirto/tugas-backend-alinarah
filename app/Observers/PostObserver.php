<?php

namespace App\Observers;

use App\Models\Post;
use App\Services\StatsService;

class PostObserver
{
    public function created(Post $post): void
    {
        $this->clearStatsCache();
    }

    public function updated(Post $post): void
    {
        $this->clearStatsCache();
    }

    public function deleted(Post $post): void
    {
        $this->clearStatsCache();
    }

    private function clearStatsCache(): void
    {
        app(StatsService::class)->forget();
    }
}
