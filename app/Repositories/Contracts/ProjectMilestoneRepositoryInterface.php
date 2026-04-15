<?php

namespace App\Repositories\Contracts;

use App\Models\ProjectMilestone;
use Illuminate\Support\Collection;

interface ProjectMilestoneRepositoryInterface
{
    public function forProject(string $projectId): Collection;

    public function findById(string $id): ?ProjectMilestone;

    public function create(string $projectId, array $data): ProjectMilestone;

    public function update(ProjectMilestone $milestone, array $data): ProjectMilestone;

    public function delete(ProjectMilestone $milestone): void;
}
