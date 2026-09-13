<?php

namespace App\Models;

use App\Services\BadgeService;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'points',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @var list<string>
     */
    protected $appends = [
        'next_badge',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'points' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Keep the persisted `badge` column in sync with `points` any
        // time points change, so the API can filter by badge type with a
        // plain WHERE clause instead of recomputing it for every row.
        static::saving(function (User $user) {
            if ($user->isDirty('points')) {
                $user->badge = BadgeService::badgeForPoints($user->points);
            }
        });
    }

    /**
     * The badge the user should aim for next. Null when they already
     * hold the highest badge available.
     */
    public function getNextBadgeAttribute(): ?string
    {
        return BadgeService::nextBadge($this->badge);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }
}
