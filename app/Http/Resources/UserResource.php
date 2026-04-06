<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                          => $this->id,
            'email'                       => $this->email,
            'username'                    => $this->username,
            'full_name'                   => $this->full_name,
            'profile_picture_url'         => $this->profile_picture_url,
            'bio'                         => $this->bio,
            'location'                    => $this->location,
            'website_url'                 => $this->website_url,
            'linkedin_url'               => $this->linkedin_url,
            'github_url'                  => $this->github_url,
            'role'                        => $this->role,
            'account_status'             => $this->account_status,
            'email_verified'             => $this->email_verified,
            'identity_verified'          => $this->identity_verified,
            'identity_verification_level'=> $this->identity_verification_level,
            'last_login_at'              => $this->last_login_at,
            'created_at'                 => $this->created_at,
            'updated_at'                 => $this->updated_at,

            // Counts (only loaded when relation is loaded)
            'skills_count'       => $this->whenLoaded('skills', fn() => $this->skills->count()),
            'portfolio_count'    => $this->whenLoaded('portfolioItems', fn() => $this->portfolioItems->count()),

            // Relations
            'skills'         => SkillResource::collection($this->whenLoaded('skills')),
            'portfolio_items' => PortfolioItemResource::collection($this->whenLoaded('portfolioItems')),
        ];
    }
}
