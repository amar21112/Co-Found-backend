<?php

namespace App\Services\Project;

use App\Exceptions\ProjectException;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Repositories\Contracts\ProjectMilestoneRepositoryInterface;
use App\Repositories\Contracts\ProjectTeamRepositoryInterface;
use App\Traits\SendsNotifications;
use Illuminate\Support\Collection;

class ProjectMilestoneService
{
    use SendsNotifications;

    public function __construct(
        private readonly ProjectMilestoneRepositoryInterface $milestoneRepo,
        private readonly ProjectTeamRepositoryInterface      $teamRepo,
    ) {}

    public function list(Project $project): Collection
    {
        return $this->milestoneRepo->forProject($project->id);
    }

    public function create(Project $project, array $data): ProjectMilestone
    {
        $milestone = $this->milestoneRepo->create($project->id, $data);

        // Notify each active team member about the new milestone
        $memberIds = $this->teamRepo->forProject($project->id, activeOnly: true)
            ->pluck('user_id')
            ->reject(fn($id) => $id === $project->owner_id) // owner already knows
            ->values()
            ->all();

        $this->notifyMany(
            userIds:  $memberIds,
            type:     'milestone_created',
            title:    'New milestone added',
            body:     "\u201c{$milestone->title}\u201d was added to {$project->title}.",
            data:     ['project_id' => $project->id, 'milestone_id' => $milestone->id],
            priority: 'normal',
        );

        return $milestone;
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
