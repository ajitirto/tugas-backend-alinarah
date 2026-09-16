<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePostRequest;
use App\Models\User;
use App\Services\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PostController extends Controller
{
    public function __construct(
        private PostService $postService
    ) {}

    public function index(Request $request): JsonResponse
    {
        Log::info('PostController@index started', [
            'query' => $request->query(),
        ]);

        try {
            $page = max(
                (int) $request->query('page', 1),
                1
            );

            $perPage = min(
                max((int) $request->query('per_page', 10), 1),
                100
            );

            Log::info('PostController@index pagination', [
                'page' => $page,
                'per_page' => $perPage,
            ]);

            $posts = $this->postService->getPosts(
                page: $page,
                perPage: $perPage,
                tag: $request->query('tag'),
                sort: $request->query('sort')
            );

            Log::info('PostController@index service success', [
                'current_page' => $posts->currentPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
            ]);

            $data = $posts->map(fn($post) => [
                'id' => $post->id,
                'title' => $post->title,
                'tags' => $post->tags ?? [],
                'views' => $post->views,
                'likes' => $post->likes,
                'user_id' => $post->user_id,
            ]);

            Log::info('PostController@index response mapping success', [
                'count' => $data->count(),
            ]);

            return response()->json([
                'data' => $data,
                'meta' => [
                    'page' => $posts->currentPage(),
                    'per_page' => $posts->perPage(),
                    'total' => $posts->total(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('PostController@index failed', [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function show(int $id): JsonResponse
    {
        $post = $this->postService->getPost($id);

        if (! $post) {
            return response()->json([
                'message' => 'Post not found.',
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' => $post->id,
                'title' => $post->title,
                'body' => $post->body,
                'tags' => $post->tags ?? [],
                'views' => $post->views,
                'likes' => $post->likes,

                'user' => [
                    'id' => $post->user->id,
                    'username' => $post->user->username,
                ],

                'comments' => $post->comments->map(
                    fn($comment) => [
                        'id' => $comment->id,
                        'body' => $comment->body,
                        'user' => [
                            'id' => $comment->user->id,
                            'username' => $comment->user->username,
                        ],
                    ]
                ),
            ],
        ]);
    }

    public function userPosts(
        int $id,
        Request $request
    ): JsonResponse {
        if (! User::where('id', $id)->exists()) {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }

        $page = max(
            (int) $request->query('page', 1),
            1
        );

        $perPage = min(
            max((int) $request->query('per_page', 10), 1),
            100
        );

        $posts = $this->postService->getUserPosts(
            userId: $id,
            page: $page,
            perPage: $perPage
        );

        return response()->json([
            'data' => $posts->map(fn($post) => [
                'id' => $post->id,
                'title' => $post->title,
                'tags' => $post->tags ?? [],
                'views' => $post->views,
                'likes' => $post->likes,
                'user_id' => $post->user_id,
            ]),
            'meta' => [
                'page' => $posts->currentPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
            ],
        ]);
    }

    public function store(
        StorePostRequest $request
    ): JsonResponse {
        $post = $this->postService->createPost(
            $request->validated()
        );

        return response()->json([
            'data' => [
                'id' => $post->id,
                'title' => $post->title,
                'tags' => $post->tags ?? [],
                'views' => $post->views,
                'likes' => $post->likes,
                'user_id' => $post->user_id,
            ],
        ], 201);
    }

    public function like(Post $post): JsonResponse
    {
        $likes = $this->postService->like($post);

        return response()->json([
            'message' => 'Post liked successfully.',
            'likes' => $likes,
        ]);
    }
}
