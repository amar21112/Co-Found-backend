<?php

namespace App\Repositories\Eloquent;

use App\Models\ProjectMilestone;
use App\Repositories\Contracts\ProjectMilestoneRepositoryInterface;
use Illuminate\Support\Collection;

class ProjectMilestoneRepository implements ProjectMilestoneRepositoryInterface
{
    public function forProject(string $projectId): Collection
    {
        return ProjectMilestone::where('project_id', $projectId)
            ->orderBy('order_index')
            ->get();
    }

    public function findById(string $id): ?ProjectMilestone
    {
        return ProjectMilestone::find($id);
    }

    public function create(string $projectId, array $data): ProjectMilestone
    {
        return ProjectMilestone::create(array_merge($data, ['project_id' => $projectId]));
    }

    public function update(ProjectMilestone $milestone, array $data): ProjectMilestone
    {
        $milestone->update($data);
        return $milestone->fresh();
    }

    public function delete(ProjectMilestone $milestone): void
    {
        $milestone->delete();
    }
}
