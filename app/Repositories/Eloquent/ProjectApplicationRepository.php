<?php

namespace App\Repositories\Eloquent;

use App\Models\ProjectApplication;
use App\Repositories\Contracts\ProjectApplicationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProjectApplicationRepository implements ProjectApplicationRepositoryInterface
{
    public function paginateForProject(string $projectId, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = ProjectApplication::with(['applicant', 'role', 'applicationSkills', 'reviewer'])
            ->where('project_id', $projectId);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['role_id'])) {
            $query->where('role_id', $filters['role_id']);
        }

        return $query->latest('applied_at')->paginate($perPage);
    }

    public function paginateForUser(string $userId, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = ProjectApplication::with(['project', 'role', 'applicationSkills'])
            ->where('applicant_id', $userId);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest('applied_at')->paginate($perPage);
    }

    public function findById(string $id): ?ProjectApplication
    {
        return ProjectApplication::with(['applicant', 'role', 'applicationSkills', 'reviewer', 'project'])
            ->find($id);
    }

    public function findByProjectAndApplicant(string $projectId, string $applicantId): ?ProjectApplication
    {
        return ProjectApplication::where('project_id', $projectId)
            ->where('applicant_id', $applicantId)
            ->first();
    }

    public function create(array $data): ProjectApplication
    {
        return ProjectApplication::create($data);
    }

    public function update(ProjectApplication $application, array $data): ProjectApplication
    {
        $application->update($data);
        return $application->fresh(['applicant', 'role', 'applicationSkills', 'reviewer']);
    }

    public function hasApplied(string $projectId, string $applicantId): bool
    {
        return ProjectApplication::where('project_id', $projectId)
            ->where('applicant_id', $applicantId)
            ->exists();
    }
}
