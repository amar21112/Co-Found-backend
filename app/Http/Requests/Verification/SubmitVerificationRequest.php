<?php

namespace App\Http\Requests\Verification;

use App\DTOs\Verification\SubmitVerificationDTO;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the identity verification submission.
 *
 * The frontend sends only the two card images and the submission_method.
 * This maps directly to SubmitVerificationDTO — no card fields.
 *
 * Card fields (NID, name, date of birth, nationality, expiry) are
 * extracted server-side by OcrEnricher and live in EnrichedVerificationDTO.
 */
class SubmitVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_card_image_front' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'id_card_image_back'  => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'submission_method'   => ['required', 'string', 'in:mobile_capture,webcam'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_card_image_front.required' => 'Front image of your ID document is required.',
            'id_card_image_back.required'  => 'Back image of your ID document is required.',
            'id_card_image_front.max'      => 'Front image must not exceed 10MB.',
            'id_card_image_back.max'       => 'Back image must not exceed 10MB.',
        ];
    }

    public function getDto(): SubmitVerificationDTO
    {
        return new SubmitVerificationDTO(
            frontImage:       $this->file('id_card_image_front'),
            backImage:        $this->file('id_card_image_back'),
            submissionMethod: $this->validated()['submission_method'],
            ipAddress:        $this->ip(),
        );
    }
}
