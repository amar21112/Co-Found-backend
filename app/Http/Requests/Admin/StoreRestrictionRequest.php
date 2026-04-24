<?php

namespace App\Http\Requests\Admin;

use App\DTOs\Admin\StoreRestrictionDTO;
use App\Enums\RestrictionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRestrictionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Gated by AdminPolicy in controller
    }

    public function rules(): array
    {
        return [
            'user_id' => [
                'required', 'string', 'uuid', 'exists:users,id',
            ],
            'restriction_type' => [
                'required',
                'string',
                Rule::in(array_column(RestrictionType::cases(), 'value')),
            ],
            'reason' => [
                'required', 'string', 'min:10', 'max:1000',
            ],
            'duration_hours' => [
                'sometimes', 'nullable', 'integer', 'min:1', 'max:8760',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.min' => 'Please provide a detailed reason (at least 10 characters).',
        ];
    }

    public function getDto(): StoreRestrictionDTO
    {
        $validated = $this->validated();

        return new StoreRestrictionDTO(
            userId:          $validated['user_id'],
            restrictionType: RestrictionType::from($validated['restriction_type']),
            reason:          $validated['reason'],
            durationHours:   $validated['duration_hours'] ?? null,
        );
    }
}
