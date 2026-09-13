<?php

namespace Tests\Feature;

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
}
