<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserRestrictionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'restriction_type' => $this->restriction_type->value,
            'reason'           => $this->reason,
            'duration_hours'   => $this->duration_hours,
            'is_active'        => $this->is_active,
            'is_permanent'     => $this->isPermanent(),
            'is_expired'       => $this->isExpired(),
            'starts_at'        => $this->starts_at?->toISOString(),
            'expires_at'       => $this->expires_at?->toISOString(),
            'lifted_at'        => $this->lifted_at?->toISOString(),
            'created_at'       => $this->created_at?->toISOString(),

            'user'          => new UserResource($this->whenLoaded('user')),
            'restricted_by' => $this->whenLoaded('restrictedBy', fn() => [
                'id'       => $this->restrictedBy->id,
                'username' => $this->restrictedBy->username,
                'role'     => $this->restrictedBy->role->value,
            ]),
            'lifted_by' => $this->whenLoaded('liftedBy', fn() => $this->liftedBy ? [
                'id'       => $this->liftedBy->id,
                'username' => $this->liftedBy->username,
                'role'     => $this->liftedBy->role->value,
            ] : null),
        ];
    }
}
