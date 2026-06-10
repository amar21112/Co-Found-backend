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

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 120, 300];

    /**
     * @param User   $user  The account requesting a password reset.
     * @param string $token The raw reset token.
     */
    public function __construct(
        public readonly User   $user,
        public readonly string $token,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to:      $this->user->email,
            subject: __('email.reset.subject'),
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
            view: 'emails.auth.reset-password',
            text: 'emails.plain.auth.reset-password',
            with: [
                'userName'      => $this->user->full_name,
                // Frontend route: /reset-password?token=  OR  /reset-password/:token
                // Page calls: POST /api/v1/auth/password/reset
                'resetUrl'      => "$base/reset-password?token=$this->token",
                'expiresInMins' => 60,
            ],
        );
    }

    public function failed(Throwable $e): void
    {
        Log::error('ResetPasswordMail failed', [
            'user_id' => $this->user->id,
            'email'   => $this->user->email,
            'error'   => $e->getMessage(),
        ]);
    }
}
