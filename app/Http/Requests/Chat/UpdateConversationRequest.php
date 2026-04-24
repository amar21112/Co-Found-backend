<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConversationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title'       => 'sometimes|string|max:255',
            'muted'       => 'sometimes|boolean',
            'muted_until' => 'sometimes|nullable|date|after:now',
        ];
    }
}
