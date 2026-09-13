<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_post_a_comment(): void
    {
        $post = Post::factory()->create();

        $response = $this->postJson("/api/posts/{$post->id}/comments", ['text' => 'Hello there']);

        $response->assertStatus(401);
        $this->assertDatabaseCount('comments', 0);
    }

    public function test_authenticated_user_can_post_a_comment(): void
    {
        $user = Sanctum::actingAs(User::factory()->create());
        $post = Post::factory()->create();

        $response = $this->postJson("/api/posts/{$post->id}/comments", ['text' => 'Great post!']);

        $response->assertCreated();
        $response->assertJsonPath('data.text', 'Great post!');
        $response->assertJsonPath('data.user.id', $user->id);

        $this->assertDatabaseHas('comments', [
            'post_id' => $post->id,
            'user_id' => $user->id,
            'text' => 'Great post!',
        ]);
    }

    public function test_comment_text_is_required(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $post = Post::factory()->create();

        $response = $this->postJson("/api/posts/{$post->id}/comments", ['text' => '']);

        $response->assertStatus(422);
    }

    public function test_posting_a_comment_awards_points_to_the_author(): void
    {
        $user = Sanctum::actingAs(User::factory()->create());
        $post = Post::factory()->create();

        $this->postJson("/api/posts/{$post->id}/comments", ['text' => 'First!'])->assertCreated();

        $this->assertSame(50, $user->fresh()->points);
    }

    public function test_owner_can_delete_their_own_comment(): void
    {
        $user = Sanctum::actingAs(User::factory()->create());
        $comment = Comment::factory()->create(['user_id' => $user->id]);

        $response = $this->deleteJson("/api/comments/{$comment->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_user_cannot_delete_someone_elses_comment(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $comment = Comment::factory()->create();

        $response = $this->deleteJson("/api/comments/{$comment->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('comments', ['id' => $comment->id]);
    }

    public function test_guest_cannot_delete_a_comment(): void
    {
        $comment = Comment::factory()->create();

        $response = $this->deleteJson("/api/comments/{$comment->id}");

        $response->assertStatus(401);
        $this->assertDatabaseHas('comments', ['id' => $comment->id]);
    }
}
