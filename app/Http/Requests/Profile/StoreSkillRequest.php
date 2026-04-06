<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class StoreSkillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'skill_name'       => ['required', 'string', 'max:100'],
            'proficiency_level'=> ['required', 'integer', 'min:1', 'max:5'],
            'years_experience' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:50'],
        ];
    }
}
