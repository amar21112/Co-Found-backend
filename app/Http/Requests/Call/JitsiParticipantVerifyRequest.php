<?php

namespace App\Http\Requests\Call;

use App\DTOs\Call\JitsiParticipantVerifyDTO;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the JSON body that mod_cofound_access sends on every MUC join.
 *
 * Body (application/json):
 *   room_name — Jitsi room name (e.g. "cofound-abc123")
 *   user_id   — UUID from session.jitsi_meet_context_user.id
 *   jti       — UUID from session.jitsi_meet_context_user.jti
 */
class JitsiParticipantVerifyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // auth handled by auth.jitsi middleware
    }

    public function rules(): array
    {
        return [
            'room_name' => ['required', 'string', 'max:255'],
            'user_id'   => ['required', 'string', 'uuid'],
            'jti'       => ['required', 'string', 'uuid'],
        ];
    }

    public function getDto(): JitsiParticipantVerifyDTO
    {
        $validated = $this->validated();

        return new JitsiParticipantVerifyDTO(
            roomName: strtolower(trim($validated['room_name'])),
            userId:   $validated['user_id'],
            jti:      $validated['jti'],
        );
    }
}
