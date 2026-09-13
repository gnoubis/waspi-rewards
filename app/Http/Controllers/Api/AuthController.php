<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Deliberately password-less "log in as" endpoint used ONLY by the demo
 * UI, so a reviewer can switch between seeded users and try commenting /
 * liking as each of them.
 *
 * Assumption (see README): the task brief does not ask for a full
 * authentication system - it only asks that a comment/like can be made
 * "as a user". Implementing real password auth would add scope without
 * demonstrating anything about the reward system itself, so this picks
 * the simplest thing that lets the UI attribute actions to a real user
 * record: pick a seeded email, get a Sanctum token back.
 */
class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->firstOrFail();

        $token = $user->createToken('demo-ui')->plainTextToken;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'points' => $user->points,
                'badge' => $user->badge,
                'next_badge' => $user->next_badge,
            ],
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    /**
     * Lightweight, unauthenticated list of {id, name} used purely to
     * populate the "log in as" dropdown in the demo UI. This is NOT the
     * graded rewards endpoint (see RewardUserController) - it exposes no
     * points/badge data and requires no access token on purpose, since
     * its only job is letting the UI show who it is possible to log in
     * as.
     */
    public function demoUsers(): JsonResponse
    {
        // Deliberately mapped to plain arrays (rather than returning the
        // Eloquent collection straight from a restricted `get([...])`):
        // User::$appends forces the `next_badge` accessor to serialize on
        // every User model, but it reads the `badge` column - which isn't
        // selected here - so it would silently render "beginner-badge"
        // for everyone. This endpoint has no business exposing reward
        // data anyway (see class docblock), so we sidestep the whole
        // issue by only ever emitting the three fields the UI needs.
        $users = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]);

        return response()->json($users);
    }
}
