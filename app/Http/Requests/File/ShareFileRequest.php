<?php

namespace App\Http\Requests\File;

use Illuminate\Foundation\Http\FormRequest;

class ShareFileRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'file_id'          => 'required|uuid|exists:files,id',
            'message_id'       => 'nullable|uuid|exists:messages,id',
            'permission_level' => 'nullable|string|in:view,download,edit',
            'expires_at'       => 'nullable|date|after:now',
        ];
    }
}
