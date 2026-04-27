<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Compact identity verification summary for embedding inside
 * AdminUserResource (list view) and ReportResource (list view).
 *
 * Shows only what a moderator needs at a glance — full detail is
 * available via IdentityVerificationDetailResource used in show views.
 */
class IdentityVerificationSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'verification_status' => $this->verification_status->value,
            'submission_method'   => $this->submission_method,
            'liveness_check_passed' => $this->liveness_check_passed,
            'face_match_score'    => $this->face_match_score,
            'rejection_reason'    => $this->rejection_reason,
            'submitted_at'        => $this->created_at?->toISOString(),
            'updated_at'          => $this->updated_at?->toISOString(),
        ];
    }
}
