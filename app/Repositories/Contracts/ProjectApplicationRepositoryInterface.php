<?php

namespace App\Repositories\Contracts;

use App\Models\ProjectApplication;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProjectApplicationRepositoryInterface
{
    public function paginateForProject(string $projectId, array $filters, int $perPage): LengthAwarePaginator;

    public function paginateForUser(string $userId, array $filters, int $perPage): LengthAwarePaginator;

    public function findById(string $id): ?ProjectApplication;

    public function findByProjectAndApplicant(string $projectId, string $applicantId): ?ProjectApplication;

    public function create(array $data): ProjectApplication;

    public function update(ProjectApplication $application, array $data): ProjectApplication;

    public function hasApplied(string $projectId, string $applicantId): bool;
}
