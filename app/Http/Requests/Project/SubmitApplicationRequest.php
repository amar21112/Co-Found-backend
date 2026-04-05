<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

class SubmitApplicationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'role_id'       => 'nullable|uuid|exists:project_roles,id',
            'proposed_role' => 'nullable|string|max:100|required_without:role_id',
            'cover_message' => 'nullable|string|max:5000',
            'availability'  => 'nullable|string|in:full_time,part_time,weekends,flexible',

            'skills'                      => 'nullable|array',
            'skills.*.skill_name'         => 'required|string|max:100',
            'skills.*.proficiency_claimed' => 'required|integer|between:1,5',
        ];
    }

    public function messages(): array
    {
        return [
            'proposed_role.required_without' => 'Either a formal role or a proposed role title is required.',
        ];
    }
}
