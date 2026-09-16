<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $q = $request->string('q')->trim();

        if ($q->isEmpty()) {
            return response()->json([
                'data' => [],
            ]);
        }

        $posts = Post::search($q)
            ->take(20)
            ->get();

        return response()->json([
            'data' => $posts,
        ]);
    }
}
