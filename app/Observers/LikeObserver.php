<?php

namespace App\Observers;

use App\Models\Like;
use App\Services\RewardService;

class LikeObserver
{
    public function __construct(private readonly RewardService $rewards) {}

    public function created(Like $like): void
    {
        $this->rewards->handleLikeCreated($like->user);
    }
}
