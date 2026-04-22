<?php

namespace App\Services\Project;

use App\Exceptions\CannotLeaveProjectException;
use App\Exceptions\ProjectException;
use App\Models\Project;
use App\Models\ProjectTeamMember;
use App\Models\User;
use App\Repositories\Contracts\ProjectTeamRepositoryInterface;
use App\Traits\SendsNotifications;
use Illuminate\Support\Collection;

class ProjectTeamService
{
    use SendsNotifications;
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

        // Notify the removed member
        $this->notify(
            userId:   $userId,
            type:     'removed_from_project',
            title:    'Removed from project',
            body:     "You have been removed from \u201c{$project->title}\u201d.",
            data:     ['project_id' => $project->id],
            priority: 'high',
        );
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

        // Notify the project owner that the member left
        $this->notify(
            userId:   $project->owner_id,
            type:     'member_left_project',
            title:    'Team member left',
            body:     "{$user->full_name} left \u201c{$project->title}\u201d.",
            data:     ['project_id' => $project->id, 'user_id' => $user->id],
            priority: 'normal',
        );
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
