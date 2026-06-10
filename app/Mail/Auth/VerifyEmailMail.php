<?php

namespace App\Mail\Auth;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class VerifyEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Retry up to 3 times with exponential backoff (seconds).
     * Auth emails are critical — never drop silently.
     */
    public int $tries = 3;
    public array $backoff = [30, 120, 300];

    /**
     * @param User   $user  The user who needs to verify their email.
     * @param string $token The raw verification token (stored hashed in DB).
     */
    public function __construct(
        public readonly User   $user,
        public readonly string $token,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to:      $this->user->email,
            subject: __('email.verify.subject'),
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
        $base = config('app.url');

        return new Content(
            view: 'emails.auth.verify-email',
            text: 'emails.plain.auth.verify-email',
            with: [
                'userName'        => $this->user->full_name,
                // Frontend route: /auth/email/verify/:token
                // Page calls: GET /api/v1/auth/email/verify/{token}
                'verificationUrl' => "$base/auth/email/verify/$this->token",
                'expiresInHours'  => 24,
            ],
        );
    }

    public function failed(Throwable $e): void
    {
        Log::error('VerifyEmailMail failed', [
            'user_id' => $this->user->id,
            'email'   => $this->user->email,
            'error'   => $e->getMessage(),
        ]);
    }
}
