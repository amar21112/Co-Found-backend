<?php

namespace App\Http\Resources\Chat;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParticipantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'user' => [
                'id'                  => $this->user->id,
                'username'            => $this->user->username,
                'full_name'           => $this->user->full_name,
                'profile_picture_url' => $this->user->profile_picture_url,
                'identity_verified'   => $this->user->identity_verified,
            ],
            'is_admin'   => $this->is_admin,
            'joined_at'  => $this->joined_at?->toISOString(),
            'muted'      => $this->muted,
            'muted_until'=> $this->muted_until?->toISOString(),
        ];
    }
}
