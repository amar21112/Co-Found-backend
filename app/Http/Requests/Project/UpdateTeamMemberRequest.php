<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTeamMemberRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'role_id'     => 'sometimes|nullable|uuid|exists:project_roles,id',
            'position'    => 'sometimes|string|max:100',
            'permissions' => 'sometimes|string|in:owner,admin,member',
        ];
    }
}
