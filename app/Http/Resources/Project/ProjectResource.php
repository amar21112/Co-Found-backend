<?php

namespace App\Http\Resources\Project;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $viewer  = $request->user();
        $isGuest = $viewer && $viewer->isGuest();

        return [
            'id'                        => $this->id,
            'title'                     => $this->title,
            'slug'                      => $this->slug,
            'short_description'         => $this->short_description,
            'category'                  => $this->category,
            'status'                    => $this->status,
            'visibility'                => $this->visibility,
            'team_size_min'             => $this->team_size_min,
            'team_size_max'             => $this->team_size_max,
            'current_team_size'         => $this->current_team_size,
            'is_accepting_applications' => $this->is_accepting_applications,
            'application_deadline'      => $this->application_deadline?->toDateString(),
            'view_count'                => $this->view_count,
            'application_count'         => $this->application_count,
            'published_at'              => $this->published_at?->toISOString(),
            'created_at'                => $this->created_at?->toISOString(),
            'updated_at'                => $this->updated_at?->toISOString(),

            // ── Full description — hidden from guests ─────────────────────────
            // Show the teaser (short_description) to guests; the full pitch
            // is only revealed to registered users — incentivises sign-up.
            'full_description' => $isGuest ? null : $this->full_description,

            // ── Timeline — hidden from guests ─────────────────────────────────
            'start_date'             => $isGuest ? null : $this->start_date?->toDateString(),
            'target_completion_date' => $isGuest ? null : $this->target_completion_date?->toDateString(),
            'actual_completion_date' => $isGuest ? null : $this->actual_completion_date?->toDateString(),
            'archived_at'            => $isGuest ? null : $this->archived_at?->toISOString(),

            // ── Owner — always visible, but stripped for guests ───────────────
            'owner' => [
                'id'                  => $this->owner->id,
                'username'            => $this->owner->username,
                'full_name'           => $this->owner->full_name,
                'profile_picture_url' => $this->owner->profile_picture_url,
                'identity_verified'   => $this->owner->identity_verified,
            ],

            // ── Skills — visible to guests (useful for discovery) ─────────────
            'skills' => ProjectSkillResource::collection($this->whenLoaded('skills')),

            // ── Roles — visible to guests (they need to know what's needed) ───
            'roles' => ProjectRoleResource::collection($this->whenLoaded('roles')),

            // ── Milestones — hidden from guests (internal roadmap) ────────────
            // Route-level block + resource-level: double protection.
            'milestones' => $isGuest
                ? null
                : ProjectMilestoneResource::collection($this->whenLoaded('milestones')),

            // ── Guest nudge ───────────────────────────────────────────────────
            'guest_restricted' => $isGuest ? true : $this->when(false, null),
        ];
    }
}
