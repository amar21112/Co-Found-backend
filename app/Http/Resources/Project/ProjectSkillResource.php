<?php

namespace App\Http\Resources\Project;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectSkillResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'skill_name'           => $this->skill_name,
            'proficiency_required' => $this->proficiency_required,
            'positions_needed'     => $this->positions_needed,
            'positions_filled'     => $this->positions_filled,
            'is_required'          => $this->is_required,
            'has_open_positions'   => $this->hasOpenPositions(),
        ];
    }
}
