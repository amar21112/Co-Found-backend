<?php

namespace App\Services\Project;

use App\Enums\ApplicationStatus;
use App\Exceptions\ApplicationAlreadyExistsException;
use App\Exceptions\ApplicationNotWithdrawableException;
use App\Exceptions\ProjectException;
use App\Exceptions\ProjectNotAcceptingApplicationsException;
use App\Models\ApplicationSkill;
use App\Models\Project;
use App\Models\ProjectApplication;
use App\Models\User;
use App\Repositories\Contracts\ProjectApplicationRepositoryInterface;
use App\Repositories\Contracts\ProjectRoleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProjectApplicationService
{
    public function __construct(
        private readonly ProjectApplicationRepositoryInterface $applicationRepo,
        private readonly ProjectRoleRepositoryInterface        $roleRepo,
    ) {}

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

        $data['project_id']   = $project->id;
        $data['applicant_id'] = $applicant->id;
        $data['status']       = ApplicationStatus::Pending->value;
        $data['applied_at']   = now();

        $application = $this->applicationRepo->create($data);

        // Persist claimed skills
        foreach ($skills as $skill) {
            ApplicationSkill::create(array_merge($skill, ['application_id' => $application->id]));
        }

        $project->increment('application_count');

        return $this->applicationRepo->findById($application->id);
    }

    public function review(Project $project, string $applicationId, string $newStatus, User $reviewer): ProjectApplication
    {
        $application = $this->resolveApplication($project, $applicationId);

        if (!ApplicationStatus::from($application->status)->isReviewable()) {
            throw new ProjectException('This application cannot be reviewed in its current state.', 422);
        }

        return $this->applicationRepo->update($application, [
            'status'      => $newStatus,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);
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
}
