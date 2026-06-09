<?php

namespace App\Mail\Project;

use App\Models\Project;
use App\Models\ProjectApplication;
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
 * Sent to the APPLICANT when their application is accepted.
 *
 * Eager-load before queuing:
 *   $application->load('role');
 */
class ApplicationAcceptedMail extends Mailable
{
    use Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [60, 180, 600];

    public function __construct(
        public readonly User               $applicant,
        public readonly Project            $project,
        public readonly ProjectApplication $application,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to:      $this->applicant->email,
            subject: __('email.application_accepted.subject', ['project' => $this->project->title]),
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
            view: 'emails.project.application-accepted',
            text: 'emails.plain.project.application-accepted',
            with: [
                'applicantName' => $this->applicant->full_name,
                'projectTitle'  => $this->project->title,
                'roleName'      => $this->application->role?->role_name,
                'projectUrl'    => config('app.url') . "/projects/{$this->project->id}",
            ],
        );
    }

    public function failed(Throwable $e): void
    {
        Log::error('ApplicationAcceptedMail failed', [
            'applicant_id'   => $this->applicant->id,
            'application_id' => $this->application->id,
            'error'          => $e->getMessage(),
        ]);
    }
}
