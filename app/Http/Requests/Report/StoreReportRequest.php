<?php

namespace App\Http\Requests\Report;

use App\DTOs\Report\StoreReportDTO;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reported_user_id' => ['required_without:reported_content_id', 'nullable', 'uuid', 'exists:users,id'],
            'reported_content_type' => ['required_with:reported_content_id', 'nullable', 'string', 'max:50'],
            'reported_content_id' => ['required_without:reported_user_id', 'nullable', 'uuid'],
            'report_type' => ['required', 'string', Rule::in(['harassment', 'spam', 'inappropriate', 'copyright', 'other'])],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'evidence' => ['sometimes', 'nullable', 'array', 'max:5'],
            'evidence.*' => ['string', 'url', 'max:500'], // Assuming URLs to uploaded screenshots/files
        ];
    }

    public function getDto(string $reporterId): StoreReportDTO
    {
        return new StoreReportDTO(
            reporterId:          $reporterId,
            reportedUserId:      $this->validated('reported_user_id'),
            reportedContentType: $this->validated('reported_content_type'),
            reportedContentId:   $this->validated('reported_content_id'),
            reportType:          $this->validated('report_type'),
            description:         $this->validated('description'),
            evidence:            $this->validated('evidence'),
        );
    }
}
