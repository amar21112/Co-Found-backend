<?php

namespace App\Services\Project;

use App\Exceptions\ProjectException;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Repositories\Contracts\ProjectMilestoneRepositoryInterface;
use Illuminate\Support\Collection;

class ProjectMilestoneService
{
    public function __construct(
        private readonly ProjectMilestoneRepositoryInterface $milestoneRepo,
    ) {}

    public function list(Project $project): Collection
    {
        return $this->milestoneRepo->forProject($project->id);
    }

    public function create(Project $project, array $data): ProjectMilestone
    {
        return $this->milestoneRepo->create($project->id, $data);
    }

    public function update(Project $project, string $milestoneId, array $data): ProjectMilestone
    {
        $milestone = $this->resolveMilestone($project, $milestoneId);
        return $this->milestoneRepo->update($milestone, $data);
    }

    public function delete(Project $project, string $milestoneId): void
    {
        $milestone = $this->resolveMilestone($project, $milestoneId);
        $this->milestoneRepo->delete($milestone);
    }

    private function resolveMilestone(Project $project, string $milestoneId): ProjectMilestone
    {
        $milestone = $this->milestoneRepo->findById($milestoneId);

        if (!$milestone || $milestone->project_id !== $project->id) {
            throw new ProjectException('Milestone not found.', 404);
        }

        return $milestone;
    }
}
