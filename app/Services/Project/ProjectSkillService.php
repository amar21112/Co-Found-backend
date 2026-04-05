<?php

namespace App\Services\Project;

use App\Exceptions\ProjectException;
use App\Models\Project;
use App\Models\ProjectSkill;
use App\Repositories\Contracts\ProjectSkillRepositoryInterface;
use Illuminate\Support\Collection;

class ProjectSkillService
{
    public function __construct(
        private readonly ProjectSkillRepositoryInterface $skillRepo,
    ) {}

    public function list(Project $project): Collection
    {
        return $this->skillRepo->forProject($project->id);
    }

    public function add(Project $project, array $data): ProjectSkill
    {
        $existing = $this->skillRepo->forProject($project->id)
            ->firstWhere('skill_name', $data['skill_name']);

        if ($existing) {
            throw new ProjectException("Skill '{$data['skill_name']}' is already required for this project.", 409);
        }

        return $this->skillRepo->create($project->id, $data);
    }

    public function update(Project $project, string $skillId, array $data): ProjectSkill
    {
        $skill = $this->resolveSkill($project, $skillId);
        return $this->skillRepo->update($skill, $data);
    }

    public function remove(Project $project, string $skillId): void
    {
        $skill = $this->resolveSkill($project, $skillId);
        $this->skillRepo->delete($skill);
    }

    private function resolveSkill(Project $project, string $skillId): ProjectSkill
    {
        $skill = $this->skillRepo->findById($skillId);

        if (!$skill || $skill->project_id !== $project->id) {
            throw new ProjectException('Skill requirement not found.', 404);
        }

        return $skill;
    }
}
