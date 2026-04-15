<?php

namespace App\Http\Requests\Collaboration;

use Illuminate\Foundation\Http\FormRequest;

class SendConnectionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'recipient_id'    => ['required', 'string', 'uuid', 'exists:users,id'],
            'connection_type' => ['sometimes', 'nullable', 'string',
                                  'in:collaborator,mentor,mentee,friend'],
        ];
    }
}
