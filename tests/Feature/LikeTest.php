<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LikeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_like_a_comment(): void
    {
        $comment = Comment::factory()->create();

        $response = $this->postJson("/api/comments/{$comment->id}/like");

        $response->assertStatus(401);
        $this->assertDatabaseCount('likes', 0);
    }

    public function test_authenticated_user_can_like_a_comment(): void
    {
        $user = Sanctum::actingAs(User::factory()->create());
        $comment = Comment::factory()->create();

        $response = $this->postJson("/api/comments/{$comment->id}/like");

        $response->assertOk();
        $response->assertJsonPath('data.likes_count', 1);
        $response->assertJsonPath('data.liked_by_current_user', true);

        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'comment_id' => $comment->id,
        ]);
    }

    public function test_liking_the_same_comment_twice_is_idempotent(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $comment = Comment::factory()->create();

        $this->postJson("/api/comments/{$comment->id}/like")->assertOk();
        $response = $this->postJson("/api/comments/{$comment->id}/like");

        $response->assertOk();
        $response->assertJsonPath('data.likes_count', 1);
        $this->assertDatabaseCount('likes', 1);
    }

    public function test_user_can_unlike_a_comment(): void
    {
        $user = Sanctum::actingAs(User::factory()->create());
        $comment = Comment::factory()->create();
        $comment->likes()->create(['user_id' => $user->id]);

        $response = $this->deleteJson("/api/comments/{$comment->id}/like");

        $response->assertOk();
        $response->assertJsonPath('data.likes_count', 0);
        $response->assertJsonPath('data.liked_by_current_user', false);
        $this->assertDatabaseCount('likes', 0);
    }

    public function test_tenth_like_via_the_api_awards_points(): void
    {
        $liker = Sanctum::actingAs(User::factory()->create());
        $comments = Comment::factory()->count(10)->create();

        foreach ($comments as $comment) {
            $this->postJson("/api/comments/{$comment->id}/like")->assertOk();
        }

        $this->assertSame(500, $liker->fresh()->points);
    }
}
