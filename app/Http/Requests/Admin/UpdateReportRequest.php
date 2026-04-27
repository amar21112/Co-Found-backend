<?php

namespace App\Http\Requests\Admin;

use App\DTOs\Admin\UpdateReportDTO;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Gated by AdminPolicy in controller
    }

    public function rules(): array
    {
        return [
            'status' => [
                'sometimes',
                'string',
                Rule::in(['pending', 'under_review', 'resolved', 'dismissed', 'escalated']),
            ],
            'priority' => [
                'sometimes',
                'string',
                Rule::in(['low', 'medium', 'high', 'critical']),
            ],
            'assigned_to' => [
                'sometimes',
                'nullable',
                'string',
                'uuid',
                'exists:users,id',
            ],
            'resolution_action' => [
                'sometimes',
                'nullable',
                'string',
                'max:500',
            ],
            'resolution_notes' => [
                'sometimes',
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    public function getDto(string $resolvedBy): UpdateReportDTO
    {
        $validated = $this->validated();

        return new UpdateReportDTO(
            status:           $validated['status']            ?? null,
            priority:         $validated['priority']          ?? null,
            assignedTo:       $validated['assigned_to']       ?? null,
            resolutionAction: $validated['resolution_action'] ?? null,
            resolutionNotes:  $validated['resolution_notes']  ?? null,
            resolvedBy:       isset($validated['resolution_action']) ? $resolvedBy : null,
        );
    }
}
