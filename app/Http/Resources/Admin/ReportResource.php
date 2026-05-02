<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Admin report resource.
 *
 * List view: reported_user carries a verification_summary block so
 *   moderators can see at a glance whether the reported user is verified
 *   and whether they already have active restrictions.
 *
 * Detail view (show): reported_user carries the full identity verification
 *   record (document images, review history) plus all active restrictions,
 *   so the admin has everything needed to act without switching tabs.
 *
 * The distinction is driven by which relations are eager-loaded in the
 * repository — AdminReportRepository::paginate vs ::findById.
 */
class ReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            // ── Report metadata ───────────────────────────────────────────────
            'id'                    => $this->id,
            'report_type'           => $this->report_type,
            'description'           => $this->description,
            'evidence'              => $this->evidence,
            'status'                => $this->status,
            'priority'              => $this->priority,
            'reported_content_type' => $this->reported_content_type,
            'reported_content_id'   => $this->reported_content_id,
            'resolution_action'     => $this->resolution_action,
            'resolution_notes'      => $this->resolution_notes,
            'resolved_at'           => $this->resolved_at?->toISOString(),
            'created_at'            => $this->created_at?->toISOString(),
            'updated_at'            => $this->updated_at?->toISOString(),

            // ── Who filed the report ──────────────────────────────────────────
            'reporter' => $this->whenLoaded('reporter', fn() =>
                $this->reporter ? [
                    'id'                  => $this->reporter->id,
                    'username'            => $this->reporter->username,
                    'full_name'           => $this->reporter->full_name,
                    'profile_picture_url' => $this->reporter->profile_picture_url,
                    'account_status'      => $this->reporter->account_status->value,
                    'identity_verified'   => $this->reporter->identity_verified,
                ] : null
            ),

            // ── Who was reported ──────────────────────────────────────────────
            // This is the key enriched block. Both list and detail views
            // expose verification status. The detail view adds full document
            // data and restriction history.
            'reported_user' => $this->whenLoaded('reportedUser', fn() =>
                $this->buildReportedUserBlock()
            ),

            // ── Assignment & resolution ───────────────────────────────────────
            'assigned_moderator' => $this->whenLoaded('assignedModerator', fn() =>
                $this->assignedModerator ? [
                    'id'        => $this->assignedModerator->id,
                    'username'  => $this->assignedModerator->username,
                    'full_name' => $this->assignedModerator->full_name,
                ] : null
            ),

            'resolver' => $this->whenLoaded('resolver', fn() =>
                $this->resolver ? [
                    'id'        => $this->resolver->id,
                    'username'  => $this->resolver->username,
                    'full_name' => $this->resolver->full_name,
                ] : null
            ),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Build the reported_user block.
     *
     * The level of detail included depends on which relations were eager-loaded:
     *   - paginate (list): identityVerification summary only
     *   - findById (detail): full identity_verification + active_restrictions
     */
    private function buildReportedUserBlock(): ?array
    {
        $user = $this->reportedUser;

        if (! $user) {
            return null;
        }

        $block = [
            'id'                  => $user->id,
            'username'            => $user->username,
            'full_name'           => $user->full_name,
            'profile_picture_url' => $user->profile_picture_url,
            'email'               => $user->email,
            'role'                => $user->role->value,
            'account_status'      => $user->account_status->value,
            'email_verified'      => $user->email_verified,
            'identity_verified'   => $user->identity_verified,
            'identity_verification_level' => $user->identity_verification_level?->value,
            'created_at'          => $user->created_at?->toISOString(),
        ];

        // ── Identity verification ─────────────────────────────────────────────
        if ($user->relationLoaded('identityVerification')) {
            if (! $user->identityVerification) {
                $block['identity_verification'] = null;
            } elseif ($user->identityVerification->relationLoaded('reviews')) {
                // Detail view — full verification record
                $block['identity_verification'] = new IdentityVerificationDetailResource(
                    $user->identityVerification
                );
            } else {
                // List view — compact summary
                $block['identity_verification'] = new IdentityVerificationSummaryResource(
                    $user->identityVerification
                );
            }
        }

        // ── Active restrictions ───────────────────────────────────────────────
        // Only present in the detail view (findById eager-loads activeRestrictions).
        // Allows the admin to see immediately if the reported user is already
        // under a messaging ban, suspension, etc.
        if ($user->relationLoaded('activeRestrictions')) {
            $block['active_restrictions'] = UserRestrictionSummaryResource::collection(
                $user->activeRestrictions
            );
        }

        return $block;
    }
}