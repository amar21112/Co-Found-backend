<?php

namespace App\Http\Requests\ML;

use App\DTOs\Match\ExportDatasetDTO;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportDatasetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'format'             => ['sometimes', Rule::in(['csv', 'json'])],
            'type'               => ['sometimes', 'nullable', Rule::in(['collaborator', 'project'])],
            'min_score'          => ['sometimes', 'numeric', 'min:0', 'max:1'],
            'with_feedback_only' => ['sometimes', 'boolean'],
        ];
    }

    public function getDto(): ExportDatasetDTO
    {
        $v = $this->validated();

        return new ExportDatasetDTO(
            format:          $v['format']             ?? 'json',
            type:            $v['type']               ?? null,
            minScore:        (float) ($v['min_score'] ?? 0),
            withFeedbackOnly:(bool)  ($v['with_feedback_only'] ?? false),
        );
    }
}
