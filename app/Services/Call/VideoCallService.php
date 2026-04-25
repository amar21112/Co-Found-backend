<?php

namespace App\Services\Call;

use App\DTOs\Call\InitiateCallDTO;
use App\Enums\CallParticipantRole;
use App\Enums\CallStatus;
use App\Exceptions\Call\CallAlreadyEndedException;
use App\Exceptions\Call\CallNotFoundException;
use App\Exceptions\Call\CallNotJoinableException;
use App\Exceptions\Call\NotACallParticipantException;
use App\Exceptions\Call\NotCallHostException;
use App\Models\ConversationParticipant;
use App\Models\ProjectTeamMember;
use App\Models\User;
use App\Models\VideoCall;
use App\Repositories\Contracts\ConversationRepositoryInterface;
use App\Repositories\Contracts\ProjectTeamRepositoryInterface;
use App\Repositories\Contracts\VideoCallRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class VideoCallService
{
    /**
     * Base URL for Jitsi Meet rooms.
     * No SDK or API key required — Jitsi Meet is open and free.
     * The backend controls who receives the URL via access checks;
     * the random room name is a second layer of defense.
     */
    private const ROOM_BASE_URL = 'https://meet.jit.si/cofound-';

    public function __construct(
        private readonly VideoCallRepositoryInterface $callRepo,
        private readonly ProjectTeamRepositoryInterface $teamRepo,
        private readonly ConversationRepositoryInterface $conversationRepo,
    ) {}

    // =========================================================================
    // List (user's own calls)
    // =========================================================================

    public function listForUser(User $user, array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->callRepo->paginateForUser($user->id, $filters, $perPage);
    }

    // =========================================================================
    // Initiate
    // =========================================================================

    /**
     * Create a new call and add the initiator as host participant.
     *
     * Access model:
     * - project calls  → project team members only
     * - conversation calls → conversation participants only
     * - ad-hoc calls   → anyone with the call ID (no context restriction)
     */
    public function initiate(User $initiator, InitiateCallDTO $dto): VideoCall
    {
        $roomName = $this->generateRoomName();
        $roomUrl  = self::ROOM_BASE_URL . $roomName;

        $call = $this->callRepo->create($initiator, $dto, $roomName, $roomUrl);

        $this->callRepo->addParticipant(
            $call,
            $initiator,
            CallParticipantRole::Host->value
        );

        return $call->load(['initiator', 'participants.user']);
    }

    // =========================================================================
    // Show
    // =========================================================================

    public function show(string $id): VideoCall
    {
        $call = $this->callRepo->findById($id);

        if (! $call) {
            throw new CallNotFoundException();
        }

        return $call;
    }

    // =========================================================================
    // Join
    // =========================================================================

    /**
     * Join an existing call.
     *
     * Access control rules:
     * - Project call  → user must be an active member of the project.
     * - Conversation call → user must be a participant in the conversation.
     * - Ad-hoc call   → no restriction (open to any verified user with the call ID).
     *
     * State rules:
     * - Cannot join a terminal (ended/cancelled) call.
     * - Rejoining (left_at set) resets left_at to null on the existing row.
     * - Already active in the call → idempotent, safe for reconnects.
     * - Joining a scheduled call activates it.
     */
    public function join(VideoCall $call, User $user): VideoCall
    {
        if ($call->isTerminal()) {
            throw new CallNotJoinableException();
        }

        // ── Access check ───────────────────────────────────────────────────────
        $this->assertCanJoin($call, $user);

        // ── Participant record ─────────────────────────────────────────────────
        $existing = $this->callRepo->findParticipant($call, $user->id);

        if ($existing) {
            if ($existing->left_at === null) {
                // Already active — idempotent return (safe on reconnect)
                return $call->load(['initiator', 'participants.user']);
            }

            // Rejoining after leaving — reset left_at on existing row
            // (unique constraint prevents creating a second row)
            $this->callRepo->rejoinParticipant($existing);
        } else {
            $this->callRepo->addParticipant(
                $call,
                $user,
                CallParticipantRole::Participant->value
            );
        }

        // Auto-activate if this is the first other person joining
        if ($call->isScheduled()) {
            $call = $this->callRepo->updateStatus($call, CallStatus::Active->value);
        }

        return $call->load(['initiator', 'participants.user']);
    }

    // =========================================================================
    // Leave
    // =========================================================================

    /**
     * Mark the user as having left the call.
     *
     * If no active participants remain, the call is automatically ended.
     */
    public function leave(VideoCall $call, User $user): VideoCall
    {
        if ($call->isTerminal()) {
            throw new CallAlreadyEndedException();
        }

        $participant = $this->callRepo->findParticipant($call, $user->id);

        if (! $participant || $participant->left_at !== null) {
            throw new NotACallParticipantException();
        }

        $this->callRepo->markParticipantLeft($participant);

        $remainingCount = $call->activeParticipants()
            ->where('user_id', '!=', $user->id)
            ->count();

        if ($remainingCount === 0) {
            return $this->callRepo->endCall($call);
        }

        return $call->fresh(['initiator', 'participants.user']);
    }

    // =========================================================================
    // End (active call → ended)
    // =========================================================================

    /**
     * End an active call forcefully. Host only.
     *
     * Semantics:
     * - Active call   → status becomes 'ended', end_time + duration recorded.
     * - Scheduled call → use cancel() instead. End on a scheduled call is rejected.
     */
    public function end(VideoCall $call, User $user): VideoCall
    {
        if ($call->isTerminal()) {
            throw new CallAlreadyEndedException();
        }

        if ($call->isScheduled()) {
            // Scheduled calls that haven't started should be cancelled, not ended.
            // Redirect the caller to use PATCH /calls/{id}/cancel instead.
            throw new CallNotJoinableException();
        }

        if ($call->initiated_by !== $user->id) {
            throw new NotCallHostException();
        }

        return $this->callRepo->endCall($call);
    }

    // =========================================================================
    // Cancel (scheduled call → cancelled)
    // =========================================================================

    /**
     * Cancel a scheduled call that hasn't started yet. Host only.
     *
     * Semantics:
     * - Scheduled call → status becomes 'cancelled'. No duration recorded.
     * - Active call → use end() instead.
     */
    public function cancel(VideoCall $call, User $user): VideoCall
    {
        if ($call->isTerminal()) {
            throw new CallAlreadyEndedException();
        }

        if (! $call->isScheduled()) {
            // Can only cancel a call that hasn't started.
            // Use end() for active calls.
            throw new CallNotJoinableException();
        }

        if ($call->initiated_by !== $user->id) {
            throw new NotCallHostException();
        }

        return $this->callRepo->cancelCall($call);
    }

    // =========================================================================
    // Private — access enforcement
    // =========================================================================

    /**
     * Assert the user is allowed to join this call.
     *
     * Context-based access:
     *   project call      → must be active project team member
     *   conversation call → must be conversation participant
     *   ad-hoc call       → no restriction (direct invite via call ID)
     */
    private function assertCanJoin(VideoCall $call, User $user): void
    {
        // The call initiator can always rejoin regardless of context —
        // they created the call so they are implicitly authorised.
        if ($call->initiated_by === $user->id) {
            return;
        }

        // Project call — enforce project membership
        if ($call->project_id) {
            $isMember = $this->teamRepo->isMember($call->project_id, $user->id);

            if (! $isMember) {
                throw new CallNotJoinableException();
            }

            return;
        }

        // Conversation call — enforce conversation membership
        if ($call->conversation_id) {
            $isParticipant = $this->conversationRepo->isParticipant($call->conversation_id, $user->id);

            if (! $isParticipant) {
                throw new CallNotJoinableException();
            }
        }

        // Ad-hoc call — no restriction. The 16-char random room name
        // and the call UUID together make accidental discovery impossible.
    }

    /**
     * Generate a unique cryptographically random room name.
     * Format: cofound-<16 random chars> — keeps Jitsi rooms unguessable.
     */
    private function generateRoomName(): string
    {
        return Str::random();
    }
}
