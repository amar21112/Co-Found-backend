<?php

namespace App\Services;

use App\Exceptions\ConflictException;
use App\Mail\Collaboration\InvitationReceivedMail;
use App\Models\CollaborationInvitation;
use App\Models\User;
use App\Traits\SendsNotifications;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class InvitationService
{
    use SendsNotifications;

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
     * @throws ValidationException|ConflictException
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
            throw new ConflictException('You already have a pending invitation of this type to this user.');
        }

        $invitation = CollaborationInvitation::create([
            'sender_id'       => $sender->id,
            'recipient_id'    => $data['recipient_id'],
            'project_id'      => $data['project_id'] ?? null,
            'invitation_type' => $data['invitation_type'],
            'role'            => $data['role'] ?? null,
            'message'         => $data['message'] ?? null,
            'status'          => 'pending',
            'expires_at'      => $data['expires_at'] ?? now()->addDays(7),
        ]);

        // Notify the recipient about the new invitation
        $typeLabel = str_replace('_', ' ', $data['invitation_type']);
        $this->notify(
            userId:   $data['recipient_id'],
            type:     'invitation_received',
            title:    'New invitation',
            body:     "{$sender->full_name} invited you: {$typeLabel}.",
            data:     ['invitation_id' => $invitation->id, 'sender_id' => $sender->id],
            priority: 'high',
        );

        $recipient = User::find($data['recipient_id']);

        Mail::to($recipient->email)->queue(
            new InvitationReceivedMail(
                recipient:  $recipient,
                sender:     $sender,
                invitation: $invitation->load(['sender', 'recipient', 'project']),
            )
        );

        return $invitation;
    }

    /**
     * Respond to a received invitation (accept or decline).
     * Only the recipient can respond.
     *
     * @throws ConflictException
     */
    public function respond(User $user, CollaborationInvitation $invitation, array $data): CollaborationInvitation
    {
        if ($invitation->recipient_id !== $user->id) {
            abort(403, 'Only the recipient can respond to this invitation.');
        }

        if ($invitation->status !== 'pending') {
            throw new ConflictException('This invitation is no longer pending.');
        }

        if ($invitation->isExpired()) {
            throw new ConflictException('This invitation has expired.');
        }

        $invitation->update([
            'status'           => $data['action'],
            'response_message' => $data['response_message'] ?? null,
            'responded_at'     => now(),
        ]);

        $loaded = $invitation->load(['sender', 'recipient', 'project']);

        // Notify the sender of the response
        $action = $data['action'];
        $this->notify(
            userId:   $invitation->sender_id,
            type:     "invitation_{$action}",
            title:    $action === 'accepted' ? 'Invitation accepted 🎉' : 'Invitation declined',
            body:     $action === 'accepted'
                ? "{$user->full_name} accepted your invitation."
                : "{$user->full_name} declined your invitation.",
            data:     ['invitation_id' => $invitation->id],
            priority: $action === 'accepted' ? 'high' : 'normal',
        );

        return $loaded;
    }

    /**
     * Withdraw a sent pending invitation.
     * Only the sender can withdraw.
     *
     * @throws ConflictException
     */
    public function withdraw(User $user, CollaborationInvitation $invitation): void
    {
        if ($invitation->sender_id !== $user->id) {
            abort(403, 'Only the sender can withdraw this invitation.');
        }

        if ($invitation->status !== 'pending') {
            throw new ConflictException('Only pending invitations can be withdrawn.');
        }

        $invitation->update(['status' => 'withdrawn']);

        // Notify the recipient that the invitation was withdrawn
        $this->notify(
            userId:   $invitation->recipient_id,
            type:     'invitation_withdrawn',
            title:    'Invitation withdrawn',
            body:     "{$user->full_name} withdrew their invitation.",
            data:     ['invitation_id' => $invitation->id],
            priority: 'normal',
        );
    }
}
