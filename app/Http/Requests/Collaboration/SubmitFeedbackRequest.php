<?php

namespace App\Http\Requests\Collaboration;

use Illuminate\Foundation\Http\FormRequest;

class SubmitFeedbackRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'feedback_type' => ['required', 'string',
                                'in:relevant,not_relevant,already_connected,not_interested'],
        ];
    }
}
