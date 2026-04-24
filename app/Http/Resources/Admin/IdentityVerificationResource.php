<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IdentityVerificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'verification_status'   => $this->verification_status->value,
            'submission_method'     => $this->submission_method,
            'full_name_on_card'     => $this->full_name_on_card,
            'date_of_birth'         => $this->date_of_birth?->toDateString(),
            'nationality'           => $this->nationality,
            'expiry_date'           => $this->expiry_date?->toDateString(),
            'liveness_check_passed' => $this->liveness_check_passed,
            'face_match_score'      => $this->face_match_score,
            'rejection_reason'      => $this->rejection_reason,
            'ip_address'            => $this->ip_address,
            'created_at'            => $this->created_at?->toISOString(),
            'updated_at'            => $this->updated_at?->toISOString(),

            // Document image URLs — included in detail view
            'id_card_image_front'   => $this->when(
                $request->routeIs('admin.verifications.show'),
                $this->id_card_image_front
            ),
            'id_card_image_back'    => $this->when(
                $request->routeIs('admin.verifications.show'),
                $this->id_card_image_back
            ),

            // Relations
            'user'    => new UserResource($this->whenLoaded('user')),
            'reviews' => VerificationReviewResource::collection($this->whenLoaded('reviews')),

            // Latest review only — for list view efficiency
            'latest_review' => $this->whenLoaded(
                'latestReview',
                fn() => $this->latestReview->first()
                    ? new VerificationReviewResource($this->latestReview->first())
                    : null
            ),
        ];
    }
}
