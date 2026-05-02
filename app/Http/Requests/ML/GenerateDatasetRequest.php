<?php

namespace App\Http\Requests\ML;

use App\DTOs\Match\GenerateDatasetDTO;
use Illuminate\Foundation\Http\FormRequest;

class GenerateDatasetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'users'              => ['sometimes', 'integer', 'min:10',  'max:500'],
            'projects'           => ['sometimes', 'integer', 'min:5',   'max:200'],
            'collaborator_pairs' => ['sometimes', 'integer', 'min:10',  'max:5000'],
            'project_pairs'      => ['sometimes', 'integer', 'min:10',  'max:5000'],
            'fresh'              => ['sometimes', 'boolean'],
        ];
    }

    public function getDto(): GenerateDatasetDTO
    {
        $v = $this->validated();

        return new GenerateDatasetDTO(
            users:             (int)  ($v['users']              ?? 100),
            projects:          (int)  ($v['projects']           ?? 40),
            collaboratorPairs: (int)  ($v['collaborator_pairs'] ?? 400),
            projectPairs:      (int)  ($v['project_pairs']      ?? 300),
            fresh:             (bool) ($v['fresh']              ?? false),
        );
    }
}
