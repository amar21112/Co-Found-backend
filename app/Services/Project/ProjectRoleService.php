<?php

namespace App\Services\Project;

use App\Exceptions\ProjectException;
use App\Models\Project;
use App\Models\ProjectRole;
use App\Repositories\Contracts\ProjectRoleRepositoryInterface;
use Illuminate\Support\Collection;

class ProjectRoleService
{
    public function __construct(
        private readonly ProjectRoleRepositoryInterface $roleRepo,
    ) {}

    public function list(Project $project): Collection
    {
        return $this->roleRepo->forProject($project->id);
    }

    public function create(Project $project, array $data): ProjectRole
    {
        $existing = $this->roleRepo->forProject($project->id)
            ->firstWhere('role_name', $data['role_name']);

        if ($existing) {
            throw new ProjectException("Role '{$data['role_name']}' already exists on this project.", 409);
        }

        return $this->roleRepo->create($project->id, $data);
    }

    public function update(Project $project, string $roleId, array $data): ProjectRole
    {
        $role = $this->resolveRole($project, $roleId);

        // Prevent duplicate role names when renaming
        if (isset($data['role_name']) && $data['role_name'] !== $role->role_name) {
            $duplicate = $this->roleRepo->forProject($project->id)
                ->firstWhere('role_name', $data['role_name']);

            if ($duplicate) {
                throw new ProjectException("Role '{$data['role_name']}' already exists on this project.", 409);
            }
        }

        return $this->roleRepo->update($role, $data);
    }

    public function delete(Project $project, string $roleId): void
    {
        $role = $this->resolveRole($project, $roleId);

        if ($role->positions_filled > 0) {
            throw new ProjectException('Cannot delete a role that has filled positions.', 422);
        }

        $this->roleRepo->delete($role);
    }

    private function resolveRole(Project $project, string $roleId): ProjectRole
    {
        $role = $this->roleRepo->findById($roleId);

        if (!$role || $role->project_id !== $project->id) {
            throw new ProjectException('Project role not found.', 404);
        }

        return $role;
    }
}
