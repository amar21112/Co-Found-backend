<?php

namespace App\Http\Requests\Admin;

use App\DTOs\Admin\LogModerationActionDTO;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LogModerationActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Gated by AdminPolicy in controller
    }

    public function rules(): array
    {
        return [
            'content_type' => [
                'required',
                'string',
                Rule::in(['message', 'project', 'user_profile', 'portfolio_item', 'comment', 'other']),
            ],
            'content_id' => [
                'required',
                'string',
                'uuid',
            ],
            'moderation_type' => [
                'required',
                'string',
                Rule::in(['reported', 'auto_flagged', 'random_sampling', 'targeted']),
            ],
            'original_content' => [
                'sometimes',
                'nullable',
                'string',
                'max:5000',
            ],
            'moderated_content' => [
                'sometimes',
                'nullable',
                'string',
                'max:5000',
            ],
            'action_taken' => [
                'required',
                'string',
                Rule::in(['approved', 'edited', 'removed', 'quarantined', 'escalated']),
            ],
            'reason' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],
            'guideline_referenced' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    public function getDto(string $moderatorId): LogModerationActionDTO
    {
        $validated = $this->validated();

        return new LogModerationActionDTO(
            moderatorId: $moderatorId,
            contentType: $validated['content_type'],
            contentId: $validated['content_id'],
            moderationType: $validated['moderation_type'],
            originalContent: $validated['original_content'] ?? null,
            moderatedContent: $validated['moderated_content'] ?? null,
            actionTaken: $validated['action_taken'],
            reason: $validated['reason'],
            guidelineReferenced: $validated['guideline_referenced'] ?? null,
        );
    }
}
