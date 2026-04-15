<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

class ListApplicationsRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'status'   => 'nullable|string|in:pending,reviewing,accepted,rejected,withdrawn,expired',
            'role_id'  => 'nullable|uuid|exists:project_roles,id',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }
}
