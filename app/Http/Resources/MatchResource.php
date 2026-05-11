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
            'match_type'          => $this->match_type->value,
            'compatibility_score' => $this->compatibility_score,
            'match_reasons'       => $this->match_reasons,
            'viewed'              => $this->viewed,
            'viewed_at'           => $this->viewed_at?->toISOString(),
            'saved'               => $this->saved,
            'action_taken'        => $this->action_taken,
            'expires_at'          => $this->expires_at?->toISOString(),
            'created_at'          => $this->created_at?->toISOString(),

            // Matched entity — only one is populated per match_type
            'matched_user' => $this->when(
                $this->isUserMatch() && $this->relationLoaded('matchedUser') && $this->matchedUser,
                fn() => [
                    'id'                         => $this->matchedUser->id,
                    'username'                   => $this->matchedUser->username,
                    'full_name'                  => $this->matchedUser->full_name,
                    'profile_picture_url'        => $this->matchedUser->profile_picture_url,
                    'bio'                        => $this->matchedUser->bio,
                    'location'                   => $this->matchedUser->location,
                    'identity_verified'          => $this->matchedUser->identity_verified,
                    'identity_verification_level'=> $this->matchedUser->identity_verification_level?->value,
                ]
            ),

            'matched_project' => $this->when(
                $this->isProjectMatch() && $this->relationLoaded('matchedProject') && $this->matchedProject,
                fn() => [
                    'id'                        => $this->matchedProject->id,
                    'title'                     => $this->matchedProject->title,
                    'slug'                      => $this->matchedProject->slug,
                    'short_description'         => $this->matchedProject->short_description,
                    'category'                  => $this->matchedProject->category,
                    'status'                    => $this->matchedProject->status,
                    'is_accepting_applications' => $this->matchedProject->is_accepting_applications,
                ]
            ),
        ];
    }
}
