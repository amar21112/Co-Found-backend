<?php

namespace App\Services;

use App\Models\CollaborationInvitation;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class InvitationService
{
    /**
     * List invitations for the user (sent + received).
     *
     * Supported query params:
     *   status          – pending | accepted | declined | expired | withdrawn
     *   invitation_type – project_join | team_invite | collaboration_request | mentorship
     *   direction       – sent | received | both (default: both)
     *   sort_by         – created_at | expires_at | responded_at (default: created_at)
     *   sort_dir        – asc | desc (default: desc)
     */
    public function list(User $user, array $filters = []): array
    {
        $sortableColumns = ['created_at', 'expires_at', 'responded_at'];
        $sortBy  = in_array($filters['sort_by'] ?? '', $sortableColumns)
            ? $filters['sort_by'] : 'created_at';
        $sortDir = ($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $direction = $filters['direction'] ?? 'both';

        // Always initialise both as proper Eloquent collections (not plain collect())
        // so InvitationResource::collection() never receives a plain Collection.
        $sent     = CollaborationInvitation::whereNull('id')->get(); // empty Eloquent collection
        $received = CollaborationInvitation::whereNull('id')->get(); // empty Eloquent collection

        if (in_array($direction, ['sent', 'both'])) {
            $sentQuery = CollaborationInvitation::where('sender_id', $user->id)
                ->with(['recipient', 'project']);

            if (! empty($filters['status']))          $sentQuery->where('status', $filters['status']);
            if (! empty($filters['invitation_type'])) $sentQuery->where('invitation_type', $filters['invitation_type']);

            $sent = $sentQuery->orderBy($sortBy, $sortDir)->get();
        }

        if (in_array($direction, ['received', 'both'])) {
            $receivedQuery = CollaborationInvitation::where('recipient_id', $user->id)
                ->with(['sender', 'project']);

            if (! empty($filters['status']))          $receivedQuery->where('status', $filters['status']);
            if (! empty($filters['invitation_type'])) $receivedQuery->where('invitation_type', $filters['invitation_type']);

            $received = $receivedQuery->orderBy($sortBy, $sortDir)->get();
        }

        return compact('sent', 'received');
    }

    /**
     * Send a new invitation.
     *
     * @throws ValidationException
     */
    public function send(User $sender, array $data): CollaborationInvitation
    {
        if ($sender->id === $data['recipient_id']) {
            throw ValidationException::withMessages([
                'recipient_id' => ['You cannot invite yourself.'],
            ]);
        }

        $duplicate = CollaborationInvitation::where('sender_id', $sender->id)
            ->where('recipient_id', $data['recipient_id'])
            ->where('invitation_type', $data['invitation_type'])
            ->where('status', 'pending')
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'invitation_type' => ['You already have a pending invitation of this type to this user.'],
            ]);
        }

        return CollaborationInvitation::create([
            'sender_id'       => $sender->id,
            'recipient_id'    => $data['recipient_id'],
            'project_id'      => $data['project_id'] ?? null,
            'invitation_type' => $data['invitation_type'],
            'role'            => $data['role'] ?? null,
            'message'         => $data['message'] ?? null,
            'status'          => 'pending',
            'expires_at'      => $data['expires_at'] ?? now()->addDays(7),
        ]);
    }

    /**
     * Respond to a received invitation (accept or decline).
     * Only the recipient can respond.
     *
     * @throws ValidationException
     */
    public function respond(User $user, CollaborationInvitation $invitation, array $data): CollaborationInvitation
    {
        if ($invitation->recipient_id !== $user->id) {
            abort(403, 'Only the recipient can respond to this invitation.');
        }

        if ($invitation->status !== 'pending') {
            throw ValidationException::withMessages([
                'invitation' => ['This invitation is no longer pending.'],
            ]);
        }

        if ($invitation->isExpired()) {
            throw ValidationException::withMessages([
                'invitation' => ['This invitation has expired.'],
            ]);
        }

        $invitation->update([
            'status'           => $data['action'],
            'response_message' => $data['response_message'] ?? null,
            'responded_at'     => now(),
        ]);

        return $invitation->load(['sender', 'recipient', 'project']);
    }

    /**
     * Withdraw a sent pending invitation.
     * Only the sender can withdraw.
     *
     * @throws ValidationException
     */
    public function withdraw(User $user, CollaborationInvitation $invitation): void
    {
        if ($invitation->sender_id !== $user->id) {
            abort(403, 'Only the sender can withdraw this invitation.');
        }

        if ($invitation->status !== 'pending') {
            throw ValidationException::withMessages([
                'invitation' => ['Only pending invitations can be withdrawn.'],
            ]);
        }

        $invitation->update(['status' => 'withdrawn']);
    }
}
