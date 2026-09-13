<?php

namespace App\Services;

/**
 * Pure, stateless logic for turning a user's total points into a badge,
 * and for figuring out which badge they should aim for next.
 *
 * Badges are derived directly from the *total points* a user has
 * accumulated, in ascending order of tier. This is a deliberate design
 * decision (see README "Assumptions"): the reward system awards points
 * for specific milestones (1st / 30th / 50th comment, 10th like), but the
 * badge a user currently holds is simply "the highest tier whose point
 * threshold has been reached", regardless of which actions produced those
 * points. This keeps the mapping points -> badge a single source of truth
 * that is trivial to test (e.g. "a user with 3000 points has top-fan-badge").
 */
class BadgeService
{
    public const BEGINNER = 'beginner-badge';

    public const TOP_FAN = 'top-fan-badge';

    public const SUPER_FAN = 'super-fan-badge';

    /**
     * Ordered (ascending) list of badge tiers and the total points
     * required to reach each one.
     *
     * @var array<int, array{badge: string, points: int}>
     */
    private const TIERS = [
        ['badge' => self::BEGINNER, 'points' => 50],
        ['badge' => self::TOP_FAN, 'points' => 2500],
        ['badge' => self::SUPER_FAN, 'points' => 5000],
    ];

    /**
     * Returns every badge name known to the system, ordered from lowest
     * to highest tier.
     *
     * @return list<string>
     */
    public static function allBadges(): array
    {
        return array_column(self::TIERS, 'badge');
    }

    /**
     * Determine the badge a user with the given total points currently
     * holds. Returns null when the user has not reached the first tier
     * yet (i.e. has no badge at all).
     */
    public static function badgeForPoints(int $points): ?string
    {
        $earned = null;

        foreach (self::TIERS as $tier) {
            if ($points >= $tier['points']) {
                $earned = $tier['badge'];
            } else {
                break;
            }
        }

        return $earned;
    }

    /**
     * Determine the next badge a user should aim for, given the badge
     * they currently hold (or null if they hold none yet).
     *
     * Returns null when the user already holds the highest available
     * badge, meaning there is nothing left to earn.
     */
    public static function nextBadge(?string $currentBadge): ?string
    {
        if ($currentBadge === null) {
            return self::TIERS[0]['badge'];
        }

        $index = array_search($currentBadge, self::allBadges(), true);

        if ($index === false) {
            return self::TIERS[0]['badge'];
        }

        return self::TIERS[$index + 1]['badge'] ?? null;
    }

    /**
     * The total points required to reach a given badge, or null if the
     * badge name is not recognised.
     */
    public static function pointsRequiredFor(string $badge): ?int
    {
        foreach (self::TIERS as $tier) {
            if ($tier['badge'] === $badge) {
                return $tier['points'];
            }
        }

        return null;
    }
}
