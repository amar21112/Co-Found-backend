<?php

namespace App\Http\Requests\ML;

use App\DTOs\Match\IngestMatchDTO;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IngestMatchesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'matches'                       => ['required', 'array', 'min:1', 'max:1000'],
            'matches.*.user_id'             => ['required', 'uuid', 'exists:users,id'],
            'matches.*.match_type'          => ['required', Rule::in(['collaborator', 'project'])],
            'matches.*.matched_user_id'     => ['nullable', 'uuid', 'exists:users,id'],
            'matches.*.matched_project_id'  => ['nullable', 'uuid', 'exists:projects,id'],
            'matches.*.compatibility_score' => ['required', 'numeric', 'min:0', 'max:1'],
            'matches.*.match_reasons'       => ['required', 'array'],
            'matches.*.expires_at'          => ['required', 'date', 'after:now'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            foreach ($this->input('matches', []) as $i => $match) {
                $type = $match['match_type'] ?? null;

                if ($type === 'collaborator' && empty($match['matched_user_id'])) {
                    $v->errors()->add("matches.$i.matched_user_id", 'Required for collaborator matches.');
                }

                if ($type === 'project' && empty($match['matched_project_id'])) {
                    $v->errors()->add("matches.$i.matched_project_id", 'Required for project matches.');
                }
            }
        });
    }

    /** @return IngestMatchDTO[] */
    public function getDto(): array
    {
        return collect($this->validated('matches'))
            ->map(fn(array $item) => new IngestMatchDTO(
                userId:             $item['user_id'],
                matchType:          $item['match_type'],
                compatibilityScore: $item['compatibility_score'],
                matchReasons:       $item['match_reasons'],
                expiresAt:          $item['expires_at'],
                matchedUserId:      $item['matched_user_id']    ?? null,
                matchedProjectId:   $item['matched_project_id'] ?? null,
            ))
            ->all();
    }
}
