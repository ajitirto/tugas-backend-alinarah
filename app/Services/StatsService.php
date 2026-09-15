<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StatsService
{
    private const TOP_TAGS_KEY = 'stats:heron:top-tags';

    private const TOP_AUTHORS_KEY = 'stats:heron:top-authors';

    public function topTags(): array
    {
        return $this->remember(
            self::TOP_TAGS_KEY,
            fn() => DB::table('posts')
                ->selectRaw('
                    jsonb_array_elements_text(tags) AS tag,
                    COUNT(*) AS count
                ')
                ->groupBy('tag')
                ->orderByDesc('count')
                ->orderBy('tag')
                ->limit(5)
                ->get()
                ->map(fn($item) => [
                    'tag' => $item->tag,
                    'count' => (int) $item->count,
                ])
                ->all()
        );
    }

    public function topAuthors(): array
    {
        return $this->remember(
            self::TOP_AUTHORS_KEY,
            fn() => DB::table('posts')
                ->join('users', 'users.id', '=', 'posts.user_id')
                ->select(
                    'users.id as user_id',
                    'users.username',
                )
                ->selectRaw('SUM(posts.views) AS total_views')
                ->groupBy('users.id', 'users.username')
                ->orderByDesc('total_views')
                ->limit(3)
                ->get()
                ->map(fn($item) => [
                    'user_id' => (int) $item->user_id,
                    'username' => $item->username,
                    'total_views' => (int) $item->total_views,
                ])
                ->all()
        );
    }

    private function remember(string $key, callable $callback): array
    {
        $cached = Cache::get($key);

        if ($cached !== null) {
            return [
                'data' => $cached,
                'cache' => 'HIT',
            ];
        }

        $lock = Cache::lock("{$key}:lock", 10);

        if ($lock->get()) {
            try {
                $cached = Cache::get($key);

                if ($cached !== null) {
                    return [
                        'data' => $cached,
                        'cache' => 'HIT',
                    ];
                }

                Log::info('STATS QUERY', ['key' => $key]);
                sleep(2);

                $result = $callback();

                Cache::put($key, $result);

                return [
                    'data' => $result,
                    'cache' => 'MISS',
                ];
            } finally {
                $lock->release();
            }
        }

        $lock->block(10);

        $cached = Cache::get($key);

        return [
            'data' => $cached,
            'cache' => 'HIT',
        ];
    }

    public function forget(): void
    {
        Cache::forget(self::TOP_TAGS_KEY);
        Cache::forget(self::TOP_AUTHORS_KEY);
    }
}
