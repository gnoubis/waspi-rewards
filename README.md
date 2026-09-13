# WASPI REWARDS

A points/badge reward system for a social feed: users earn points and badges
for commenting on and liking posts. Built for the Waspito technical
assessment.

- **Stack:** Laravel 11 (API) + Vue 3 / Vite (SPA-style frontend) + SCSS,
  single repo, single deployable app.
- **Live demo:** _\<add your deployed URL here\>_
- **Repository:** _\<add your GitHub repository URL here\>_

---

## 1. Graded endpoint (read this first)

```
GET /api/rewards/users?access_token=waspito-reviewer-access-token-2026
```

Optional filters (combinable):

| Filter   | Meaning                                             | Example                 |
|----------|------------------------------------------------------|--------------------------|
| `type`   | exact badge name a user must hold                    | `type=top-fan-badge`    |
| `points` | exact points total a user must have                  | `points=2550`            |

- Missing or wrong `access_token` -> **HTTP 401, empty body**. No user data
  is ever returned without a valid token.
- Every user in the response includes `next_badge`: the badge they'd earn
  next, or `null` if they already hold the highest one (`super-fan-badge`).

Example:

```bash
curl "https://<your-deployed-url>/api/rewards/users?access_token=waspito-reviewer-access-token-2026"

curl "https://<your-deployed-url>/api/rewards/users?access_token=waspito-reviewer-access-token-2026&type=super-fan-badge"

curl "https://<your-deployed-url>/api/rewards/users?access_token=waspito-reviewer-access-token-2026&points=2550"
```

Sample response:

```json
{
  "data": [
    {
      "id": 18,
      "name": "Tomas TopFan",
      "email": "topfan@waspito.test",
      "points": 2550,
      "badge": "top-fan-badge",
      "next_badge": "super-fan-badge"
    }
  ]
}
```

The token above is seeded automatically by `php artisan db:seed` (see
`database/seeders/WaspiRewardsSeeder.php`) for a "Waspito Reviewer" account,
from the `REVIEWER_ACCESS_TOKEN` env var (defaults to the value shown above
if unset). **Do not change `REVIEWER_ACCESS_TOKEN` on the deployed host**
unless you update this README to match.

---

## 2. Running the project locally

Requirements: PHP 8.2+, Composer, Node 18+, npm.

```bash
git clone <this-repository-url>
cd waspito-test

composer install
npm install

cp .env.example .env
php artisan key:generate

touch database/database.sqlite   # the app uses SQLite - zero setup, one file
php artisan migrate
php artisan db:seed              # creates demo users, posts, and the reviewer token

npm run build                    # or `npm run dev` in a second terminal for HMR
php artisan serve
```

Visit `http://127.0.0.1:8000`. Use the "Log in as..." dropdown in the header
to act as one of the seeded demo users (see below) and try commenting/liking.

### Running the tests

```bash
php artisan test
```

50 tests cover: badge/points math (unit), the comment and like reward
flows end-to-end, the graded API endpoint (auth + filters), comment
CRUD, and like/unlike. See `tests/Unit/BadgeServiceTest.php` and
`tests/Feature/*`.

### Seeded demo accounts

| Name             | Email                    | Why they're there                          |
|------------------|--------------------------|---------------------------------------------|
| Waspito Reviewer | reviewer@waspito.test    | Holds the fixed API access token            |
| Nadia Newcomer   | newcomer@waspito.test    | No badge yet (no actions taken)             |
| Boris Beginner   | beginner@waspito.test    | 1 comment -> `beginner-badge`               |
| Lina Liker       | liker@waspito.test       | 10 likes -> `beginner-badge`                |
| Tomas TopFan     | topfan@waspito.test      | 30 comments -> `top-fan-badge`              |
| Sofia SuperFan   | superfan@waspito.test    | 50 comments -> `super-fan-badge`            |

Logging in from the UI is password-less by design for this demo - see
**Assumptions** below for why.

---

## 3. The reward rules, as implemented

| Trigger                | Points awarded | Badge unlocked     |
|-------------------------|----------------|---------------------|
| 1st ever comment         | +50            | `beginner-badge`    |
| 30th comment             | +2500          | `top-fan-badge`     |
| 50th comment             | +5000          | `super-fan-badge`   |
| 10th like                | +500           | `beginner-badge`    |

- Milestones are **one-off bonuses**: they fire exactly once, the moment a
  user's comment/like count reaches that number - not on every action
  afterwards.
- A user's **badge is derived purely from their total points**, via the
  same thresholds (50 / 2500 / 5000), regardless of whether those points
  came from commenting or liking. This is what makes the brief's own
  example work exactly as stated: *"if a user is rewarded 3000 pts, let
  them have a top-fan-badge"* - see `App\Services\BadgeService` and
  `tests/Unit/BadgeServiceTest.php`.
- All of this logic lives in two small, framework-agnostic classes:
  `App\Services\BadgeService` (points -> badge, badge -> next badge) and
  `App\Services\RewardService` (counts -> which milestone just fired).

---

## 4. Assumptions

The brief leaves several implementation details open. Here is exactly what
was assumed and why, so nothing is a silent surprise during review:

1. **A `Post` model was added.** The brief describes liking/commenting "on
   a post" but only specifies `User`, `Comment`, and `Like` in the data
   model. Comments need something to belong to, so a minimal `Post`
   (title, body, author) was added and seeded - no post CRUD UI was
   built, since it isn't asked for.

2. **Badges are derived from total points, not tracked as earned
   "counters."** See section 3. This was chosen because it's the only
   interpretation consistent with the brief's own worked example (3000
   pts -> top-fan-badge), and it keeps "what badge does this user have"
   a pure function of one column instead of stateful bookkeeping.

3. **Points are not revoked on delete/unlike.** Deleting a comment or
   unliking a comment does not retroactively subtract the points that
   milestone already awarded. Once earned, a badge/points stay earned.
   This avoids a confusing "badge downgrade" from an incidental cleanup
   action, and the brief doesn't ask for the reverse behavior.

4. **`vue-js-modal` was not used.** It's a Vue 2-only plugin with no Vue 3
   release, and Laravel's current official frontend scaffolding (and this
   project) uses Vue 3 + Vite. Per the brief's own fallback instruction -
   "anything not [a cited library] should be done in vanilla JS" - the
   modal (`resources/js/components/AppModal.vue`) is a small hand-rolled
   Vue component using `<Teleport>`, plain CSS/SCSS transitions, and no
   extra dependency. It satisfies the actual requirement (a button opens
   a modal to add a comment) without a broken/incompatible dependency.

5. **Login/sign-up is password-less, for the demo UI only.** The brief
   asks for a way to "add a comment as a user" / "like a comment as a
   user" - not for a full authentication system. The UI lets you either
   pick an existing user by email (`POST /api/auth/login`) or create a
   brand new one on the spot via the "New user" button (name + email
   only, `POST /api/auth/register`) - either way you get back a real
   Sanctum token, so every comment/like is attributed to a real,
   authenticated user server-side. A registered user gets a random,
   never-surfaced password under the hood purely because the `users`
   table requires one; there is no password login flow to go with it.
   This keeps scope focused on the reward system rather than
   reimplementing auth. `GET /api/auth/demo-users` is a UI-only
   convenience endpoint (name/email only, no auth) and is unrelated to
   the graded, token-gated `GET /api/rewards/users` endpoint.

6. **The `points` filter on the graded endpoint is an exact match**
   ("users having those points" was read literally), not a minimum
   threshold.

7. **SQLite** is used as the only datastore, for both app and tests
   (in-memory for tests). This makes the whole project runnable and
   deployable with zero external database setup, at the cost of not
   reflecting a "real" production database choice.

8. **Brand colors were sampled from `app.waspito.com`** (primary green,
   its hero-gradient orange, the teal business accent) and applied to the
   SCSS design tokens in `resources/scss/_variables.scss`. The header
   mark and badge icons are original inline SVGs (not Waspito's actual
   logo), and the reward tiers deliberately walk the same green -> olive
   -> orange gradient Waspito uses on its own homepage headline.

9. **A comment can be liked by the same user only once** (unique
   constraint on `user_id` + `comment_id`); liking an already-liked
   comment is treated as idempotent (still returns 200) rather than an
   error.

---

## 5. Bonus features implemented

- **Delete a comment** (`DELETE /api/comments/{comment}`) - only the
  comment's author may delete it.
- **Unlike a comment** (`DELETE /api/comments/{comment}/like`).
- Tailwind CSS is wired in alongside hand-written SCSS (variables, maps,
  mixins, nesting - see `resources/scss/`), as suggested as a plus in the
  brief.

---

## 6. API reference (full)

All endpoints are under `/api`. Endpoints under "Auth-gated" require an
`Authorization: Bearer <token>` header, obtained from `/auth/login`.

| Method | Path                          | Auth        | Purpose                                   |
|--------|-------------------------------|-------------|--------------------------------------------|
| GET    | `/rewards/users`              | access_token param | **Graded endpoint** - list/filter users, points, badges |
| POST   | `/auth/login`                 | none        | Demo "log in as" by email, returns a Sanctum token |
| POST   | `/auth/register`              | none        | Create a new user (name + email) and log in as them |
| POST   | `/auth/logout`                | Bearer      | Revoke the current token                   |
| GET    | `/auth/user`                  | Bearer      | Current authenticated user                 |
| GET    | `/auth/demo-users`            | none        | `{id, name, email}` list for the UI's login dropdown |
| GET    | `/posts`                      | none        | List posts with their comments             |
| POST   | `/posts/{post}/comments`      | Bearer      | Add a comment to a post                    |
| DELETE | `/comments/{comment}`         | Bearer      | Delete your own comment                    |
| POST   | `/comments/{comment}/like`    | Bearer      | Like a comment (idempotent)                |
| DELETE | `/comments/{comment}/like`    | Bearer      | Unlike a comment                           |

---

## 7. Deployment

This repo ships a `Dockerfile` that builds the frontend assets and serves
the whole app (API + SPA) from a single container via `php artisan serve`.
This is a deliberate simplification for a reviewable demo, not a
production PHP-FPM/Nginx setup - see Assumptions.

### Option A: Railway / Render (recommended, free tier)

1. Push this repository to GitHub (see section 8).
2. Create a new service on [Railway](https://railway.app) or
   [Render](https://render.com), pointing it at your GitHub repo, and let
   it detect the `Dockerfile`.
3. Set these environment variables on the service:
   - `APP_KEY` - generate one locally with `php artisan key:generate --show`
     and paste the `base64:...` value, so it's stable across restarts.
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `REVIEWER_ACCESS_TOKEN=waspito-reviewer-access-token-2026` (or your
     own value - just make sure it matches what you put in section 1).
4. Deploy. The container runs migrations + the seeder automatically on
   boot (`docker/entrypoint.sh`), so the demo data (including the
   reviewer token) is ready immediately.
5. Note: because the database is a local SQLite file inside the
   container, **data resets on every redeploy/restart**. That's fine for
   review purposes; it is not how you'd run this for real users.

### Option B: any Docker host

```bash
docker build -t waspi-rewards .
docker run -p 8080:8080 \
  -e APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')" \
  -e REVIEWER_ACCESS_TOKEN=waspito-reviewer-access-token-2026 \
  waspi-rewards
```

Then visit `http://localhost:8080`.

---

## 8. Publishing to GitHub

```bash
git init   # if not already a repo
git add .
git commit -m "WASPI REWARDS: points/badges reward system"
git branch -M main
git remote add origin <your-empty-github-repo-url>
git push -u origin main
```

---

## 9. Project structure (backend highlights)

```
app/
  Services/
    BadgeService.php     # points -> badge, badge -> next badge (pure, unit-tested)
    RewardService.php     # comment/like counts -> which milestone just fired
  Observers/
    CommentObserver.php   # awards points after a comment is created
    LikeObserver.php      # awards points after a like is created
  Http/Controllers/Api/
    RewardUserController.php  # the graded endpoint
    AuthController.php        # demo login + demo-users convenience list
    PostController.php, CommentController.php, LikeController.php
  Models/
    User.php  (points, badge columns; badge kept in sync via a model event)
    Post.php, Comment.php, Like.php
database/
  seeders/WaspiRewardsSeeder.php  # reviewer token + one showcase user per badge tier
resources/
  js/       # Vue 3 SPA (components/, composables/, App.vue)
  scss/     # hand-written SCSS design system (variables, mixins, components)
tests/
  Unit/BadgeServiceTest.php
  Feature/{RewardServiceTest,RewardsApiTest,CommentTest,LikeTest,AuthTest}.php
```
