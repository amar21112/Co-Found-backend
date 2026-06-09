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
 * Sent to the project OWNER when a new application arrives.
 *
 * Eager-load before queuing:
 *   $project->load('owner');
 *   $application->load('role');
 */
class ApplicationReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [60, 180, 600];

    public function __construct(
        public readonly User               $owner,
        public readonly User               $applicant,
        public readonly Project            $project,
        public readonly ProjectApplication $application,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to:      $this->owner->email,
            subject: __('email.application_received.subject', ['project' => $this->project->title]),
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
            view: 'emails.project.application-received',
            text: 'emails.plain.project.application-received',
            with: [
                'ownerName'     => $this->owner->full_name,
                'applicantName' => $this->applicant->full_name,
                'projectTitle'  => $this->project->title,
                'projectId'     => $this->project->id,
                'roleName'      => $this->application->role?->role_name,
                'coverNote'     => $this->application->cover_note ?? null,
                'reviewUrl'     => config('app.url') . "/projects/{$this->project->id}/applications/{$this->application->id}",
            ],
        );
    }

    public function failed(Throwable $e): void
    {
        Log::error('ApplicationReceivedMail failed', [
            'owner_id'       => $this->owner->id,
            'application_id' => $this->application->id,
            'error'          => $e->getMessage(),
        ]);
    }
}
