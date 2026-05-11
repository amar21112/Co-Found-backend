<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Full identity verification detail for admin show views.
 *
 * Used inside:
 *   - AdminUserResource (GET /admin/users/{id})
 *   - ReportResource    (GET /admin/reports/{id})
 *
 * Includes document images, all personal data, and full review history
 * so the admin has everything needed to act without switching screens.
 */
class IdentityVerificationDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'verification_status'   => $this->verification_status->value,
            'submission_method'     => $this->submission_method,

            // Personal document data
            'full_name_on_card'     => $this->full_name_on_card,
            'date_of_birth'         => $this->date_of_birth?->toDateString(),
            'nationality'           => $this->nationality,
            'id_card_number'        => $this->id_card_number,
            'expiry_date'           => $this->expiry_date?->toDateString(),

            // Document images — only exposed in detail (show) views
            'id_card_image_front'   => $this->id_card_image_front,
            'id_card_image_back'    => $this->id_card_image_back,

            // Liveness / biometric checks
            'liveness_check_passed' => $this->liveness_check_passed,
            // Re-encode as a JSON string — model casts to array but callers
            // expect a raw JSON string so they can parse it however they need.
            'liveness_check_data'   => $this->liveness_check_data !== null
                ? json_encode($this->liveness_check_data)
                : null,
            'face_match_score'      => $this->face_match_score,

            // Decision
            'rejection_reason'      => $this->rejection_reason,

            // Submission metadata
            'ip_address'            => $this->ip_address,
            'user_agent'            => $this->user_agent,
            // Same as liveness_check_data — expose as raw JSON string.
            'device_info'           => $this->device_info !== null
                ? json_encode($this->device_info)
                : null,

            'submitted_at'          => $this->created_at?->toISOString(),
            'updated_at'            => $this->updated_at?->toISOString(),

            // Review history — loaded when reviews relation is eager-loaded
            'reviews' => VerificationReviewResource::collection(
                $this->whenLoaded('reviews')
            ),

            // Latest review shortcut — latestReview is a HasMany limited to 1,
            // so the eager-loaded value is a Collection; call ->first() on it.
            'latest_review' => $this->whenLoaded('latestReview', function () {
                $review = $this->latestReview instanceof \Illuminate\Database\Eloquent\Collection
                    ? $this->latestReview->first()
                    : $this->latestReview; // fallback if resolved differently

                return $review ? new VerificationReviewResource($review) : null;
            }),
        ];
    }
}
