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
use App\Models\User;
use App\Models\VideoCall;
use App\Repositories\Contracts\ConversationRepositoryInterface;
use App\Repositories\Contracts\ProjectTeamRepositoryInterface;
use App\Repositories\Contracts\VideoCallRepositoryInterface;
use Firebase\JWT\JWT;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class VideoCallService
{
    public function __construct(
        private readonly VideoCallRepositoryInterface $callRepo,
        private readonly ProjectTeamRepositoryInterface $teamRepo,
        private readonly ConversationRepositoryInterface $conversationRepo,
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
     */
    public function initiate(User $initiator, InitiateCallDTO $dto): VideoCall
    {
        $roomName = $this->generateRoomName();
        $roomUrl  = $this->buildRoomUrl($roomName);

        $call = $this->callRepo->create($initiator, $dto, $roomName, $roomUrl);

        $this->callRepo->addParticipant(
            $call,
            $initiator,
            CallParticipantRole::Host->value
        );

        $call = $call->load(['initiator', 'participants.user']);

        // Mint the host token immediately — initiator goes straight to the room
        $call->join_token = $this->mintJwt($call, $initiator);

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
     *   - Project call      → active project team member only
     *   - Conversation call → conversation participant only
     *   - No context        → rejected (fail-closed; should never reach here
     *                         if InitiateCallRequest validation is enforced)
     *
     * State rules:
     *   - Terminal calls (ended / cancelled) cannot be joined.
     *   - Rejoining after leave resets left_at on the existing row.
     *   - Already active → idempotent (safe on frontend reconnect).
     *   - Joining a scheduled call activates it.
     *
     * Returns the updated VideoCall model with `join_token` appended as a
     * transient attribute so VideoCallResource can expose it.
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
                // Already active — mint a fresh token and return (safe reconnect)
                $call->join_token = $this->mintJwt($call, $user);
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

        if ($call->isScheduled()) {
            $call = $this->callRepo->updateStatus($call, CallStatus::Active->value);
        }

        $call->join_token = $this->mintJwt($call, $user);

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
    // Private — JWT minting
    // =========================================================================

    /**
     * Mint a Jitsi-compatible JWT for the given user and room.
     *
     * The token follows the Jitsi JWT structure:
     *   https://github.com/jitsi/lib-jitsi-meet/blob/master/doc/tokens.md
     *
     * Jitsi's prosody token plugin validates:
     *   - iss  → must match APP_ID configured in prosody
     *   - aud  → must be "jitsi"
     *   - sub  → the XMPP domain of your Jitsi server
     *   - room → the specific room this token is valid for (scopes the token)
     *   - exp  → token expiry (JITSI_TOKEN_TTL seconds from now)
     */
    private function mintJwt(VideoCall $call, User $user): string
    {
        $appId     = config('jitsi.app_id');
        $appSecret = config('jitsi.app_secret');
        $domain    = parse_url(config('jitsi.base_url'), PHP_URL_HOST);
        $ttl       = (int) config('jitsi.token_ttl');

        $isHost = $call->initiated_by === $user->id;

        $payload = [
            'iss'  => $appId,
            'aud'  => 'jitsi',
            'sub'  => $domain,
            'room' => $call->room_name,
            'exp'  => time() + $ttl,
            'iat'  => time(),
            'context' => [
                'user' => [
                    'id'     => $user->id,
                    'name'   => $user->full_name,
                    'email'  => $user->email,
                    'avatar' => $user->profile_picture_url ?? '',
                ],
                'features' => [
                    // Only the call initiator gets moderator rights
                    'recording'  => $isHost,
                    'livestream' => false,
                    'outbound-call' => false,
                ],
            ],
            // Jitsi uses this to grant moderator / kick rights
            'moderator' => $isHost,
        ];

        return JWT::encode($payload, $appSecret, 'HS256');
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
     * The initiator is always allowed to rejoin — they created the call and
     * already hold a host participant row.
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
            if (! $this->conversationRepo->isParticipant($call->conversation_id, $user->id)) {
                throw new CallNotJoinableException();
            }

            return;
        }

        // No context at all — fail closed.
        // A well-formed call never reaches here; this guards against
        // corrupt data or direct DB writes bypassing validation.
        throw new CallNotJoinableException();
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
        return 'cofound-' . Str::random();
    }
}
