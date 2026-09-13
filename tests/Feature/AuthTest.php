<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_with_a_known_email_returns_a_token(): void
    {
        $user = User::factory()->create(['email' => 'known@example.com']);

        $response = $this->postJson('/api/auth/login', ['email' => 'known@example.com']);

        $response->assertOk();
        $response->assertJsonPath('user.id', $user->id);
        $this->assertNotEmpty($response->json('token'));
    }

    public function test_login_with_an_unknown_email_fails_validation(): void
    {
        $response = $this->postJson('/api/auth/login', ['email' => 'nobody@example.com']);

        $response->assertStatus(422);
    }

    public function test_demo_users_endpoint_lists_users_without_authentication(): void
    {
        User::factory()->count(2)->create();

        $response = $this->getJson('/api/auth/demo-users');

        $response->assertOk();
        $response->assertJsonCount(2);
    }

    public function test_demo_users_endpoint_does_not_leak_reward_fields(): void
    {
        User::factory()->withPoints(3000)->create(['name' => 'Should Not Leak']);

        $response = $this->getJson('/api/auth/demo-users');

        $user = collect($response->json())->firstWhere('name', 'Should Not Leak');

        $this->assertArrayNotHasKey('points', $user);
        $this->assertArrayNotHasKey('badge', $user);
        $this->assertArrayNotHasKey('next_badge', $user);
    }

    public function test_token_from_login_can_be_used_to_authenticate(): void
    {
        $user = User::factory()->create(['email' => 'known@example.com']);
        $token = $this->postJson('/api/auth/login', ['email' => 'known@example.com'])->json('token');

        $response = $this->getJson('/api/auth/user', ['Authorization' => "Bearer {$token}"]);

        $response->assertOk();
        $response->assertJsonPath('id', $user->id);
    }

    public function test_registering_creates_a_new_user_and_returns_a_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('user.name', 'Ada Lovelace');
        $response->assertJsonPath('user.email', 'ada@example.com');
        $response->assertJsonPath('user.points', 0);
        $response->assertJsonPath('user.badge', null);
        $this->assertNotEmpty($response->json('token'));

        $this->assertDatabaseHas('users', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
        ]);
    }

    public function test_registering_with_an_existing_email_fails_validation(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Someone Else',
            'email' => 'taken@example.com',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_registering_requires_a_name_and_email(): void
    {
        $response = $this->postJson('/api/auth/register', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'email']);
    }

    public function test_a_newly_registered_user_can_immediately_comment(): void
    {
        $post = Post::factory()->create();

        $token = $this->postJson('/api/auth/register', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
        ])->json('token');

        $response = $this->postJson(
            "/api/posts/{$post->id}/comments",
            ['text' => 'Hello!'],
            ['Authorization' => "Bearer {$token}"]
        );

        $response->assertCreated();
    }
}
