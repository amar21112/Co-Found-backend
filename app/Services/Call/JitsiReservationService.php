<?php

namespace App\Services\Call;

use App\DTOs\Call\JitsiParticipantVerifyDTO;
use App\DTOs\Call\JitsiReservationDTO;
use App\Exceptions\Call\CallNotFoundException;
use App\Exceptions\Call\CallParticipantNotAllowedException;
use App\Exceptions\Call\CallReservationDeniedException;
use App\Firebase\FirebaseService;
use App\Models\ProjectTeamMember;
use App\Models\VideoCall;
use App\Repositories\Contracts\VideoCallRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Exception\DatabaseException;

class JitsiReservationService
{
    public function __construct(
        private readonly VideoCallRepositoryInterface $callRepo,
        private readonly FirebaseService             $firebaseService,
    ) {}

    // =========================================================================
    // Conference creation — POST /jitsi/conference
    // =========================================================================

    /**
     * @throws CallReservationDeniedException → Handler maps to 403
     * @throws DatabaseException
     * @return array{call: VideoCall, maxOccupants: int, duration: int}
     */
    public function approveConference(JitsiReservationDTO $dto): array
    {
        $call = $this->callRepo->findActiveByRoomName($dto->roomName);

        if (! $call) {
            Log::warning('JitsiReservation: room creation denied — no active call', [
                'room_name' => $dto->roomName,
            ]);
            throw new CallReservationDeniedException();
        }

        $maxOccupants = $this->resolveMaxOccupants($call);
        $duration     = config('jitsi.room_duration', 14400);

        Log::info('JitsiReservation: room creation approved', [
            'room_name'     => $dto->roomName,
            'call_id'       => $call->id,
            'max_occupants' => $maxOccupants,
        ]);

        return [
            'call'         => $call,
            'maxOccupants' => $maxOccupants,
            'duration'     => $duration,
        ];
    }

    // =========================================================================
    // Existing reservation fetch — GET /jitsi/conference/{id}
    // =========================================================================

    /**
     * @return array{call: VideoCall, maxOccupants: int, duration: int}
     * @throws DatabaseException
     * @throws CallNotFoundException → Handler maps to 404
     */
    public function fetchReservation(string $callId): array
    {
        $call = $this->callRepo->findById($callId);

        if (! $call || $call->isTerminal()) {
            throw new CallNotFoundException();
        }

        return [
            'call'         => $call,
            'maxOccupants' => $this->resolveMaxOccupants($call),
            'duration'     => config('jitsi.room_duration', 14400),
        ];
    }

    // =========================================================================
    // Per-join participant verification — POST /jitsi/participant/verify
    // =========================================================================

    /**
     * Verify the joining user's identity and token session on every MUC join.
     *
     * Two checks run in order:
     *
     * 1. MEMBER CHECK
     *    conversation_id → Firebase conversations/{id}/participants array
     *    project_id      → MySQL ProjectTeamMember (is_active = true)
     *
     * 2. JTI CHECK (only if participant row is already active)
     *    Presented jti must match active_token_jti on the DB row.
     *    MATCH    → legitimate reconnect → allow
     *    MISMATCH → stale or shared token → block
     *
     * Token rotation closes the sharing window:
     *   Person A's frontend calls /join every 25s → new jti stored on their row.
     *   Person B (holding the old jti) is blocked on their next Prosody join.
     *   Person B cannot call /join (no backend auth session of their own).
     *
     * @throws CallReservationDeniedException    → 403
     * @throws CallParticipantNotAllowedException → 403
     * @throws DatabaseException
     */
    public function verifyParticipant(JitsiParticipantVerifyDTO $dto): void
    {
        $call = $this->callRepo->findActiveByRoomName($dto->roomName);

        if (! $call) {
            Log::warning('JitsiReservation: verify — no active call', [
                'room_name' => $dto->roomName,
                'user_id'   => $dto->userId,
            ]);
            throw new CallReservationDeniedException();
        }

        // ── 1. Member check ───────────────────────────────────────────────────
        if (! $this->isAllowedMember($call, $dto->userId)) {
            Log::warning('JitsiReservation: blocked — user not in member list', [
                'room_name' => $dto->roomName,
                'call_id'   => $call->id,
                'user_id'   => $dto->userId,
            ]);
            throw new CallParticipantNotAllowedException();
        }

        // ── 2. JTI check (only for already-active participants) ───────────────
        $participant = $this->callRepo->findParticipant($call, $dto->userId);

        if ($participant && $participant->isActiveInCall()) {
            if ($participant->active_token_jti !== $dto->jti) {
                Log::warning('JitsiReservation: blocked — jti mismatch', [
                    'room_name'     => $dto->roomName,
                    'call_id'       => $call->id,
                    'user_id'       => $dto->userId,
                    'expected_jti'  => $participant->active_token_jti,
                    'presented_jti' => $dto->jti,
                ]);
                throw new CallParticipantNotAllowedException();
            }
        }

        Log::info('JitsiReservation: participant verified', [
            'room_name' => $dto->roomName,
            'call_id'   => $call->id,
            'user_id'   => $dto->userId,
        ]);
    }

    // =========================================================================
    // Room destruction — DELETE /jitsi/conference/{id}
    // =========================================================================

    /**
     * End the call when Prosody destroys the room. Idempotent.
     */
    public function handleRoomDestroyed(string $callId): void
    {
        $call = $this->callRepo->findById($callId);

        if (! $call || $call->isTerminal()) {
            return;
        }

        $this->callRepo->endCall($call);

        Log::info('JitsiReservation: call ended via room destruction', [
            'call_id' => $callId,
        ]);
    }

    // =========================================================================
    // Private — member check
    // =========================================================================

    /**
     * Check whether the given user is allowed to be in this call.
     *
     * conversation_id → reads conversations/{id}/participants from Firebase.
     *                   The participants array contains UUIDs of active members,
     *                   maintained by the chat sync layer.
     *
     * project_id      → checks MySQL ProjectTeamMember directly.
     *                   Projects live entirely in MySQL — no Firebase needed.
     *
     * Returns false on any Firebase error (fail closed — deny if unsure).
     * @throws DatabaseException
     */
    private function isAllowedMember(VideoCall $call, string $userId): bool
    {
        if ($call->conversation_id) {
            return $this->firebaseService->isConversationParticipant(
                $call->conversation_id,
                $userId
            );
        }

        if ($call->project_id) {
            return ProjectTeamMember::where('project_id', $call->project_id)
                ->where('user_id', $userId)
                ->where('is_active', true)
                ->exists();
        }

        return false;
    }

    // =========================================================================
    // Private — capacity resolution for mod_muc_max_occupants
    // =========================================================================

    /**
     * Resolve the hard participant cap to pass to mod_muc_max_occupants.
     *
     * private conversation → 2 real users + 2 internal buffer
     * other conversation   → participant count from Firebase + 2 buffer
     * project call         → active team member count from MySQL + 2 buffer
     *
     * +2 buffers for Jicofo (focus) and JVB occupants, which are excluded
     * via muc_access_whitelist in Prosody but the buffer prevents edge cases.
     *
     * Falls back to 4 (2 real + 2 buffer) on any Firebase error.
     * @throws DatabaseException
     */
    private function resolveMaxOccupants(VideoCall $call): int
    {
        return $this->resolveActualLimit($call) + 2;
    }

    /**
     * @throws DatabaseException
     */
    private function resolveActualLimit(VideoCall $call): int
    {
        if ($call->conversation_id) {
            if ($this->firebaseService->isPrivateConversation($call->conversation_id)) {
                return 2;
            }

            return max(2, $this->firebaseService->conversationParticipantCount(
                $call->conversation_id
            ));
        }

        if ($call->project_id) {
            $count = ProjectTeamMember::where('project_id', $call->project_id)
                ->where('is_active', true)
                ->count();

            return max(2, $count);
        }

        return 2;
    }
}
