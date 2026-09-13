<?php

namespace App\Observers;

use App\Models\Comment;
use App\Services\RewardService;

class CommentObserver
{
    public function __construct(private readonly RewardService $rewards) {}

    public function created(Comment $comment): void
    {
        $this->rewards->handleCommentCreated($comment->user);
    }
}
