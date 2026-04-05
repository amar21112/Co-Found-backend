<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

class CreateProjectRoleRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'role_name'        => 'required|string|max:100',
            'description'      => 'nullable|string',
            'positions_needed' => 'nullable|integer|min:1',
        ];
    }
}
