<?php

namespace App\Http\Requests\Call;

use App\DTOs\Call\InitiateCallDTO;
use App\Enums\CallStatus;
use App\Enums\CallType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class InitiateCallRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // call_type is no longer sent by the client — it is derived from
            // whichever context ID is present. Removed from input to avoid
            // any mismatch between what the client claims and what ID they send.

            'conversation_id' => [
                'nullable',
                'string',
                'max:255',
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

    /**
     * After field-level validation passes, enforce business rules:
     *   1. Exactly one context ID must be provided — a call must be anchored.
     *   2. Both IDs together are rejected — no mixed-context calls (see NOTES).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($v) {
            $hasConversation = ! empty($this->conversation_id);
            $hasProject      = ! empty($this->project_id);

            if (! $hasConversation && ! $hasProject) {
                $v->errors()->add(
                    'context',
                    'A call must be linked to either a conversation or a project.'
                );
            }

            if ($hasConversation && $hasProject) {
                $v->errors()->add(
                    'context',
                    'A call cannot be linked to both a conversation and a project. ' .
                    'Please initiate a separate call for each context.'
                );
            }
        });
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

        // Derive call_type from whichever context ID is present.
        // The client never sends call_type — this prevents any mismatch.
        $callType = isset($validated['project_id'])
            ? CallType::Project
            : CallType::Conversation;

        return new InitiateCallDTO(
            callType:       $callType,
            conversationId: $validated['conversation_id'] ?? null,
            projectId:      $validated['project_id']      ?? null,
            startTime:      $validated['start_time']      ?? null,
            status:         CallStatus::from($validated['status'] ?? CallStatus::Scheduled->value),
        );
    }
}
