<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Services\BadgeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RewardServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_comment_awards_fifty_points_and_beginner_badge(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        Comment::factory()->create(['user_id' => $user->id, 'post_id' => $post->id]);

        $user->refresh();

        $this->assertSame(50, $user->points);
        $this->assertSame(BadgeService::BEGINNER, $user->badge);
    }

    public function test_comments_between_milestones_do_not_award_extra_points(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        // Comments 2 through 29: no milestone should fire.
        Comment::factory()->count(29)->create(['user_id' => $user->id, 'post_id' => $post->id]);

        $user->refresh();

        // Only the 1st-comment milestone (50 pts) has fired so far.
        $this->assertSame(50, $user->points);
        $this->assertSame(BadgeService::BEGINNER, $user->badge);
    }

    public function test_thirtieth_comment_awards_top_fan_badge(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        Comment::factory()->count(30)->create(['user_id' => $user->id, 'post_id' => $post->id]);

        $user->refresh();

        // 1st comment (+50) + 30th comment (+2500) = 2550.
        $this->assertSame(2550, $user->points);
        $this->assertSame(BadgeService::TOP_FAN, $user->badge);
    }

    public function test_fiftieth_comment_awards_super_fan_badge(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        Comment::factory()->count(50)->create(['user_id' => $user->id, 'post_id' => $post->id]);

        $user->refresh();

        // 1st (+50) + 30th (+2500) + 50th (+5000) = 7550.
        $this->assertSame(7550, $user->points);
        $this->assertSame(BadgeService::SUPER_FAN, $user->badge);
    }

    public function test_comments_do_not_award_points_for_other_users(): void
    {
        $author = User::factory()->create();
        $bystander = User::factory()->create();
        $post = Post::factory()->create();

        Comment::factory()->create(['user_id' => $author->id, 'post_id' => $post->id]);

        $bystander->refresh();

        $this->assertSame(0, $bystander->points);
        $this->assertNull($bystander->badge);
    }

    public function test_tenth_like_awards_five_hundred_points_and_beginner_badge(): void
    {
        $liker = User::factory()->create();
        $commentAuthor = User::factory()->create();
        $post = Post::factory()->create();

        $comments = Comment::factory()
            ->count(10)
            ->create(['user_id' => $commentAuthor->id, 'post_id' => $post->id]);

        foreach ($comments as $comment) {
            $comment->likes()->create(['user_id' => $liker->id]);
        }

        $liker->refresh();

        $this->assertSame(500, $liker->points);
        $this->assertSame(BadgeService::BEGINNER, $liker->badge);
    }

    public function test_likes_before_the_tenth_award_no_points(): void
    {
        $liker = User::factory()->create();
        $commentAuthor = User::factory()->create();
        $post = Post::factory()->create();

        $comments = Comment::factory()
            ->count(9)
            ->create(['user_id' => $commentAuthor->id, 'post_id' => $post->id]);

        foreach ($comments as $comment) {
            $comment->likes()->create(['user_id' => $liker->id]);
        }

        $liker->refresh();

        $this->assertSame(0, $liker->points);
        $this->assertNull($liker->badge);
    }

    public function test_comment_and_like_milestones_stack_independently(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        // 30 comments -> top-fan-badge worth of comment points (2550).
        Comment::factory()->count(30)->create(['user_id' => $user->id, 'post_id' => $post->id]);

        // Plus 10 likes on other people's comments -> +500.
        $otherAuthor = User::factory()->create();
        $likableComments = Comment::factory()
            ->count(10)
            ->create(['user_id' => $otherAuthor->id, 'post_id' => $post->id]);

        foreach ($likableComments as $comment) {
            $comment->likes()->create(['user_id' => $user->id]);
        }

        $user->refresh();

        $this->assertSame(3050, $user->points); // 2550 + 500
        // Badge is still driven by the highest tier reached overall.
        $this->assertSame(BadgeService::TOP_FAN, $user->badge);
    }

    public function test_deleting_a_comment_does_not_revoke_previously_earned_points(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $comment = Comment::factory()->create(['user_id' => $user->id, 'post_id' => $post->id]);
        $user->refresh();
        $this->assertSame(50, $user->points);

        $comment->delete();
        $user->refresh();

        $this->assertSame(50, $user->points);
        $this->assertSame(BadgeService::BEGINNER, $user->badge);
    }

    public function test_liking_the_same_comment_twice_only_counts_once(): void
    {
        $liker = User::factory()->create();
        $commentAuthor = User::factory()->create();
        $post = Post::factory()->create();
        $comment = Comment::factory()->create(['user_id' => $commentAuthor->id, 'post_id' => $post->id]);

        $comment->likes()->firstOrCreate(['user_id' => $liker->id]);
        $comment->likes()->firstOrCreate(['user_id' => $liker->id]);

        $this->assertSame(1, $comment->likes()->count());
    }
}
