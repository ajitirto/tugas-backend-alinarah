<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StatsService
{
    private const TOP_TAGS_KEY = 'stats:heron:top-tags';

    private const TOP_AUTHORS_KEY = 'stats:heron:top-authors';

    /**
     * @return array{data: list<array{tag: string, count: int}>, cache: 'HIT'|'MISS'}
     */
    public function topTags(): array
    {
        return $this->remember(
            self::TOP_TAGS_KEY,
            function (): array {
                $query = DB::table('posts');

                if (DB::connection()->getDriverName() === 'pgsql') {
                    $query->selectRaw('
                        jsonb_array_elements_text(tags) AS tag,
                        COUNT(*) AS count
                    ');
                    $query->groupBy('tag');
                } else {
                    $query
                        ->selectRaw('JSON_UNQUOTE(jt.tag) AS tag, COUNT(*) AS count')
                        ->crossJoin(DB::raw('JSON_TABLE(tags, \'$[*]\' COLUMNS (tag VARCHAR(255) PATH \'$\')) AS jt'));
                    $query->groupBy('jt.tag');
                }

                return array_values(
                    $query
                        ->orderByDesc('count')
                        ->orderBy('tag')
                        ->limit(5)
                        ->get()
                        ->map(fn ($item): array => [
                            'tag' => (string) $item->tag,
                            'count' => (int) $item->count,
                        ])
                        ->all()
                );
            }
        );
    }

    /**
     * @return array{data: list<array{user_id: int, username: string, total_views: int}>, cache: 'HIT'|'MISS'}
     */
    public function topAuthors(): array
    {
        return $this->remember(
            self::TOP_AUTHORS_KEY,
            function (): array {
                return array_values(
                    DB::table('posts')
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
                        ->map(fn ($item): array => [
                            'user_id' => (int) $item->user_id,
                            'username' => (string) $item->username,
                            'total_views' => (int) $item->total_views,
                        ])
                        ->all()
                );
            }
        );
    }

    /**
     * @template TData of array
     *
     * @param  callable(): TData  $callback
     * @return array{data: TData, cache: 'HIT'|'MISS'}
     */
    private function remember(string $key, callable $callback): array
    {
        $cached = Cache::get($key);

        if (is_array($cached)) {
            return [
                'data' => $cached,
                'cache' => 'HIT',
            ];
        }

        $lock = Cache::lock("{$key}:lock", 10);

        if ($lock->get()) {
            try {
                $cached = Cache::get($key);

                if (is_array($cached)) {
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
