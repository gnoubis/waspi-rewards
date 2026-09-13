<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds a small but complete demo dataset for WASPI REWARDS:
 *
 *  - a "reviewer" account with a fixed, documented Sanctum access token
 *    (see README "API - graded endpoint") so the graded endpoint can be
 *    tested immediately without registering anything;
 *  - a handful of authors and posts to comment on;
 *  - one showcase user per badge milestone described in the task brief,
 *    created by actually performing the comment/like actions (not by
 *    setting `points` directly) so the seeder doubles as a live
 *    end-to-end demonstration that RewardService + BadgeService work.
 */
class WaspiRewardsSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedReviewerToken();

        $authors = User::factory()->count(3)->create();

        $posts = collect();
        foreach ($authors as $author) {
            $posts = $posts->merge(Post::factory()->for($author)->count(2)->create());
        }

        // Filler users/comments purely so there is enough content for the
        // "liker" showcase user below to like 10 distinct comments.
        $fillerComments = collect();
        foreach (User::factory()->count(10)->create() as $fillerUser) {
            $fillerComments->push(
                Comment::factory()->create([
                    'user_id' => $fillerUser->id,
                    'post_id' => $posts->random()->id,
                ])
            );
        }

        // --- No actions yet: no badge ---
        User::factory()->create([
            'name' => 'Nadia Newcomer',
            'email' => 'newcomer@waspito.test',
        ]);

        // --- 1st ever comment -> +50 pts, beginner-badge ---
        $beginner = User::factory()->create([
            'name' => 'Boris Beginner',
            'email' => 'beginner@waspito.test',
        ]);
        Comment::factory()->create([
            'user_id' => $beginner->id,
            'post_id' => $posts->random()->id,
        ]);

        // --- 10th like -> +500 pts, beginner-badge ---
        $liker = User::factory()->create([
            'name' => 'Lina Liker',
            'email' => 'liker@waspito.test',
        ]);
        foreach ($fillerComments->take(10) as $comment) {
            $comment->likes()->create(['user_id' => $liker->id]);
        }

        // --- 30 comments -> 50 + 2500 = 2550 pts, top-fan-badge ---
        $topFan = User::factory()->create([
            'name' => 'Tomas TopFan',
            'email' => 'topfan@waspito.test',
        ]);
        for ($i = 0; $i < 30; $i++) {
            Comment::factory()->create([
                'user_id' => $topFan->id,
                'post_id' => $posts->random()->id,
            ]);
        }

        // --- 50 comments -> 50 + 2500 + 5000 = 7550 pts, super-fan-badge ---
        $superFan = User::factory()->create([
            'name' => 'Sofia SuperFan',
            'email' => 'superfan@waspito.test',
        ]);
        for ($i = 0; $i < 50; $i++) {
            Comment::factory()->create([
                'user_id' => $superFan->id,
                'post_id' => $posts->random()->id,
            ]);
        }
    }

    /**
     * Creates a "reviewer" user and manually issues a Sanctum personal
     * access token with a fixed, known plaintext value (Sanctum normally
     * only reveals the plaintext once, at creation time, which is
     * useless for a reproducible seeder). The plaintext is whatever
     * REVIEWER_ACCESS_TOKEN is set to in .env, defaulting to a fixed
     * value documented in the README so reviewers can use it immediately.
     */
    private function seedReviewerToken(): void
    {
        $reviewer = User::factory()->create([
            'name' => 'Waspito Reviewer',
            'email' => 'reviewer@waspito.test',
        ]);

        $plainToken = env('REVIEWER_ACCESS_TOKEN', 'waspito-reviewer-access-token-2026');

        DB::table('personal_access_tokens')->insert([
            'tokenable_type' => User::class,
            'tokenable_id' => $reviewer->id,
            'name' => 'reviewer-access',
            'token' => hash('sha256', $plainToken),
            'abilities' => json_encode(['*']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
