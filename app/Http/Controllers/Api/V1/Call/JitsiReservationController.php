<?php

namespace App\Http\Controllers\Api\V1\Call;

use App\Http\Controllers\Controller;
use App\Http\Requests\Call\JitsiParticipantVerifyRequest;
use App\Http\Requests\Call\JitsiReservationRequest;
use App\Services\Call\JitsiReservationService;
use Illuminate\Http\JsonResponse;

/**
 * Implements the Jicofo Reservation System API contract.
 * Called exclusively by Prosody — protected by 'auth.jitsi' middleware.
 * Exceptions are handled globally by Handler.php.
 *
 * Ref: https://github.com/jitsi/jicofo/blob/master/doc/reservation.md
 */
class JitsiReservationController extends Controller
{
    public function __construct(
        private readonly JitsiReservationService $reservationService,
    ) {}

    /**
     * POST /jitsi/conference
     * Called by mod_reservations before creating a Jitsi room.
     * Handler maps CallReservationDeniedException → 403 (Prosody denies creation).
     */
    public function create(JitsiReservationRequest $request): JsonResponse
    {
        $result = $this->reservationService->approveConference($request->getDto());
        $call   = $result['call'];

        return response()->json([
            'id'            => $call->id,
            'name'          => $call->room_name,
            'mail_owner'    => $request->validated('mail_owner'),
            'start_time'    => ($call->start_time ?? $call->created_at)->toIso8601String(),
            'duration'      => $result['duration'],
            'max_occupants' => $result['maxOccupants'],
        ]);
    }

    /**
     * GET /jitsi/conference/{id}
     * Called by mod_reservations on 409 conflict.
     * Handler maps CallNotFoundException → 404.
     */
    public function show(string $id): JsonResponse
    {
        $result = $this->reservationService->fetchReservation($id);
        $call   = $result['call'];

        return response()->json([
            'id'            => $call->id,
            'name'          => $call->room_name,
            'mail_owner'    => '',
            'start_time'    => ($call->start_time ?? $call->created_at)->toIso8601String(),
            'duration'      => $result['duration'],
            'max_occupants' => $result['maxOccupants'],
        ]);
    }

    /**
     * DELETE /jitsi/conference/{id}
     * Called by mod_reservations when the room is destroyed.
     * Idempotent — no exception thrown if already ended.
     */
    public function destroy(string $id): JsonResponse
    {
        $this->reservationService->handleRoomDestroyed($id);

        return response()->json(['message' => 'OK']);
    }

    /**
     * POST /jitsi/participant/verify
     * Called by mod_cofound_access (custom Lua module) on every MUC join.
     * Checks the joining user's ID against the actual allowed member list.
     * Handler maps CallParticipantNotAllowedException → 403 (Prosody denies join).
     */
    public function verifyParticipant(JitsiParticipantVerifyRequest $request): JsonResponse
    {
        $this->reservationService->verifyParticipant($request->getDto());

        return response()->json(['allowed' => true]);
    }
}
