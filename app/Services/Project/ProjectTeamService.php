<?php

namespace App\Services\Project;

use App\Exceptions\CannotLeaveProjectException;
use App\Exceptions\ProjectException;
use App\Models\Project;
use App\Models\ProjectTeamMember;
use App\Models\User;
use App\Repositories\Contracts\ProjectTeamRepositoryInterface;
use Illuminate\Support\Collection;

class ProjectTeamService
{
    public function __construct(
        private readonly ProjectTeamRepositoryInterface $teamRepo,
    ) {}

    public function list(Project $project, bool $activeOnly): Collection
    {
        return $this->teamRepo->forProject($project->id, $activeOnly);
    }

    public function updateMember(Project $project, string $userId, array $data): ProjectTeamMember
    {
        $member = $this->resolveMember($project, $userId);

        // Prevent demoting the owner
        if ($member->permissions === 'owner' && isset($data['permissions']) && $data['permissions'] !== 'owner') {
            throw new ProjectException('Cannot change the permissions of the project owner.', 422);
        }

        return $this->teamRepo->updateMember($member, $data);
    }

    public function removeMember(Project $project, string $userId): void
    {
        if ($userId === $project->owner_id) {
            throw new ProjectException('The project owner cannot be removed from the team.', 422);
        }

        $member = $this->resolveMember($project, $userId);
        $this->teamRepo->removeMember($member);
    }

    public function leave(Project $project, User $user): void
    {
        if ($user->id === $project->owner_id) {
            throw new CannotLeaveProjectException('The project owner cannot leave. Transfer ownership first.');
        }

        $member = $this->teamRepo->findMember($project->id, $user->id);

        if (!$member || !$member->is_active) {
            throw new CannotLeaveProjectException('You are not an active member of this project.');
        }

        $this->teamRepo->removeMember($member);
    }

    private function resolveMember(Project $project, string $userId): ProjectTeamMember
    {
        $member = $this->teamRepo->findMember($project->id, $userId);

        if (!$member || !$member->is_active) {
            throw new ProjectException('Team member not found.', 404);
        }

        return $member;
    }
}
