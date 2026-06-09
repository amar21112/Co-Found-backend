<?php

namespace App\Mail\Collaboration;

use App\Models\CollaborationInvitation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sent to RECIPIENT when they receive a collaboration invitation.
 *
 * Eager-load before queuing:
 *   $invitation->load(['sender', 'recipient', 'project']);
 */
class InvitationReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [60, 180, 600];

    public function __construct(
        public readonly User                    $recipient,
        public readonly User                    $sender,
        public readonly CollaborationInvitation $invitation,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to:      $this->recipient->email,
            subject: __('email.invitation_received.subject', ['sender' => $this->sender->full_name]),
        );
    }

    public function headers(): Headers
    {
        return new Headers(
            text: [
                'List-Unsubscribe'      => '<mailto:unsubscribe@' . parse_url(config('app.url'), PHP_URL_HOST) . '?subject=unsubscribe>',
                'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
            ],
        );
    }

    public function content(): Content
    {
        $typeLabels = [
            'project_join'          => __('email.invitation_types.project_join'),
            'team_invite'           => __('email.invitation_types.team_invite'),
            'collaboration_request' => __('email.invitation_types.collaboration_request'),
            'mentorship'            => __('email.invitation_types.mentorship'),
            'co_founder'            => __('email.invitation_types.co_founder'),
        ];

        return new Content(
            view: 'emails.collaboration.invitation-received',
            text: 'emails.plain.collaboration.invitation-received',
            with: [
                'recipientName'  => $this->recipient->full_name,
                'senderName'     => $this->sender->full_name,
                'invitationType' => $typeLabels[$this->invitation->invitation_type] ?? __('email.invitation_types.default'),
                'projectTitle'   => $this->invitation->project?->title,
                'message'        => $this->invitation->message ?? null,
                'inviteUrl'      => config('app.url') . "/invitations/{$this->invitation->id}",
                'expiresAt'      => $this->invitation->expires_at?->format('M j, Y'),
            ],
        );
    }

    public function failed(Throwable $e): void
    {
        Log::error('InvitationReceivedMail failed', [
            'recipient_id'  => $this->recipient->id,
            'invitation_id' => $this->invitation->id,
            'error'         => $e->getMessage(),
        ]);
    }
}
