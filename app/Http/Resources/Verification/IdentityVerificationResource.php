<?php

namespace App\Http\Resources\Verification;

use App\Enums\IdentityVerificationStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IdentityVerificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'verification_status'   => $this->verification_status->value,
            'status_label'          => $this->verification_status->label(),
            'submission_method'     => $this->submission_method,

            // Personal data — returned so user can confirm what was submitted
            'full_name_on_card'     => $this->full_name_on_card,
            'date_of_birth'         => $this->date_of_birth?->toDateString(),
            'nationality'           => $this->nationality,
            'expiry_date'           => $this->expiry_date?->toDateString(),

            // Liveness result — set by ML processing after submission
            'liveness_check_passed' => $this->liveness_check_passed,
            'face_match_score'      => $this->face_match_score,

            // Rejection reason — only shown when rejected
            'rejection_reason' => $this->verification_status === IdentityVerificationStatus::Rejected
                ? $this->rejection_reason
                : null,

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Note: id_card_image_front/back paths are never exposed to the client.
            // id_card_number is stored encrypted and never returned.
        ];
    }
}
