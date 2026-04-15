<?php

namespace App\Http\Requests\Collaboration;

use Illuminate\Foundation\Http\FormRequest;

class StoreRatingRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'rated_user_id'          => ['required', 'string', 'uuid', 'exists:users,id'],
            'project_id'             => ['sometimes', 'nullable', 'string', 'uuid', 'exists:projects,id'],
            'communication_rating'   => ['sometimes', 'nullable', 'integer', 'min:1', 'max:5'],
            'reliability_rating'     => ['sometimes', 'nullable', 'integer', 'min:1', 'max:5'],
            'skill_rating'           => ['sometimes', 'nullable', 'integer', 'min:1', 'max:5'],
            'problem_solving_rating' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:5'],
            'teamwork_rating'        => ['sometimes', 'nullable', 'integer', 'min:1', 'max:5'],
            'written_feedback'       => ['sometimes', 'nullable', 'string', 'max:2000'],
            'visibility'             => ['sometimes', 'string', 'in:public,private,anonymous'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $ratingFields = [
                'communication_rating', 'reliability_rating', 'skill_rating',
                'problem_solving_rating', 'teamwork_rating',
            ];

            $provided = collect($ratingFields)
                ->filter(fn($f) => filled($this->input($f)))
                ->count();

            if ($provided === 0 && empty($this->input('written_feedback'))) {
                $validator->errors()->add(
                    'ratings',
                    'At least one rating score or written feedback must be provided.'
                );
            }
        });
    }
}
