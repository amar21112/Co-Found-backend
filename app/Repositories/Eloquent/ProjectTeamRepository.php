<?php

namespace App\Repositories\Eloquent;

use App\Models\ProjectTeamMember;
use App\Repositories\Contracts\ProjectTeamRepositoryInterface;
use Illuminate\Support\Collection;

class ProjectTeamRepository implements ProjectTeamRepositoryInterface
{
    public function forProject(string $projectId, bool $activeOnly = true): Collection
    {
        $query = ProjectTeamMember::with(['user', 'role'])
            ->where('project_id', $projectId);

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->get();
    }

    public function findMember(string $projectId, string $userId): ?ProjectTeamMember
    {
        return ProjectTeamMember::where('project_id', $projectId)
            ->where('user_id', $userId)
            ->first();
    }

    public function addMember(string $projectId, string $userId, array $data): ProjectTeamMember
    {
        return ProjectTeamMember::create(array_merge($data, [
            'project_id' => $projectId,
            'user_id'    => $userId,
            'joined_at'  => now(),
            'is_active'  => true,
        ]));
    }

    public function updateMember(ProjectTeamMember $member, array $data): ProjectTeamMember
    {
        $member->update($data);
        return $member->fresh(['user', 'role']);
    }

    public function removeMember(ProjectTeamMember $member): void
    {
        $member->update(['is_active' => false, 'left_at' => now()]);
    }

    public function isMember(string $projectId, string $userId): bool
    {
        return ProjectTeamMember::where('project_id', $projectId)
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->exists();
    }

    public function isOwner(string $projectId, string $userId): bool
    {
        return ProjectTeamMember::where('project_id', $projectId)
            ->where('user_id', $userId)
            ->where('permissions', 'owner')
            ->where('is_active', true)
            ->exists();
    }
}
