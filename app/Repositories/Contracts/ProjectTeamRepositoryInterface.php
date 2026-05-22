<?php

namespace App\Repositories\Contracts;

use App\Models\ProjectTeamMember;
use Illuminate\Support\Collection;

interface ProjectTeamRepositoryInterface
{
    public function forProject(string $projectId, bool $activeOnly): Collection;

    public function findMember(string $projectId, string $userId): ?ProjectTeamMember;

    public function countActiveTeamMembers(string $projectId): int;

    public function addMember(string $projectId, string $userId, array $data): ProjectTeamMember;

    public function updateMember(ProjectTeamMember $member, array $data): ProjectTeamMember;

    public function removeMember(ProjectTeamMember $member): void;

    public function isMember(string $projectId, string $userId): bool;

    public function isOwner(string $projectId, string $userId): bool;
}
