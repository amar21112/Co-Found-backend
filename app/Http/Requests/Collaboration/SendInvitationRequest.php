<?php

namespace App\Http\Requests\Collaboration;

use Illuminate\Foundation\Http\FormRequest;

class SendInvitationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'recipient_id'    => ['required', 'string', 'uuid', 'exists:users,id'],
            'project_id'      => ['sometimes', 'nullable', 'string', 'uuid', 'exists:projects,id'],
            'invitation_type' => ['required', 'string',
                                  'in:project_join,team_invite,collaboration_request,mentorship,co_founder'],
            'role'            => ['sometimes', 'nullable', 'string', 'max:100'],
            'message'         => ['sometimes', 'nullable', 'string', 'max:1000'],
            'expires_at'      => ['sometimes', 'nullable', 'date', 'after:now'],
        ];
    }
}
