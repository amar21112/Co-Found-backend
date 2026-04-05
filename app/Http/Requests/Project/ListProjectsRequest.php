<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

class ListProjectsRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'status'                  => 'nullable|string|in:planning,active,on_hold,completed,cancelled',
            'category'                => 'nullable|string|max:100',
            'skill'                   => 'nullable|string|max:100',
            'search'                  => 'nullable|string|max:255',
            'accepting_applications'  => 'nullable|boolean',
            'sort'                    => 'nullable|string|in:created_at,view_count,application_count',
            'per_page'                => 'nullable|integer|min:1|max:100',
        ];
    }
}
