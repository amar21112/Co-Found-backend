<?php

namespace App\Http\Requests\Call;

use App\DTOs\Call\InitiateCallDTO;
use App\Enums\CallStatus;
use App\Enums\CallType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InitiateCallRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'call_type' => [
                'required',
                'string',
                Rule::in(array_column(CallType::cases(), 'value')),
            ],
            'conversation_id' => [
                'nullable',
                'string',
                'uuid',
                'exists:conversations,id',
            ],
            'project_id' => [
                'nullable',
                'string',
                'uuid',
                'exists:projects,id',
            ],
            'start_time' => [
                'nullable',
                'date',
                'after_or_equal:now',
            ],
            'status' => [
                'sometimes',
                'string',
                Rule::in([CallStatus::Scheduled->value, CallStatus::Active->value]),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'start_time.after_or_equal' => 'Scheduled start time must be in the future.',
        ];
    }

    public function getDto(): InitiateCallDTO
    {
        $validated = $this->validated();

        return new InitiateCallDTO(
            callType:       CallType::from($validated['call_type']),
            conversationId: $validated['conversation_id'] ?? null,
            projectId:      $validated['project_id']      ?? null,
            startTime:      $validated['start_time']      ?? null,
            status:         CallStatus::from($validated['status'] ?? CallStatus::Scheduled->value),
        );
    }
}
