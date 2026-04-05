<?php

namespace App\Http\Resources\Project;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationSkillResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'skill_name'          => $this->skill_name,
            'proficiency_claimed' => $this->proficiency_claimed,
        ];
    }
}
