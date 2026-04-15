<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'match_type'          => $this->match_type ?: (
                $this->matched_project_id ? 'project' : 'collaborator'
            ),
            'compatibility_score' => $this->compatibility_score,
            'match_reasons'       => $this->match_reasons,
            'viewed'              => $this->viewed,
            'viewed_at'           => $this->viewed_at,
            'saved'               => $this->saved,
            'action_taken'        => $this->action_taken,
            'expires_at'          => $this->expires_at,
            'matched_user'        => new UserResource($this->whenLoaded('matchedUser')),
            'matched_project'     => $this->whenLoaded('matchedProject'),
            'created_at'          => $this->created_at,
        ];
    }
}
