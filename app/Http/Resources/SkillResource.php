<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SkillResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'skill_name'        => $this->skill_name,
            'proficiency_level' => $this->proficiency_level,
            'years_experience'  => $this->years_experience,
            'is_approved'       => $this->is_approved,
            'endorsements_count'=> $this->whenLoaded('endorsements', fn() => $this->endorsements->count()),
            'endorsements'      => EndorsementResource::collection($this->whenLoaded('endorsements')),
            'created_at'        => $this->created_at,
        ];
    }
}
