<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $viewer    = $request->user();
        $isGuest   = $viewer && $viewer->isGuest();
        $isOwner   = $viewer && $viewer->id === $this->id;

        return [
            'id'                  => $this->id,
            'username'            => $this->username,
            'full_name'           => $this->full_name,
            'profile_picture_url' => $this->profile_picture_url,
            'bio'                 => $this->bio,
            'location'            => $this->location,

            // ── Enum-backed fields — always serialize as raw string value ─────
            'role'                         => $this->role->value,
            'account_status'               => $this->account_status->value,
            'identity_verification_level'  => $this->identity_verification_level?->value,
            'identity_verified'            => $this->identity_verified,
            'email_verified'               => $this->email_verified,

            // ── Contact / external links — hidden from guests ─────────────────
            // Guests must register to access contact info. This prevents
            // scraping user data before committing to the platform.
            'email'       => $isOwner || !$isGuest ? $this->email       : null,
            'website_url' => $isOwner || !$isGuest ? $this->website_url : null,
            'linkedin_url'=> $isOwner || !$isGuest ? $this->linkedin_url: null,
            'github_url'  => $isOwner || !$isGuest ? $this->github_url  : null,

            // ── Timestamps ────────────────────────────────────────────────────
            'last_login_at' => $isOwner ? $this->last_login_at : null,
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,

            // ── Counts — only when relation is eager-loaded ───────────────────
            'skills_count'    => $this->whenLoaded('skills',        fn() => $this->skills->count()),
            'portfolio_count' => $this->whenLoaded('portfolioItems', fn() => $this->portfolioItems->count()),

            // ── Relations ─────────────────────────────────────────────────────
            'skills'         => SkillResource::collection($this->whenLoaded('skills')),
            'portfolio_items' => $isGuest
                ? null  // guests never see portfolio items — route-level block + resource-level
                : PortfolioItemResource::collection($this->whenLoaded('portfolioItems')),

            // ── Guest nudge — signals the client to show a register prompt ────
            'guest_restricted' => $isGuest ? true : $this->when(false, null),
        ];
    }
}
