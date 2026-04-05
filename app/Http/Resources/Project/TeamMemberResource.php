<?php

namespace App\Http\Resources\Project;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'project_id'  => $this->project_id,
            'user'        => [
                'id'                  => $this->user->id,
                'username'            => $this->user->username,
                'full_name'           => $this->user->full_name,
                'profile_picture_url' => $this->user->profile_picture_url,
                'identity_verified'   => $this->user->identity_verified,
            ],
            'role'        => $this->when($this->role, fn() => new ProjectRoleResource($this->role)),
            'position'    => $this->position,
            'permissions' => $this->permissions,
            'joined_at'   => $this->joined_at?->toISOString(),
            'left_at'     => $this->left_at?->toISOString(),
            'is_active'   => $this->is_active,
        ];
    }
}
