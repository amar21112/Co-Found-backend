<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMilestoneRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title'          => 'sometimes|string|max:255',
            'description'    => 'sometimes|nullable|string',
            'due_date'       => 'sometimes|nullable|date',
            'completed_date' => 'sometimes|nullable|date',
            'order_index'    => 'sometimes|integer|min:0',
            'status'         => 'sometimes|string|in:pending,in_progress,completed,delayed',
        ];
    }
}
