<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Compact active-restriction summary for embedding inside
 * AdminUserResource and ReportResource.
 *
 * Admins reviewing a report or user profile need to know immediately
 * whether the user is already under a restriction without navigating
 * to a separate restrictions endpoint.
 */
class UserRestrictionSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'restriction_type' => $this->restriction_type->value,
            'reason'           => $this->reason,
            'is_permanent'     => $this->isPermanent(),
            'starts_at'        => $this->starts_at?->toISOString(),
            'expires_at'       => $this->expires_at?->toISOString(),
            'restricted_by'    => $this->whenLoaded('restrictedBy', fn() =>
                $this->restrictedBy ? [
                    'id'       => $this->restrictedBy->id,
                    'username' => $this->restrictedBy->username,
                ] : null
            ),
        ];
    }
}
