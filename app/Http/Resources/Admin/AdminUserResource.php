<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Full admin view of a single user.
 *
 * List view (paginate): includes identity_verification_summary only
 *   — a compact status block so moderators can triage without clicking
 *     into each user.
 *
 * Detail view (findById): includes full identity_verification with
 *   document images + review history, plus active_restrictions so the
 *   admin has everything on one screen.
 *
 * The distinction is driven purely by which relations are eager-loaded
 * in the repository — no flag or extra parameter needed in the resource.
 */
class AdminUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            // ── Core identity ─────────────────────────────────────────────────
            'id'                          => $this->id,
            'email'                       => $this->email,
            'username'                    => $this->username,
            'full_name'                   => $this->full_name,
            'profile_picture_url'         => $this->profile_picture_url,
            'bio'                         => $this->bio,
            'location'                    => $this->location,
            'website_url'                 => $this->website_url,
            'linkedin_url'                => $this->linkedin_url,
            'github_url'                  => $this->github_url,

            // ── Enum-backed fields ────────────────────────────────────────────
            'role'                        => $this->role->value,
            'account_status'              => $this->account_status->value,
            'identity_verification_level' => $this->identity_verification_level?->value,

            // ── Verification flags ────────────────────────────────────────────
            'email_verified'              => $this->email_verified,
            'identity_verified'           => $this->identity_verified,

            // ── Admin-only security fields ────────────────────────────────────
            'login_attempts'              => $this->login_attempts,
            'locked_until'                => $this->locked_until?->toISOString(),
            'last_login_at'               => $this->last_login_at?->toISOString(),
            'last_login_ip'               => $this->last_login_ip,
            'deleted_at'                  => $this->deleted_at?->toISOString(),

            'created_at'                  => $this->created_at?->toISOString(),
            'updated_at'                  => $this->updated_at?->toISOString(),

            // ── Identity verification ─────────────────────────────────────────
            //
            // Summary (list view): loaded when 'identityVerification' is
            //   eager-loaded WITHOUT nested relations.
            //
            // Full detail (show view): loaded when 'identityVerification.reviews'
            //   and 'identityVerification.latestReview' are eager-loaded.
            //   Exposes document images, personal data, and review history.
            //
            // If the relation is not loaded at all, this key is omitted.
            // If the user has never submitted verification, it is null.
            'identity_verification' => $this->whenLoaded(
                'identityVerification',
                function () {
                    if (! $this->identityVerification) {
                        return null;
                    }

                    // Detail view: reviews are eager-loaded
                    if ($this->identityVerification->relationLoaded('reviews')) {
                        return new IdentityVerificationDetailResource(
                            $this->identityVerification
                        );
                    }

                    // List / summary view
                    return new IdentityVerificationSummaryResource(
                        $this->identityVerification
                    );
                }
            ),

            // ── Active restrictions ───────────────────────────────────────────
            // Only present in the detail view (findById eager-loads this).
            // Gives the admin an instant picture of any current bans/suspensions
            // without navigating to a separate restrictions endpoint.
            'active_restrictions' => $this->whenLoaded(
                'activeRestrictions',
                fn() => UserRestrictionSummaryResource::collection(
                    $this->activeRestrictions
                )
            ),

            // ── Stats (when loaded) ───────────────────────────────────────────
            'reports_received_count' => $this->whenLoaded(
                'reportsReceived',
                fn() => $this->reportsReceived->count()
            ),
        ];
    }
}
