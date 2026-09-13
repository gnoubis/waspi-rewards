<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use Illuminate\Http\JsonResponse;

class LikeController extends Controller
{
    public function store(Comment $comment): JsonResponse
    {
        $user = request()->user();

        // Idempotent: liking an already-liked comment just returns its
        // current state instead of erroring, since from the user's point
        // of view the desired outcome ("I like this") already holds.
        $comment->likes()->firstOrCreate(['user_id' => $user->id]);

        $comment->load('user');

        return (new CommentResource($comment))->response();
    }

    public function destroy(Comment $comment): JsonResponse
    {
        $user = request()->user();

        $comment->likes()->where('user_id', $user->id)->delete();

        $comment->load('user');

        return (new CommentResource($comment))->response();
    }
}
