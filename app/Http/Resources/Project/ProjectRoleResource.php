<?php

namespace App\Http\Resources\Project;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectRoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'role_name'        => $this->role_name,
            'description'      => $this->description,
            'positions_needed' => $this->positions_needed,
            'positions_filled' => $this->positions_filled,
            'has_open_positions' => $this->hasOpenPositions(),
            'created_at'       => $this->created_at?->toISOString(),
            'updated_at'       => $this->updated_at?->toISOString(),
        ];
    }
}
