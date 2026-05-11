<?php

namespace App\Http\Requests\Project;

use App\Enums\RestrictionType;
use Illuminate\Foundation\Http\FormRequest;

class CreateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        // auth:sanctum, no.guest, and verified middleware already enforce
        // authentication, guest exclusion, and email verification before
        // this request is resolved. Only the posting_ban restriction is
        // a request-level concern.
        return !$this->user()->activeRestrictions()
            ->where('restriction_type', RestrictionType::PostingBan->value)
            ->exists();
    }

    public function rules(): array
    {
        return [
            'title'                    => 'required|string|max:255',
            'short_description'        => 'required|string|max:500',
            'full_description'         => 'required|string',
            'category'                 => 'required|string|max:100',
            'status'                   => 'nullable|string|in:planning,active',
            'visibility'               => 'nullable|string|in:public,private,unlisted',
            'team_size_min'            => 'nullable|integer|min:1',
            'team_size_max'            => 'nullable|integer|min:1|gte:team_size_min',
            'start_date'               => 'nullable|date',
            'target_completion_date'   => 'nullable|date|after_or_equal:start_date',
            'application_deadline'     => 'nullable|date',
            'is_accepting_applications'=> 'nullable|boolean',

            'skills'                      => 'nullable|array',
            'skills.*.skill_name'         => 'required|string|max:100',
            'skills.*.proficiency_required'=> 'required|integer|between:1,5',
            'skills.*.positions_needed'   => 'nullable|integer|min:1',
            'skills.*.is_required'        => 'nullable|boolean',

            'roles'                      => 'nullable|array',
            'roles.*.role_name'          => 'required|string|max:100',
            'roles.*.description'        => 'nullable|string',
            'roles.*.positions_needed'   => 'nullable|integer|min:1',
        ];
    }
}
