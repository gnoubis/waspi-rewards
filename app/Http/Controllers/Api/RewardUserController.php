<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserRewardResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * GET /api/rewards/users?access_token=...&type=...&points=...
 *
 * Lists users with their points/badge. The access token is a plain
 * query/body parameter rather than an Authorization header, and is
 * checked against issued Sanctum tokens directly - missing or wrong,
 * and this returns 401 with an empty body, no exceptions.
 *
 * Optional filters, combinable:
 *  - type:   exact badge name, e.g. "top-fan-badge".
 *  - points: exact points total a user must have.
 *
 * Every user also carries `next_badge`: the badge they'd earn next, or
 * null once they hold the highest one.
 */
class RewardUserController extends Controller
{
    public function __invoke(Request $request): AnonymousResourceCollection|Response
    {
        $accessToken = $request->input('access_token');

        if (! is_string($accessToken) || $accessToken === '') {
            return response()->noContent(401);
        }

        $token = PersonalAccessToken::findToken($accessToken);

        if (! $token) {
            return response()->noContent(401);
        }

        $validated = $request->validate([
            'type' => ['sometimes', 'string'],
            'points' => ['sometimes', 'integer', 'min:0'],
        ]);

        $users = User::query()
            ->when(
                $validated['type'] ?? null,
                fn ($query, $type) => $query->where('badge', $type)
            )
            ->when(
                array_key_exists('points', $validated),
                fn ($query) => $query->where('points', $validated['points'])
            )
            ->orderBy('id')
            ->get();

        return UserRewardResource::collection($users);
    }
}
