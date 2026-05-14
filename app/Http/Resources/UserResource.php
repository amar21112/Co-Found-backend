<?php

namespace App\Http\Resources;

use App\Services\ProfilePictureService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * UserResource
 *
 * profile_picture_url is stored in the DB as a relative path
 * (e.g. "profile_pictures/uuid.jpg").
 *
 * This resource converts it to a full public URL on the way out
 * so the frontend never needs to know about the storage internals.
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $viewer  = $request->user();
        $isGuest = $viewer && $viewer->isGuest();
        $isOwner = $viewer && $viewer->id === $this->id;

        /** @var ProfilePictureService $pictureService */
        $pictureService = app(ProfilePictureService::class);

        return [
            'id'                  => $this->id,
            'username'            => $this->username,
            'full_name'           => $this->full_name,

            // ── Profile picture — always returned as a full URL ───────────────
            // The DB column holds a relative storage path; we resolve it here.
            // Falls back to null when no picture is set.
            'profile_picture_url' => $pictureService->toUrl($this->profile_picture_url),

            'bio'      => $this->bio,
            'location' => $this->location,

            // ── Enum-backed fields ────────────────────────────────────────────
            'role'                        => $this->role->value,
            'account_status'              => $this->account_status->value,
            'identity_verification_level' => $this->identity_verification_level?->value,
            'identity_verified'           => $this->identity_verified,
            'email_verified'              => $this->email_verified,

            // ── Contact / external links — hidden from guests ─────────────────
            'email'        => ! $isGuest ? $this->email        : null,
            'website_url'  => $isOwner || ! $isGuest ? $this->website_url  : null,
            'linkedin_url' => $isOwner || ! $isGuest ? $this->linkedin_url : null,
            'github_url'   => $isOwner || ! $isGuest ? $this->github_url   : null,

            // ── Timestamps ────────────────────────────────────────────────────
            'last_login_at' => $isOwner ? $this->last_login_at : null,
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,

            // ── Counts ────────────────────────────────────────────────────────
            'skills_count'    => $this->whenLoaded('skills',         fn() => $this->skills->count()),
            'portfolio_count' => $this->whenLoaded('portfolioItems', fn() => $this->portfolioItems->count()),

            // ── Relations ─────────────────────────────────────────────────────
            'skills'          => SkillResource::collection($this->whenLoaded('skills')),
            'portfolio_items' => $isGuest
                ? null
                : PortfolioItemResource::collection($this->whenLoaded('portfolioItems')),

            // ── Guest nudge ───────────────────────────────────────────────────
            'guest_restricted' => $isGuest ? true : $this->when(false, null),
        ];
    }
}
