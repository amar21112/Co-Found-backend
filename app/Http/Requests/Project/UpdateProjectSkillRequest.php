<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectSkillRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'proficiency_required' => 'sometimes|integer|between:1,5',
            'positions_needed'     => 'sometimes|integer|min:1',
            'is_required'          => 'sometimes|boolean',
        ];
    }
}
