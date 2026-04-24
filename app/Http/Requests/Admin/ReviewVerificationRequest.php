<?php

namespace App\Http\Requests\Admin;

use App\DTOs\Admin\ReviewVerificationDTO;
use App\Enums\RejectionReasonCategory;
use App\Enums\ReviewAction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Gated by AdminPolicy in controller
    }

    public function rules(): array
    {
        $action = $this->input('review_action');

        return [
            'review_action' => [
                'required',
                'string',
                Rule::in(array_column(ReviewAction::cases(), 'value')),
            ],
            'review_notes' => [
                'nullable', 'string', 'max:2000',
            ],
            'rejection_reason_category' => [
                Rule::requiredIf($action === ReviewAction::Rejected->value),
                'nullable',
                'string',
                Rule::in(array_column(RejectionReasonCategory::cases(), 'value')),
            ],
            'automated_checks_passed' => ['sometimes', 'boolean'],
            'automated_checks_data'   => ['sometimes', 'nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'rejection_reason_category.required' => 'A rejection reason category is required when rejecting a verification.',
        ];
    }

    public function getDto(): ReviewVerificationDTO
    {
        $validated = $this->validated();

        return new ReviewVerificationDTO(
            reviewAction:             ReviewAction::from($validated['review_action']),
            reviewNotes:              $validated['review_notes'] ?? null,
            rejectionReasonCategory:  isset($validated['rejection_reason_category'])
                ? RejectionReasonCategory::from($validated['rejection_reason_category'])
                : null,
            automatedChecksPassed:    $validated['automated_checks_passed'] ?? true,
            automatedChecksData:      $validated['automated_checks_data'] ?? null,
        );
    }
}
