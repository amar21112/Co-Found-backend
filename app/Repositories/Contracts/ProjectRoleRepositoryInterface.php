<?php

namespace App\Repositories\Contracts;

use App\Models\ProjectRole;
use Illuminate\Support\Collection;

interface ProjectRoleRepositoryInterface
{
    public function forProject(string $projectId): Collection;

    public function findById(string $id): ?ProjectRole;

    public function create(string $projectId, array $data): ProjectRole;

    public function update(ProjectRole $role, array $data): ProjectRole;

    public function delete(ProjectRole $role): void;
}
