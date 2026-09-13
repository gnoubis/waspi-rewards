<?php

namespace App\Services;

use App\Models\User;

/**
 * Awards WASPI REWARDS points to a user when they cross one of the
 * milestones below:
 *
 *  - 1st ever comment      -> +50 points   (beginner-badge threshold)
 *  - 30th comment          -> +2500 points (top-fan-badge threshold)
 *  - 50th comment          -> +5000 points (super-fan-badge threshold)
 *  - 10th like             -> +500 points  (still within beginner-badge range)
 *
 * Milestones are one-off bonuses: they fire exactly once, the moment the
 * user's running total of comments/likes reaches the given count. They do
 * not repeat for every comment/like made afterwards.
 *
 * Points are awarded on creation only. Deleting a comment or unliking a
 * comment does NOT retroactively revoke previously earned points - once
 * a milestone is reached, it stays earned. This avoids surprising
 * "badge downgrades" from a purely incidental cleanup action.
 */
class RewardService
{
    /**
     * @var array<int, int> milestone comment count => points awarded
     */
    public const COMMENT_MILESTONES = [
        1 => 50,
        30 => 2500,
        50 => 5000,
    ];

    /**
     * @var array<int, int> milestone like count => points awarded
     */
    public const LIKE_MILESTONES = [
        10 => 500,
    ];

    /**
     * Call after a comment has been created for the given user. Awards
     * points if the user's new comment count matches a milestone.
     */
    public function handleCommentCreated(User $user): void
    {
        $count = $user->comments()->count();

        $this->awardIfMilestone($user, self::COMMENT_MILESTONES, $count);
    }

    /**
     * Call after a like has been created for the given user. Awards
     * points if the user's new like count matches a milestone.
     */
    public function handleLikeCreated(User $user): void
    {
        $count = $user->likes()->count();

        $this->awardIfMilestone($user, self::LIKE_MILESTONES, $count);
    }

    /**
     * @param  array<int, int>  $milestones
     */
    private function awardIfMilestone(User $user, array $milestones, int $count): void
    {
        if (! array_key_exists($count, $milestones)) {
            return;
        }

        // Deliberately not Model::increment(): it issues a raw UPDATE and
        // does not fire the model's save events, which is what keeps the
        // `badge` column in sync (see User::booted()).
        $user->points += $milestones[$count];
        $user->save();
    }
}
