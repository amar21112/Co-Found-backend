<?php

namespace App\Http\Requests\Collaboration;

use App\DTOs\Match\SubmitFeedbackDTO;
use App\Enums\FeedbackType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'feedback_type' => [
                'required',
                'string',
                Rule::in(array_column(FeedbackType::cases(), 'value')),
            ],
        ];
    }

    public function getDto(): SubmitFeedbackDTO
    {
        return new SubmitFeedbackDTO(
            feedbackType: FeedbackType::from($this->validated('feedback_type')),
        );
    }
}
