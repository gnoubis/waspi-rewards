<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Shape returned by the graded "list users" endpoint
 * (GET /api/rewards/users). See routes/api.php and
 * App\Http\Controllers\Api\RewardUserController.
 */
class UserRewardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'points' => $this->points,
            'badge' => $this->badge,
            'next_badge' => $this->next_badge,
        ];
    }
}
