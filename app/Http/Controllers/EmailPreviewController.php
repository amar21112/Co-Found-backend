<?php

namespace App\Http\Controllers;

use App\Mail\Auth\ResetPasswordMail;
use App\Mail\Auth\VerifyEmailMail;
use App\Mail\Collaboration\ConnectionRequestMail;
use App\Mail\Collaboration\InvitationReceivedMail;
use App\Mail\Project\ApplicationAcceptedMail;
use App\Mail\Project\ApplicationReceivedMail;
use App\Mail\Project\ApplicationRejectedMail;
use App\Models\CollaborationInvitation;
use App\Models\Project;
use App\Models\ProjectApplication;
use App\Models\ProjectRole;
use App\Models\User;
use App\Models\UserConnection;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use ReflectionException;

class EmailPreviewController extends Controller
{
    /**
     * @throws ReflectionException
     */
    public function __invoke(string $template): Response
    {
        $mailable = match ($template) {
            'verify-email'           => $this->verifyEmail(),
            'reset-password'         => $this->resetPassword(),
            'application-received'   => $this->applicationReceived(),
            'application-accepted'   => $this->applicationAccepted(),
            'application-rejected'   => $this->applicationRejected(),
            'invitation-received'    => $this->invitationReceived(),
            'connection-request'     => $this->connectionRequest(),
        };

        if (request()->boolean('plain')) {
            $textView = $mailable->textView ?? null;
            if (!$textView) {
                return response('No plain text version available.', 404);
            }
            return response(
                view($textView, $mailable->buildViewData())->render(),
                200,
                ['Content-Type' => 'text/plain; charset=UTF-8'],
            );
        }

        return response($mailable->render());
    }

    // ── Fake model factories ─────────────────────────────────────────────────

    private function fakeUser(array $attrs = []): User
    {
        $user = new User();
        $user->setRawAttributes(array_merge([
            'id'        => '00000000-0000-0000-0000-000000000001',
            'full_name' => 'Alex Johnson',
            'email'     => 'alex@example.com',
            'username'  => 'alexjohnson',
            'headline'  => 'Founder & CEO at BuildTrack',
        ], $attrs));
        return $user;
    }

    private function fakeProject(): Project
    {
        $project = new Project();
        $project->setRawAttributes([
            'id'    => '00000000-0000-0000-0000-000000000042',
            'title' => 'BuildTrack',
        ]);
        return $project;
    }

    private function fakeApplication(): ProjectApplication
    {
        $app = new ProjectApplication();
        $app->setRawAttributes([
            'id'         => '00000000-0000-0000-0000-000000000007',
            'project_id' => '00000000-0000-0000-0000-000000000042',
            'cover_note' => 'I\'ve been following BuildTrack since the beta.',
        ]);
        $app->setRelation('role', new ProjectRole(['role_name' => 'Lead Designer']));
        return $app;
    }

    private function fakeInvitation(): CollaborationInvitation
    {
        $inv = new CollaborationInvitation([
            'id'              => '00000000-0000-0000-0000-000000000003',
            'invitation_type' => 'co_founder',
            'message'         => 'Your background in developer tooling is exactly what BuildTrack needs.',
            'expires_at'      => now()->addDays(14),
        ]);
        $inv->setRelation('project', $this->fakeProject());
        return $inv;
    }

    private function fakeConnection(): UserConnection
    {
        return new UserConnection([
            'id' => '00000000-0000-0000-0000-000000000009',
        ]);
    }

    // ── Mailable builders ────────────────────────────────────────────────────

    private function verifyEmail(): VerifyEmailMail
    {
        return new VerifyEmailMail(
            user:  $this->fakeUser(),
            token: Str::random(64),
        );
    }

    private function resetPassword(): ResetPasswordMail
    {
        return new ResetPasswordMail(
            user:  $this->fakeUser(),
            token: Str::random(64),
        );
    }

    private function applicationReceived(): ApplicationReceivedMail
    {
        return new ApplicationReceivedMail(
            owner:       $this->fakeUser(['full_name' => 'Jordan Kim', 'email' => 'jordan@example.com']),
            applicant:   $this->fakeUser(),
            project:     $this->fakeProject(),
            application: $this->fakeApplication(),
        );
    }

    private function applicationAccepted(): ApplicationAcceptedMail
    {
        return new ApplicationAcceptedMail(
            applicant:   $this->fakeUser(),
            project:     $this->fakeProject(),
            application: $this->fakeApplication(),
        );
    }

    private function applicationRejected(): ApplicationRejectedMail
    {
        return new ApplicationRejectedMail(
            applicant:   $this->fakeUser(),
            project:     $this->fakeProject(),
            application: $this->fakeApplication(),
        );
    }

    private function invitationReceived(): InvitationReceivedMail
    {
        return new InvitationReceivedMail(
            recipient:  $this->fakeUser(),
            sender:     $this->fakeUser(['full_name' => 'Jordan Kim', 'email' => 'jordan@example.com']),
            invitation: $this->fakeInvitation(),
        );
    }

    private function connectionRequest(): ConnectionRequestMail
    {
        return new ConnectionRequestMail(
            recipient:      $this->fakeUser(),
            requester:      $this->fakeUser(['full_name' => 'Jordan Kim', 'email' => 'jordan@example.com']),
            userConnection: $this->fakeConnection(),
        );
    }
}
