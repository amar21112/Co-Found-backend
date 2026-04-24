<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'content'               => 'required|string|max:10000',
            'message_type'          => 'nullable|string|in:text,file,poll',
            'replied_to_message_id' => 'nullable|uuid|exists:messages,id',
            'formatted_content'     => 'nullable|array',
        ];
    }
}
