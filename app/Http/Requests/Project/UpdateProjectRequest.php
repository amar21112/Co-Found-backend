<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title'                    => 'sometimes|string|max:255',
            'short_description'        => 'sometimes|string|max:500',
            'full_description'         => 'sometimes|string',
            'category'                 => 'sometimes|string|max:100',
            'status'                   => 'sometimes|string|in:planning,active,on_hold,completed,cancelled',
            'visibility'               => 'sometimes|string|in:public,private,unlisted',
            'team_size_min'            => 'sometimes|nullable|integer|min:1',
            'team_size_max'            => 'sometimes|nullable|integer|min:1|gte:team_size_min',
            'start_date'               => 'sometimes|nullable|date',
            'target_completion_date'   => 'sometimes|nullable|date',
            'actual_completion_date'   => 'sometimes|nullable|date',
            'application_deadline'     => 'sometimes|nullable|date',
            'is_accepting_applications'=> 'sometimes|boolean',
        ];
    }
}
