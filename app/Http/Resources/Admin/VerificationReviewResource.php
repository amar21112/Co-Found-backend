<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VerificationReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                        => $this->id,
            'review_action'             => $this->review_action->value,
            'review_notes'              => $this->review_notes,
            'rejection_reason_category' => $this->rejection_reason_category?->value,
            'automated_checks_passed'   => $this->automated_checks_passed,
            'automated_checks_data'     => $this->automated_checks_data,
            'reviewed_at'               => $this->reviewed_at?->toISOString(),
            'reviewer'                  => $this->whenLoaded('reviewer', fn() => [
                'id'       => $this->reviewer->id,
                'username' => $this->reviewer->username,
                'role'     => $this->reviewer->role->value,
            ]),
        ];
    }
}
