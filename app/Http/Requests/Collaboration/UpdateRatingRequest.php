<?php

namespace App\Http\Requests\Collaboration;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRatingRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'overall_rating'         => ['sometimes', 'integer', 'min:1', 'max:5'],
            'communication_rating'   => ['sometimes', 'nullable', 'integer', 'min:1', 'max:5'],
            'reliability_rating'     => ['sometimes', 'nullable', 'integer', 'min:1', 'max:5'],
            'skill_rating'           => ['sometimes', 'nullable', 'integer', 'min:1', 'max:5'],
            'problem_solving_rating' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:5'],
            'teamwork_rating'        => ['sometimes', 'nullable', 'integer', 'min:1', 'max:5'],
            'written_feedback'       => ['sometimes', 'nullable', 'string', 'max:2000'],
            'review_text'            => ['sometimes', 'nullable', 'string', 'max:2000'],
            'visibility'             => ['sometimes', 'string', 'in:public,private,anonymous'],
        ];
    }
}
