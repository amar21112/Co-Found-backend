<?php

namespace App\Services;

use App\Exceptions\ConflictException;
use App\Mail\Collaboration\ConnectionRequestMail;
use App\Models\User;
use App\Models\UserConnection;
use App\Traits\SendsNotifications;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class ConnectionService
{
    use SendsNotifications;

    /**
     * List connections for a user.
     *
     * Supported query params:
     *   status          – pending | accepted | rejected | blocked (default: accepted)
     *   connection_type – collaborator | mentor | mentee | friend
     *   search          – partial match on the other user's full_name or username
     *   sort_by         – created_at | updated_at (default: created_at)
     *   sort_dir        – asc | desc (default: desc)
     */
    public function list(User $user, array $filters = []): Collection
    {
        $status = $filters['status'] ?? 'accepted';

        $query = UserConnection::where(function ($q) use ($user) {
            $q->where('requester_id', $user->id)
                ->orWhere('recipient_id', $user->id);
        })
            ->where('status', $status)
            ->with(['requester', 'recipient']);

        if (! empty($filters['connection_type'])) {
            $query->where('connection_type', $filters['connection_type']);
        }

        if (! empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($user, $term) {
                $q->where(function ($inner) use ($user, $term) {
                    $inner->where('requester_id', $user->id)
                        ->whereHas(
                            'recipient',
                            fn($u) =>
                            $u->where('full_name', 'like', $term)
                                ->orWhere('username', 'like', $term)
                        );
                })->orWhere(function ($inner) use ($user, $term) {
                    $inner->where('recipient_id', $user->id)
                        ->whereHas(
                            'requester',
                            fn($u) =>
                            $u->where('full_name', 'like', $term)
                                ->orWhere('username', 'like', $term)
                        );
                });
            });
        }

        $sortableColumns = ['created_at', 'updated_at'];
        $sortBy  = in_array($filters['sort_by'] ?? '', $sortableColumns)
            ? $filters['sort_by'] : 'created_at';
        $sortDir = ($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortBy, $sortDir)->get();
    }

    /**
     * Send a connection request to another user.
     *
     * @throws ValidationException|ConflictException
     */
    public function send(User $requester, array $data): UserConnection
    {
        $recipientId = $data['recipient_id'];

        if ($requester->id === $recipientId) {
            throw ValidationException::withMessages([
                'recipient_id' => ['You cannot send a connection request to yourself.'],
            ]);
        }

        $existing = UserConnection::where(function ($q) use ($requester, $recipientId) {
            $q->where('requester_id', $requester->id)->where('recipient_id', $recipientId);
        })
            ->orWhere(function ($q) use ($requester, $recipientId) {
                $q->where('requester_id', $recipientId)->where('recipient_id', $requester->id);
            })
            ->first();

        if ($existing) {
            $message = match ($existing->status) {
                'pending'  => 'A connection request already exists between you and this user.',
                'accepted' => 'You are already connected with this user.',
                'blocked'  => 'This connection is blocked.',
                'rejected' => 'This connection request was rejected.',
                default    => 'A connection already exists.',
            };

            throw new ConflictException($message);
        }

        $connection = UserConnection::create([
            'requester_id'    => $requester->id,
            'recipient_id'    => $recipientId,
            'connection_type' => $data['connection_type'] ?? null,
            'status'          => 'pending',
        ]);

        // Notify the recipient of the new request
        $this->notify(
            userId:   $recipientId,
            type:     'connection_request',
            title:    'New connection request',
            body:     "{$requester->full_name} sent you a connection request.",
            data:     ['connection_id' => $connection->id, 'requester_id' => $requester->id],
            priority: 'normal',
        );

        $recipient = User::find($recipientId);

        Mail::to($recipient->email)->queue(
            new ConnectionRequestMail(
                recipient:      $recipient,
                requester:      $requester,
                userConnection: $connection,
            )
        );

        return $connection;
    }

    /**
     * Accept a pending connection request.
     * Only the recipient can accept.
     *
     * @throws ConflictException
     */
    public function accept(User $user, UserConnection $connection): UserConnection
    {
        if ($connection->recipient_id !== $user->id) {
            abort(403, 'Only the recipient can accept a connection request.');
        }

        if ($connection->status !== 'pending') {
            throw new ConflictException('This connection request is no longer pending.');
        }

        $connection->update(['status' => 'accepted']);

        $loaded = $connection->load(['requester', 'recipient']);

        // Notify the requester that their request was accepted
        $this->notify(
            userId:   $connection->requester_id,
            type:     'connection_accepted',
            title:    'Connection accepted 🎉',
            body:     "{$user->full_name} accepted your connection request.",
            data:     ['connection_id' => $connection->id],
            priority: 'normal',
        );

        return $loaded;
    }

    /**
     * Reject a pending connection request.
     * Only the recipient can reject.
     * After rejection the record is deleted — the requester just sees it as gone.
     *
     * @throws ConflictException
     */
    public function reject(User $user, UserConnection $connection): void
    {
        if ($connection->recipient_id !== $user->id) {
            abort(403, 'Only the recipient can reject a connection request.');
        }

        if ($connection->status !== 'pending') {
            throw new ConflictException('Only pending connection requests can be rejected.');
        }

        // Delete immediately — rejected requests are not kept in the DB.
        // This means the requester can send again in the future if they choose.
        $connection->delete();
    }

    /**
     * Block a connection / user.
     * Either party can block.
     * Blocking an accepted connection converts it to blocked status.
     * Blocking someone you are not connected with creates a new blocked record
     * so that future connection requests from that user are prevented.
     * The blocked user is NOT notified.
     *
     * @throws ConflictException
     */
    public function block(User $user, UserConnection $connection): UserConnection
    {
        $isParticipant = $connection->requester_id === $user->id
            || $connection->recipient_id === $user->id;

        if (! $isParticipant) {
            abort(403, 'You are not part of this connection.');
        }

        if ($connection->status === 'blocked') {
            throw new ConflictException('This connection is already blocked.');
        }

        // Whoever calls block becomes the requester in the blocked record,
        // so we know who did the blocking. We re-use the same row.
        $connection->update([
            'status'       => 'blocked',
            'requester_id' => $user->id,                                              // blocker
            'recipient_id' => $connection->requester_id === $user->id                 // blocked
                ? $connection->recipient_id
                : $connection->requester_id,
        ]);

        return $connection->fresh()->load(['requester', 'recipient']);
    }

    /**
     * Remove (delete) a connection — works for accepted, pending, and blocked.
     */
    public function remove(User $user, UserConnection $connection): void
    {
        $isParticipant = $connection->requester_id === $user->id
            || $connection->recipient_id === $user->id;

        if (! $isParticipant) {
            abort(403, 'You are not part of this connection.');
        }

        $connection->delete();
    }
}
