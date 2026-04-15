<?php

namespace App\Repositories\Contracts;

use App\Models\ProjectSkill;
use Illuminate\Support\Collection;

interface ProjectSkillRepositoryInterface
{
    public function forProject(string $projectId): Collection;

    public function findById(string $id): ?ProjectSkill;

    public function create(string $projectId, array $data): ProjectSkill;

    public function update(ProjectSkill $skill, array $data): ProjectSkill;

    public function delete(ProjectSkill $skill): void;

    public function syncForProject(string $projectId, array $skills): void;
}
