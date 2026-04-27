<?php

namespace App\Http\Requests\Report;

use App\DTOs\Report\UpdateReportDTO;
use Illuminate\Foundation\Http\FormRequest;

class UpdateReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'evidence' => ['sometimes', 'nullable', 'array', 'max:5'],
            'evidence.*' => ['string', 'url', 'max:500'],
        ];
    }

    public function getDto(): UpdateReportDTO
    {
        return new UpdateReportDTO(
            description: $this->validated('description'),
            evidence:    $this->validated('evidence'),
        );
    }
}
