<?php

namespace App\Services;

use App\Models\CollaborationInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class InvitationService
{
    /**
     * List all invitations for the user (sent + received).
     */
    public function list(User $user): array
    {
        $sent = CollaborationInvitation::where('sender_id', $user->id)
            ->with(['recipient', 'project'])
            ->latest()
            ->get();

        $received = CollaborationInvitation::where('recipient_id', $user->id)
            ->with(['sender', 'project'])
            ->latest()
            ->get();

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

        // Prevent duplicate pending invitations of the same type to the same recipient
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
