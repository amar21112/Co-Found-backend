<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class CreateConversationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'conversation_type' => 'required|string|in:direct,group,project',
            'title'             => 'nullable|string|max:255|required_if:conversation_type,group',
            'project_id'        => 'nullable|uuid|exists:projects,id|required_if:conversation_type,project',
            'participant_ids'   => 'required|array|min:1',
            'participant_ids.*' => 'uuid|exists:users,id|different:participant_ids.0',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required_if'      => 'A title is required for group conversations.',
            'project_id.required_if' => 'A project ID is required for project conversations.',
        ];
    }
}
