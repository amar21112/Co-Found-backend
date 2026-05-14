<?php

namespace App\Http\Resources\Project;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Project\ProjectSimpleResource;
class ProjectApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'project' => new ProjectSimpleResource($this->whenLoaded('project')),
            'applicant'     => [
                'id'                  => $this->applicant->id,
                'username'            => $this->applicant->username,
                'full_name'           => $this->applicant->full_name,
                'profile_picture_url' => $this->applicant->profile_picture_url,
                'identity_verified'   => $this->applicant->identity_verified,
            ],
            'role'          => $this->when($this->role, fn() => new ProjectRoleResource($this->role)),
            'proposed_role' => $this->proposed_role,
            'cover_message' => $this->cover_message,
            'availability'  => $this->availability,
            'status'        => $this->status,
            'match_score'   => $this->match_score,
            'has_defined_role' => $this->hasDefinedRole(),
            'skills'        => ApplicationSkillResource::collection($this->whenLoaded('applicationSkills')),
            'reviewer'      => $this->when($this->reviewer, fn() => [
                'id'       => $this->reviewer->id,
                'username' => $this->reviewer->username,
                'full_name'=> $this->reviewer->full_name,
            ]),
            'reviewed_at'   => $this->reviewed_at?->toISOString(),
            'applied_at'    => $this->applied_at?->toISOString(),
            'created_at'    => $this->created_at?->toISOString(),
            'updated_at'    => $this->updated_at?->toISOString(),
        ];
    }
}
