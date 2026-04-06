<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserConnection;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class ConnectionService
{
    /**
     * List all accepted connections for a user.
     */
    public function list(User $user): Collection
    {
        return UserConnection::where(function ($q) use ($user) {
                $q->where('requester_id', $user->id)
                  ->orWhere('recipient_id', $user->id);
            })
            ->where('status', 'accepted')
            ->with(['requester', 'recipient'])
            ->latest()
            ->get();
    }

    /**
     * Send a connection request to another user.
     *
     * @throws ValidationException
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
                default    => 'A connection already exists.',
            };
            throw ValidationException::withMessages(['recipient_id' => [$message]]);
        }

        return UserConnection::create([
            'requester_id'    => $requester->id,
            'recipient_id'    => $recipientId,
            'connection_type' => $data['connection_type'] ?? null,
            'status'          => 'pending',
        ]);
    }

    /**
     * Accept a pending connection request.
     * Only the recipient can accept.
     *
     * @throws ValidationException
     */
    public function accept(User $user, UserConnection $connection): UserConnection
    {
        if ($connection->recipient_id !== $user->id) {
            abort(403, 'Only the recipient can accept a connection request.');
        }

        if ($connection->status !== 'pending') {
            throw ValidationException::withMessages([
                'connection' => ['This connection request is no longer pending.'],
            ]);
        }

        $connection->update(['status' => 'accepted']);

        return $connection->load(['requester', 'recipient']);
    }

    /**
     * Remove (delete) a connection regardless of who initiated it.
     *
     * @throws ValidationException
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
