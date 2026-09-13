<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $currentUser = $request->user();

        return [
            'id' => $this->id,
            'text' => $this->text,
            'created_at' => $this->created_at?->toIso8601String(),
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'badge' => $this->user->badge,
            ],
            'likes_count' => $this->whenCounted('likes', fn () => $this->likes_count, fn () => $this->likes()->count()),
            'liked_by_current_user' => $currentUser
                ? $this->likes()->where('user_id', $currentUser->id)->exists()
                : false,
            'can_delete' => $currentUser?->id === $this->user_id,
        ];
    }
}
