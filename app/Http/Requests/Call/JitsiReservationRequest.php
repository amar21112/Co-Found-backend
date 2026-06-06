<?php

namespace App\Http\Requests\Call;

use App\DTOs\Call\JitsiReservationDTO;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the form-encoded body that Prosody's mod_reservations sends
 * when a new Jitsi room is about to be created.
 *
 * mod_reservations spec:
 *   POST body (application/x-www-form-urlencoded):
 *     name       — room name, e.g. "cofound-abc123"
 *     start_time — ISO 8601 datetime string
 *     mail_owner — JID of the occupant creating the room
 *
 * Authentication is handled upstream by the 'auth.jitsi' middleware.
 * This request class only validates the payload shape.
 */
class JitsiReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:255'],
            'start_time' => ['required', 'string'],
            'mail_owner' => ['required', 'string'],
        ];
    }

    public function getDto(): JitsiReservationDTO
    {
        $validated = $this->validated();

        return new JitsiReservationDTO(
            roomName:  strtolower(trim($validated['name'])),
            startTime: $validated['start_time'],
            mailOwner: $validated['mail_owner'],
        );
    }
}
