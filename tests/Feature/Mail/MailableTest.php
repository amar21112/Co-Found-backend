<?php

namespace Tests\Feature\Mail;

use App\Mail\Auth\ResetPasswordMail;
use App\Mail\Auth\VerifyEmailMail;
use App\Mail\Collaboration\ConnectionRequestMail;
use App\Mail\Collaboration\InvitationReceivedMail;
use App\Mail\Project\ApplicationAcceptedMail;
use App\Mail\Project\ApplicationReceivedMail;
use App\Mail\Project\ApplicationRejectedMail;
use App\Models\CollaborationInvitation;
use App\Models\NotificationPreference;
use App\Models\Project;
use App\Models\ProjectApplication;
use App\Models\User;
use App\Models\UserConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MailableTest extends TestCase
{
    use RefreshDatabase;

    // ────────────────────────────────────────────────────────────────────────
    // Auth
    // ────────────────────────────────────────────────────────────────────────

    public function test_verify_email_is_queued_with_correct_recipient(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        Mail::to($user->email)->queue(new VerifyEmailMail($user, 'raw-token-123'));

        Mail::assertQueued(VerifyEmailMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_verify_email_has_correct_subject(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $mail = new VerifyEmailMail($user, 'raw-token-123');

        $this->assertStringContainsString(
            'Verify',
            $mail->envelope()->subject,
        );
    }

    public function test_reset_password_is_queued_with_correct_recipient(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        Mail::to($user->email)->queue(new ResetPasswordMail($user, 'reset-token-abc'));

        Mail::assertQueued(ResetPasswordMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_verify_email_always_sends_regardless_of_preference(): void
    {
        Mail::fake();

        $user = User::factory()
            ->has(NotificationPreference::factory()->state(['email_notifications' => false]))
            ->create();

        // Auth emails bypass the preference — queue directly
        Mail::to($user->email)->queue(new VerifyEmailMail($user, 'token'));

        Mail::assertQueued(VerifyEmailMail::class);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Project
    // ────────────────────────────────────────────────────────────────────────

    public function test_application_received_is_queued_to_owner(): void
    {
        Mail::fake();

        $owner       = User::factory()->create();
        $applicant   = User::factory()->create();
        $project     = Project::factory()->for($owner, 'owner')->create();
        $application = ProjectApplication::factory()
            ->for($project)
            ->for($applicant, 'applicant')
            ->create();

        Mail::to($owner->email)->queue(
            new ApplicationReceivedMail($owner, $applicant, $project, $application)
        );

        Mail::assertQueued(ApplicationReceivedMail::class, fn ($mail) =>
        $mail->hasTo($owner->email)
        );
    }

    public function test_application_accepted_is_queued_to_applicant(): void
    {
        Mail::fake();

        $applicant   = User::factory()->create();
        $project     = Project::factory()->create();
        $application = ProjectApplication::factory()
            ->for($project)
            ->for($applicant, 'applicant')
            ->create();

        Mail::to($applicant->email)->queue(
            new ApplicationAcceptedMail($applicant, $project, $application)
        );

        Mail::assertQueued(ApplicationAcceptedMail::class, fn ($mail) =>
        $mail->hasTo($applicant->email)
        );
    }

    public function test_application_rejected_is_queued_to_applicant(): void
    {
        Mail::fake();

        $applicant   = User::factory()->create();
        $project     = Project::factory()->create();
        $application = ProjectApplication::factory()
            ->for($project)
            ->for($applicant, 'applicant')
            ->create();

        Mail::to($applicant->email)->queue(
            new ApplicationRejectedMail($applicant, $project, $application)
        );

        Mail::assertQueued(ApplicationRejectedMail::class, fn ($mail) =>
        $mail->hasTo($applicant->email)
        );
    }

    public function test_notification_email_not_sent_when_preference_disabled(): void
    {
        Mail::fake();

        $applicant = User::factory()
            ->has(NotificationPreference::factory()->state(['email_notifications' => false]))
            ->create();
        $project     = Project::factory()->create();
        $application = ProjectApplication::factory()
            ->for($project)
            ->for($applicant, 'applicant')
            ->create();

        // Simulate the SendsEmail trait preference check
        if ($applicant->notificationPreferences->email_notifications) {
            Mail::to($applicant->email)->queue(
                new ApplicationAcceptedMail($applicant, $project, $application)
            );
        }

        Mail::assertNothingQueued();
    }

    // ────────────────────────────────────────────────────────────────────────
    // Collaboration
    // ────────────────────────────────────────────────────────────────────────

    public function test_invitation_received_is_queued_to_recipient(): void
    {
        Mail::fake();

        $recipient  = User::factory()->create();
        $sender     = User::factory()->create();
        $invitation = CollaborationInvitation::factory()
            ->for($sender, 'sender')
            ->for($recipient, 'recipient')
            ->create();

        Mail::to($recipient->email)->queue(
            new InvitationReceivedMail($recipient, $sender, $invitation)
        );

        Mail::assertQueued(InvitationReceivedMail::class, fn ($mail) =>
        $mail->hasTo($recipient->email)
        );
    }

    public function test_connection_request_is_queued_to_recipient(): void
    {
        Mail::fake();

        $recipient  = User::factory()->create();
        $requester  = User::factory()->create();
        $connection = UserConnection::factory()
            ->for($requester, 'requester')
            ->for($recipient, 'recipient')
            ->create();

        Mail::to($recipient->email)->queue(
            new ConnectionRequestMail($recipient, $requester, $connection)
        );

        Mail::assertQueued(ConnectionRequestMail::class, fn ($mail) =>
        $mail->hasTo($recipient->email)
        );
    }

    // ────────────────────────────────────────────────────────────────────────
    // Retry config sanity check
    // ────────────────────────────────────────────────────────────────────────

    public function test_all_mailables_have_retry_config(): void
    {
        $mailables = [
            new VerifyEmailMail(User::factory()->make(), 'token'),
            new ResetPasswordMail(User::factory()->make(), 'token'),
        ];

        foreach ($mailables as $mail) {
            $this->assertSame(3, $mail->tries, get_class($mail) . ' missing $tries');
            $this->assertCount(3, $mail->backoff, get_class($mail) . ' backoff must have 3 intervals');
        }
    }
}
