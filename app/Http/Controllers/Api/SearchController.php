<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $q = $request->string('q')->trim();

        if ($q->isEmpty()) {
            return response()->json([
                'data' => [],
            ]);
        }

        $posts = Post::search($q)
            ->take(20)
            ->get([
                'id',
                'title',
                'body',
            ]);

        return response()->json([
            'data' => $posts,
        ]);
    }
}
