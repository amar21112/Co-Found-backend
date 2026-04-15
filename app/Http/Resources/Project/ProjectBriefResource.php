<?php

namespace App\Http\Resources\Project;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectBriefResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                       => $this->id,
            'title'                    => $this->title,
            'slug'                     => $this->slug,
            'short_description'        => $this->short_description,
            'category'                 => $this->category,
            'status'                   => $this->status,
            'visibility'               => $this->visibility,
            'current_team_size'        => $this->current_team_size,
            'team_size_min'            => $this->team_size_min,
            'team_size_max'            => $this->team_size_max,
            'is_accepting_applications'=> $this->is_accepting_applications,
            'application_deadline'     => $this->application_deadline?->toDateString(),
            'view_count'               => $this->view_count,
            'application_count'        => $this->application_count,
            'owner'                    => [
                'id'                  => $this->owner->id,
                'username'            => $this->owner->username,
                'full_name'           => $this->owner->full_name,
                'profile_picture_url' => $this->owner->profile_picture_url,
                'identity_verified'   => $this->owner->identity_verified,
            ],
//            'skills'                   => ProjectSkillResource::collection($this->whenLoaded('skills')),
            'created_at'               => $this->created_at?->toISOString(),
        ];
    }
}
