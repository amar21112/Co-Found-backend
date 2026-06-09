<?php

namespace App\Services\Project;

use App\Enums\ApplicationStatus;
use App\Exceptions\ApplicationAlreadyExistsException;
use App\Exceptions\ApplicationNotWithdrawableException;
use App\Exceptions\ProjectException;
use App\Exceptions\ProjectNotAcceptingApplicationsException;
use App\Mail\Project\ApplicationAcceptedMail;
use App\Mail\Project\ApplicationReceivedMail;
use App\Mail\Project\ApplicationRejectedMail;
use App\Models\ApplicationSkill;
use App\Models\Project;
use App\Models\ProjectApplication;
use App\Models\User;
use App\Repositories\Contracts\ProjectApplicationRepositoryInterface;
use App\Repositories\Contracts\ProjectRoleRepositoryInterface;
use App\Repositories\Contracts\ProjectTeamRepositoryInterface;
use App\Traits\SendsNotifications;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ProjectApplicationService
{
    use SendsNotifications;
    public function __construct(
        private readonly ProjectApplicationRepositoryInterface $applicationRepo,
        private readonly ProjectRoleRepositoryInterface $roleRepo,
        private readonly ProjectTeamRepositoryInterface $teamRepo,
    ) {
    }

    public function listForProject(Project $project, array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->applicationRepo->paginateForProject($project->id, $filters, $perPage);
    }

    public function listForUser(User $user, array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->applicationRepo->paginateForUser($user->id, $filters, $perPage);
    }

    public function show(string $applicationId): ProjectApplication
    {
        $application = $this->applicationRepo->findById($applicationId);

        if (!$application) {
            throw new ProjectException('Application not found.', 404);
        }

        return $application;
    }

    public function apply(Project $project, User $applicant, array $data): ProjectApplication
    {
        // Guard: project must be accepting applications
        if (!$project->is_accepting_applications) {
            throw new ProjectNotAcceptingApplicationsException();
        }

        // Guard: no duplicate application
        if ($this->applicationRepo->hasApplied($project->id, $applicant->id)) {
            throw new ApplicationAlreadyExistsException();
        }

        // Validate role_id belongs to this project
        if (!empty($data['role_id'])) {
            $role = $this->roleRepo->findById($data['role_id']);
            if (!$role || $role->project_id !== $project->id) {
                throw new ProjectException('The specified role does not belong to this project.', 422);
            }

            // Ensure proposed_role is null when role_id is used
            $data['proposed_role'] = null;
        }

        $skills = $data['skills'] ?? [];
        unset($data['skills']);

        $data['project_id'] = $project->id;
        $data['applicant_id'] = $applicant->id;
        $data['status'] = ApplicationStatus::Pending->value;
        $data['applied_at'] = now();

        $application = $this->applicationRepo->create($data);

        // Persist claimed skills
        foreach ($skills as $skill) {
            ApplicationSkill::create(array_merge($skill, ['application_id' => $application->id]));
        }

        $project->increment('application_count');

        $fresh = $this->applicationRepo->findById($application->id);

        // Notify the project owner that a new application arrived
        $this->notify(
            userId: $project->owner_id,
            type: 'new_application',
            title: 'New application received',
            body: "{$applicant->full_name} applied to \u201c{$project->title}\u201d.",
            data: ['project_id' => $project->id, 'application_id' => $fresh->id],
            priority: 'high',
        );

        $owner = $project->owner;   // eager-load or resolve the owner model
        Mail::to($owner->email)->queue(
            new ApplicationReceivedMail(
                owner:       $owner,
                applicant:   $applicant,
                project:     $project,
                application: $fresh,
            )
        );

        return $fresh;
    }

    public function review(
        Project $project,
        string $applicationId,
        string $newStatus,
        User $reviewer,
    ): ProjectApplication {
        $application = $this->resolveApplication($project, $applicationId);

        if (!ApplicationStatus::from($application->status)->isReviewable()) {
            throw new ProjectException('This application cannot be reviewed in its current state.', 422);
        }


        if ($newStatus === ApplicationStatus::Accepted->value) {
            if ($this->teamRepo->isMember($project->id, $application->applicant_id)) {
                throw new ProjectException(
                    'This user is already a team member. The application cannot be accepted.',
                    409
                );
            }

            DB::transaction(function () use ($project, $application, $reviewer) {

                $this->applicationRepo->update($application, [
                    'status' => ApplicationStatus::Accepted->value,
                    'reviewed_by' => $reviewer->id,
                    'reviewed_at' => now(),
                ]);


                $position = $this->resolvePosition($application);

                $this->teamRepo->addMember($project->id, $application->applicant_id, [
                    'role_id' => $application->role_id,      // null when proposed_role was used
                    'position' => $position,
                    'permissions' => 'member',
                ]);

                // 4. Increment role positions_filled if a formal role was targeted
                if ($application->role_id) {
                    $role = $this->roleRepo->findById($application->role_id);
                    if ($role) {
                        $role->increment('positions_filled');
                    }
                }

                // 5. Increment project team size
                $project->increment('current_team_size');
            });

            // Notify applicant of acceptance (outside transaction — non-critical)
            $this->notify(
                userId: $application->applicant_id,
                type: 'application_accepted',
                title: '🎉 Your application was accepted!',
                body: "You've been added to {$project->title}" . ($application->role ? " as {$application->role->role_name}" : '') . '.',
                data: [
                    'project_id' => $project->id,
                    'application_id' => $application->id,
                ],
                priority: 'high',
            );

            Mail::to($application->applicant->email)->queue(
                new ApplicationAcceptedMail(
                    applicant:   $application->applicant,
                    project:     $project,
                    application: $this->applicationRepo->findById($application->id),
                )
            );

            return $this->applicationRepo->findById($application->id);
        }

        // ── Rejection / reviewing path ─────────────────────────────────────────
        $updated = $this->applicationRepo->update($application, [
            'status' => $newStatus,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        if ($newStatus === ApplicationStatus::Rejected->value) {
            $this->notify(
                userId: $application->applicant_id,
                type: 'application_rejected',
                title: 'Application update for ' . $project->title,
                body: 'Your application was not selected this time.',
                data: [
                    'project_id' => $project->id,
                    'application_id' => $application->id,
                ],
                priority: 'normal',
            );

            Mail::to($application->applicant->email)->queue(
                new ApplicationRejectedMail(
                    applicant:   $application->applicant,
                    project:     $project,
                    application: $updated,
                )
            );
        }

        return $updated;
    }

    public function withdraw(string $applicationId, User $applicant): ProjectApplication
    {
        $application = $this->applicationRepo->findById($applicationId);

        if (!$application || $application->applicant_id !== $applicant->id) {
            throw new ProjectException('Application not found.', 404);
        }

        $status = ApplicationStatus::from($application->status);

        if ($status->isTerminal()) {
            throw new ApplicationNotWithdrawableException();
        }

        return $this->applicationRepo->update($application, [
            'status' => ApplicationStatus::Withdrawn->value,
        ]);
    }

    private function resolveApplication(Project $project, string $applicationId): ProjectApplication
    {
        $application = $this->applicationRepo->findById($applicationId);

        if (!$application || $application->project_id !== $project->id) {
            throw new ProjectException('Application not found.', 404);
        }

        return $application;
    }

    private function resolvePosition(ProjectApplication $application): string
    {
        if ($application->role_id && $application->role) {
            return $application->role->role_name;
        }

        if ($application->proposed_role) {
            return $application->proposed_role;
        }

        return 'Team Member';
    }
}
