<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\BadgeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Covers the graded endpoint: GET /api/rewards/users
 */
class RewardsApiTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'unit-test-access-token';

    private function seedAccessToken(): User
    {
        $reviewer = User::factory()->create();

        DB::table('personal_access_tokens')->insert([
            'tokenable_type' => User::class,
            'tokenable_id' => $reviewer->id,
            'name' => 'test',
            'token' => hash('sha256', self::TOKEN),
            'abilities' => json_encode(['*']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $reviewer;
    }

    public function test_request_without_access_token_returns_nothing(): void
    {
        User::factory()->count(3)->create();

        $response = $this->getJson('/api/rewards/users');

        $response->assertStatus(401);
        $this->assertEmpty($response->getContent());
    }

    public function test_request_with_wrong_access_token_returns_nothing(): void
    {
        $this->seedAccessToken();
        User::factory()->count(3)->create();

        $response = $this->getJson('/api/rewards/users?access_token=not-the-right-token');

        $response->assertStatus(401);
        $this->assertEmpty($response->getContent());
    }

    public function test_request_with_valid_access_token_lists_all_users(): void
    {
        $reviewer = $this->seedAccessToken();
        User::factory()->count(3)->create();

        $response = $this->getJson('/api/rewards/users?access_token='.self::TOKEN);

        $response->assertOk();
        // 1 reviewer + 3 extra users.
        $response->assertJsonCount(4, 'data');
        $this->assertSame($reviewer->id, $response->json('data.0.id'));
    }

    public function test_response_includes_points_badge_and_next_badge(): void
    {
        $this->seedAccessToken();
        User::factory()->withPoints(3000)->create(['name' => 'Top Fan Example']);

        $response = $this->getJson('/api/rewards/users?access_token='.self::TOKEN);

        $response->assertOk();
        $user = collect($response->json('data'))->firstWhere('name', 'Top Fan Example');

        $this->assertSame(3000, $user['points']);
        $this->assertSame(BadgeService::TOP_FAN, $user['badge']);
        $this->assertSame(BadgeService::SUPER_FAN, $user['next_badge']);
    }

    public function test_filter_by_badge_type(): void
    {
        $this->seedAccessToken();
        User::factory()->withPoints(50)->create(['name' => 'Beginner Example']);
        User::factory()->withPoints(5000)->create(['name' => 'Super Fan Example']);

        $response = $this->getJson('/api/rewards/users?access_token='.self::TOKEN.'&type='.BadgeService::SUPER_FAN);

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name');

        $this->assertTrue($names->contains('Super Fan Example'));
        $this->assertFalse($names->contains('Beginner Example'));
    }

    public function test_filter_by_points(): void
    {
        $this->seedAccessToken();
        User::factory()->withPoints(2550)->create(['name' => 'Exactly 2550']);
        User::factory()->withPoints(3000)->create(['name' => 'Exactly 3000']);

        $response = $this->getJson('/api/rewards/users?access_token='.self::TOKEN.'&points=2550');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name');

        $this->assertTrue($names->contains('Exactly 2550'));
        $this->assertFalse($names->contains('Exactly 3000'));
    }

    public function test_next_badge_is_null_for_users_at_the_top_tier(): void
    {
        $this->seedAccessToken();
        User::factory()->withPoints(9000)->create(['name' => 'Maxed Out']);

        $response = $this->getJson('/api/rewards/users?access_token='.self::TOKEN);

        $user = collect($response->json('data'))->firstWhere('name', 'Maxed Out');

        $this->assertSame(BadgeService::SUPER_FAN, $user['badge']);
        $this->assertNull($user['next_badge']);
    }
}
