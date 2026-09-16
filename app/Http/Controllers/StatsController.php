<?php

namespace App\Http\Controllers;

use App\Services\StatsService;
use Illuminate\Http\JsonResponse;

class StatsController extends Controller
{
    public function __construct(
        private readonly StatsService $stats
    ) {}

    public function topTags(): JsonResponse
    {
        $result = $this->stats->topTags();

        return response()->json($result['data'])
            ->header('X-Cache', $result['cache']);
    }

    public function topAuthors(): JsonResponse
    {
        $result = $this->stats->topAuthors();

        return response()->json($result['data'])
            ->header('X-Cache', $result['cache']);
    }
}
