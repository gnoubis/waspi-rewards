<?php

namespace Tests\Unit;

use App\Services\BadgeService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class BadgeServiceTest extends TestCase
{
    #[DataProvider('pointsProvider')]
    public function test_badge_for_points(int $points, ?string $expectedBadge): void
    {
        $this->assertSame($expectedBadge, BadgeService::badgeForPoints($points));
    }

    public static function pointsProvider(): array
    {
        return [
            'zero points has no badge' => [0, null],
            'just below beginner threshold' => [49, null],
            'exactly beginner threshold' => [50, BadgeService::BEGINNER],
            'within beginner range (likes milestone)' => [500, BadgeService::BEGINNER],
            'just below top-fan threshold' => [2499, BadgeService::BEGINNER],
            'exactly top-fan threshold' => [2500, BadgeService::TOP_FAN],
            // The example given in the task brief: "if a user is rewarded
            // 3000 pts let them have a top-fan-badge".
            '3000 points from the task brief example' => [3000, BadgeService::TOP_FAN],
            'just below super-fan threshold' => [4999, BadgeService::TOP_FAN],
            'exactly super-fan threshold' => [5000, BadgeService::SUPER_FAN],
            'well above super-fan threshold' => [50000, BadgeService::SUPER_FAN],
        ];
    }

    public function test_next_badge_when_user_has_no_badge_yet(): void
    {
        $this->assertSame(BadgeService::BEGINNER, BadgeService::nextBadge(null));
    }

    public function test_next_badge_progression(): void
    {
        $this->assertSame(BadgeService::TOP_FAN, BadgeService::nextBadge(BadgeService::BEGINNER));
        $this->assertSame(BadgeService::SUPER_FAN, BadgeService::nextBadge(BadgeService::TOP_FAN));
    }

    public function test_next_badge_is_null_once_at_the_highest_tier(): void
    {
        $this->assertNull(BadgeService::nextBadge(BadgeService::SUPER_FAN));
    }

    public function test_all_badges_are_ordered_lowest_to_highest(): void
    {
        $this->assertSame(
            [BadgeService::BEGINNER, BadgeService::TOP_FAN, BadgeService::SUPER_FAN],
            BadgeService::allBadges()
        );
    }

    public function test_points_required_for_a_known_badge(): void
    {
        $this->assertSame(50, BadgeService::pointsRequiredFor(BadgeService::BEGINNER));
        $this->assertSame(2500, BadgeService::pointsRequiredFor(BadgeService::TOP_FAN));
        $this->assertSame(5000, BadgeService::pointsRequiredFor(BadgeService::SUPER_FAN));
    }

    public function test_points_required_for_an_unknown_badge_is_null(): void
    {
        $this->assertNull(BadgeService::pointsRequiredFor('made-up-badge'));
    }
}
