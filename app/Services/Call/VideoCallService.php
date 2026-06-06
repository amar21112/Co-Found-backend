<?php

namespace App\Services\Call;

use App\DTOs\Call\InitiateCallDTO;
use App\Enums\CallParticipantRole;
use App\Enums\CallStatus;
use App\Exceptions\Call\CallAlreadyEndedException;
use App\Exceptions\Call\CallFullException;
use App\Exceptions\Call\CallNotFoundException;
use App\Exceptions\Call\CallNotJoinableException;
use App\Exceptions\Call\NotACallParticipantException;
use App\Exceptions\Call\NotCallHostException;
use App\Exceptions\ConversationNotFoundException;
use App\Firebase\FirebaseService;
use App\Models\User;
use App\Models\VideoCall;
use App\Repositories\Contracts\ProjectTeamRepositoryInterface;
use App\Repositories\Contracts\VideoCallRepositoryInterface;
use Firebase\JWT\JWT;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Kreait\Firebase\Exception\DatabaseException;

class VideoCallService
{
    public function __construct(
        private readonly VideoCallRepositoryInterface   $callRepo,
        private readonly ProjectTeamRepositoryInterface $teamRepo,
        private readonly FirebaseService               $firebaseService,
    ) {}

    // =========================================================================
    // List
    // =========================================================================

    public function listForUser(User $user, array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->callRepo->paginateForUser($user->id, $filters, $perPage);
    }

    // =========================================================================
    // Initiate
    // =========================================================================

    /**
     * Create a new call, add the initiator as host participant, and mint
     * their Jitsi JWT immediately.
     *
     * The initiator is already a participant (host) the moment they create
     * the call, so there is no reason to make them call /join afterwards.
     * Returning join_token here saves one round-trip and lets the frontend
     * open the Jitsi room directly from the initiate response.
     *
     * room_url stored in DB is always the bare path (no token embedded).
     * The frontend constructs the final URL as: {room_url}?jwt={join_token}
     *
     * @throws DatabaseException
     * @throws ConversationNotFoundException
     */
    public function initiate(User $initiator, InitiateCallDTO $dto): VideoCall
    {
        if ($dto->conversationId) {
            if (! $this->firebaseService->exists(
                $this->firebaseService->conversationPath($dto->conversationId)
            )) {
                throw new ConversationNotFoundException();
            }
        }

        $roomName = $this->generateRoomName();
        $roomUrl  = $this->buildRoomUrl($roomName);

        $call = $this->callRepo->create($initiator, $dto, $roomName, $roomUrl);

        [$token, $jti] = $this->mintJwt($call, $initiator);

        $this->callRepo->addParticipant(
            $call,
            $initiator,
            CallParticipantRole::Host->value,
            $jti
        );

        $call = $call->load(['initiator', 'participants.user']);
        $call->join_token = $token;

        return $call;
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
     * Join a call and receive a short-lived Jitsi JWT for that room.
     *
     * Access control:
     *   - Project call      → active project team member only (MySQL)
     *   - Conversation call → participant of that conversation (Firebase)
     *   - No context        → rejected (fail-closed)
     *
     * Capacity control:
     *   - Private conversation → max 2
     *   - Group conversation   → active participant count (Firebase)
     *   - Project call         → active team member count (MySQL)
     *
     * Token rotation:
     *   The minted jti is stored on the participant row as active_token_jti.
     *   mod_cofound_access verifies jti on every Prosody MUC join.
     *   The frontend refreshes every 25s — each refresh mints a new jti,
     *   invalidating any previously shared token at the DB level.
     *
     * @throws DatabaseException
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

        [$token, $jti] = $this->mintJwt($call, $user);

        if ($existing) {
            if ($existing->left_at === null) {
                // Already active — idempotent reconnect.
                // Update jti: the new token is now the valid one, any session
                // still holding the old jti will be blocked by mod_cofound_access.
                $this->callRepo->updateParticipantJti($existing, $jti);
                $call->join_token = $token;
                return $call->load(['initiator', 'participants.user']);
            }

            $this->assertCapacity($call);

            $this->callRepo->rejoinParticipant($existing, $jti);
        } else {
            // Brand new participant — must fit within capacity.
            $this->assertCapacity($call);

            $this->callRepo->addParticipant(
                $call,
                $user,
                CallParticipantRole::Participant->value,
                $jti
            );
        }

        if ($call->isScheduled()) {
            $call = $this->callRepo->updateStatus($call, CallStatus::Active->value);
        }

        $call->join_token = $token;

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
    // End
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
    // Cancel
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
    // Private — JWT minting
    // =========================================================================

    /**
     * Mint a Jitsi JWT and return [token, jti].
     *
     * jti is placed at the top level (JWT spec) AND inside context.user so
     * mod_auth_token copies it to session.jitsi_meet_context_user, where
     * mod_cofound_access reads it without re-parsing the full JWT.
     *
     * Jitsi's prosody token plugin validates:
     *   - iss  → must match APP_ID configured in prosody
     *   - aud  → must be "jitsi"
     *   - sub  → the XMPP domain of your Jitsi server
     *   - room → the specific room this token is valid for (scopes the token)
     *   - exp  → token expiry (JITSI_TOKEN_TTL seconds from now)
     *
     * Token TTL: JITSI_TOKEN_TTL (default 30s).
     * Frontend must refresh every JITSI_TOKEN_REFRESH_INTERVAL (default 25s).
     *
     * @return array{0: string, 1: string} [token, jti]
     */
    private function mintJwt(VideoCall $call, User $user): array
    {
        $appId     = config('jitsi.app_id');
        $appSecret = config('jitsi.app_secret');
        $domain    = parse_url(config('jitsi.base_url'), PHP_URL_HOST);
        $ttl       = (int) config('jitsi.token_ttl');
        $jti       = Str::uuid()->toString();
        $isHost    = $call->initiated_by === $user->id;

        $payload = [
            'iss'  => $appId,
            'aud'  => 'jitsi',
            'sub'  => $domain,
            'room' => strtolower($call->room_name),
            'exp'  => time() + $ttl,
            'iat'  => time(),
            'nbf'  => time() - 5,
            'jti'  => $jti,
            'context' => [
                'user' => [
                    'id'     => $user->id,
                    'name'   => $user->full_name,
                    'email'  => $user->email,
                    'avatar' => $user->profile_picture_url ?? '',
                    'jti'    => $jti,
                ],
                'features' => [
                    'recording'      => $isHost,
                    'livestream'     => false,
                    'outbound-call'  => false,
                ],
            ],
            'moderator' => $isHost,
        ];

        return [
            JWT::encode($payload, $appSecret, 'HS256'), $jti
        ];
    }

    // =========================================================================
    // Private — access enforcement
    // =========================================================================

    /**
     * Assert the user is allowed to join this call.
     *
     * Every call must have exactly one context (project_id XOR conversation_id),
     * enforced at creation time by InitiateCallRequest. This method is a
     * second, independent line of defence — it fails closed if somehow a
     * context-less call record exists (e.g. created directly in the DB,
     * or via a future code path that bypasses the request class).
     *
     * @throws DatabaseException
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
            if (! $this->teamRepo->isMember($call->project_id, $user->id)) {
                throw new CallNotJoinableException();
            }

            return;
        }

        // Conversation call — enforce conversation membership
        if ($call->conversation_id) {
            if (! $this->firebaseService->isConversationParticipant(
                $call->conversation_id,
                $user->id
            )) {
                throw new CallNotJoinableException();
            }

            return;
        }

        throw new CallNotJoinableException();
    }

    // =========================================================================
    // Private — capacity enforcement
    // =========================================================================

    /**
     * @throws CallFullException
     * @throws DatabaseException
     */
    private function assertCapacity(VideoCall $call): void
    {
        $activeInCall = $this->callRepo->activeParticipantCount($call);
        $maxAllowed   = $this->resolveMaxParticipants($call);

        if ($activeInCall >= $maxAllowed) {
            throw new CallFullException();
        }
    }

    /**
     * Resolve the maximum number of simultaneous participants for a call.
     *
     * Direct conversation → exactly 2 (the two members, no extras).
     * Group/project conversation → all active members of that context.
     *
     * @throws DatabaseException
     */
    private function resolveMaxParticipants(VideoCall $call): int
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
            return max(2, $this->teamRepo->countActiveTeamMembers($call->project_id));
        }

        // Fallback — should never reach here given context enforcement
        return 2;
    }

    // =========================================================================
    // Private — room helpers
    // =========================================================================

    /**
     * Build the bare room URL (no JWT). Stored in DB for reference.
     * The frontend must use the join_token returned by /join to get access.
     */
    private function buildRoomUrl(string $roomName): string
    {
        return rtrim(config('jitsi.base_url'), '/') . '/' . $roomName;
    }

    /**
     * 16-char cryptographically random room name — makes room enumeration
     * infeasible even without JWT (defense in depth).
     */
    private function generateRoomName(): string
    {
        return 'cofound-' . strtolower(Str::random());
    }
}
