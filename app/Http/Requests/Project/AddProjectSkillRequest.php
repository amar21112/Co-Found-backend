<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

class AddProjectSkillRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'skill_name'           => 'required|string|max:100',
            'proficiency_required' => 'required|integer|between:1,5',
            'positions_needed'     => 'nullable|integer|min:1',
            'is_required'          => 'nullable|boolean',
        ];
    }
}
