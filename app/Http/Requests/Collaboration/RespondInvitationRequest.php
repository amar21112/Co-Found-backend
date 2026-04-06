<?php

namespace App\Http\Requests\Collaboration;

use Illuminate\Foundation\Http\FormRequest;

class RespondInvitationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'action'           => ['required', 'string', 'in:accepted,declined'],
            'response_message' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }
}
