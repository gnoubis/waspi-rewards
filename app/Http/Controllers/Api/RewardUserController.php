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
 * The graded "list all users" endpoint from the task brief:
 *
 *   GET /api/rewards/users?access_token=...&type=...&points=...
 *
 * Auth: a valid Sanctum personal access token string is required as the
 * `access_token` query (or body) parameter - NOT as an Authorization
 * header. This matches the brief's wording ("The method should receive
 * an access token") literally. If the token is missing, malformed, or
 * does not match any issued token, the endpoint returns HTTP 401 with an
 * empty body - no user data is leaked either way.
 *
 * Filters (both optional, can be combined):
 *  - type:   exact badge name, e.g. "top-fan-badge". Users who don't
 *            hold this badge are excluded.
 *  - points: exact points total a user must have to be included.
 *
 * Every returned user also carries `next_badge`: the badge they would
 * earn next, or null if they already hold the highest one.
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
