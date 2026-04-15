<?php

namespace App\Repositories\Eloquent;

use App\Models\ProjectSkill;
use App\Repositories\Contracts\ProjectSkillRepositoryInterface;
use Illuminate\Support\Collection;

class ProjectSkillRepository implements ProjectSkillRepositoryInterface
{
    public function forProject(string $projectId): Collection
    {
        return ProjectSkill::where('project_id', $projectId)->get();
    }

    public function findById(string $id): ?ProjectSkill
    {
        return ProjectSkill::find($id);
    }

    public function create(string $projectId, array $data): ProjectSkill
    {
        return ProjectSkill::create(array_merge($data, ['project_id' => $projectId]));
    }

    public function update(ProjectSkill $skill, array $data): ProjectSkill
    {
        $skill->update($data);
        return $skill->fresh();
    }

    public function delete(ProjectSkill $skill): void
    {
        $skill->delete();
    }

    public function syncForProject(string $projectId, array $skills): void
    {
        ProjectSkill::where('project_id', $projectId)->delete();

        foreach ($skills as $skill) {
            ProjectSkill::create(array_merge($skill, ['project_id' => $projectId]));
        }
    }
}
