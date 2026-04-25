<?php

namespace App\Http\Resources\Call;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CallParticipantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'role'             => $this->role->value,
            'joined_at'        => $this->joined_at?->toISOString(),
            'left_at'          => $this->left_at?->toISOString(),
            'duration_seconds' => $this->duration_seconds,
            'is_active'        => $this->left_at === null,
            'user'             => $this->whenLoaded('user', fn() => [
                'id'                  => $this->user->id,
                'username'            => $this->user->username,
                'full_name'           => $this->user->full_name,
                'profile_picture_url' => $this->user->profile_picture_url,
            ]),
        ];
    }
}
