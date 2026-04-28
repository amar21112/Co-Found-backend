<?php

namespace App\Http\Requests\Verification;

use App\DTOs\Verification\SubmitVerificationDTO;
use Illuminate\Foundation\Http\FormRequest;

class SubmitVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_card_image_front' => [
                'required', 'file', 'image',
                'mimes:jpg,jpeg,png,webp', 'max:10240',
            ],
            'id_card_image_back' => [
                'required', 'file', 'image',
                'mimes:jpg,jpeg,png,webp', 'max:10240',
            ],
            'id_card_number'      => ['nullable', 'string', 'max:50'],
            'full_name_on_card'   => ['required', 'string', 'max:255'],
            'date_of_birth'       => ['required', 'date', 'before:today'],
            'nationality'         => ['nullable', 'string', 'max:100'],
            'expiry_date'         => ['nullable', 'date'],
            'submission_method'   => [
                'required', 'string', 'in:mobile_capture,webcam',
            ],
            'liveness_check_data' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_card_image_front.required' => 'Front image of your ID document is required.',
            'id_card_image_back.required'  => 'Back image of your ID document is required.',
            'id_card_image_front.max'      => 'Front image must not exceed 10MB.',
            'id_card_image_back.max'       => 'Back image must not exceed 10MB.',
            'date_of_birth.before'         => 'Date of birth must be in the past.',
        ];
    }

    public function getDto(): SubmitVerificationDTO
    {
        $validated = $this->validated();

        return new SubmitVerificationDTO(
            frontImage:        $this->file('id_card_image_front'),
            backImage:         $this->file('id_card_image_back'),
            idCardNumber:      $validated['id_card_number']    ?? null,
            fullNameOnCard:    $validated['full_name_on_card'],
            dateOfBirth:       $validated['date_of_birth'],
            nationality:       $validated['nationality']       ?? null,
            expiryDate:        $validated['expiry_date']       ?? null,
            submissionMethod:  $validated['submission_method'],
            livenessCheckData: isset($validated['liveness_check_data'])
                ? json_decode($validated['liveness_check_data'], true)
                : null,
            ipAddress:         $this->ip(),
        );
    }
}
