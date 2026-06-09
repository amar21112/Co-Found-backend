<?php

namespace App\Mail\Collaboration;

use App\Models\User;
use App\Models\UserConnection;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sent to the TARGET user when someone sends them a connection request.
 */
class ConnectionRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [60, 180, 600];

    public function __construct(
        public readonly User           $recipient,
        public readonly User           $requester,
        public readonly UserConnection $userConnection,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to:      $this->recipient->email,
            subject: __('email.connection_request.subject', ['requester' => $this->requester->full_name]),
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
        return new Content(
            view: 'emails.collaboration.connection-request',
            text: 'emails.plain.collaboration.connection-request',
            with: [
                'recipientName'  => $this->recipient->full_name,
                'requesterName'  => $this->requester->full_name,
                'requesterTitle' => $this->requester->headline ?? null,
                'profileUrl'     => config('app.url') . "/profile/{$this->requester->username}",
                'connectionsUrl' => config('app.url') . '/connections',
            ],
        );
    }

    public function failed(Throwable $e): void
    {
        Log::error('ConnectionRequestMail failed', [
            'recipient_id'  => $this->recipient->id,
            'requester_id'  => $this->requester->id,
            'error'         => $e->getMessage(),
        ]);
    }
}
