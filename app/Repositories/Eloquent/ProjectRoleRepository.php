<?php

namespace App\Repositories\Eloquent;

use App\Models\ProjectRole;
use App\Repositories\Contracts\ProjectRoleRepositoryInterface;
use Illuminate\Support\Collection;

class ProjectRoleRepository implements ProjectRoleRepositoryInterface
{
    public function forProject(string $projectId): Collection
    {
        return ProjectRole::where('project_id', $projectId)->get();
    }

    public function findById(string $id): ?ProjectRole
    {
        return ProjectRole::find($id);
    }

    public function create(string $projectId, array $data): ProjectRole
    {
        return ProjectRole::create(array_merge($data, ['project_id' => $projectId,'positions_filled' => 0]));
    }

    public function update(ProjectRole $role, array $data): ProjectRole
    {
        $role->update($data);
        return $role->fresh();
    }

    public function delete(ProjectRole $role): void
    {
        $role->delete();
    }
}
