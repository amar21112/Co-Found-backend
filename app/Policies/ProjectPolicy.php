<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    /**
     * Anyone authenticated can view public projects.
     * Private/unlisted projects require ownership or team membership.
     */
    public function view(User $user, Project $project): bool
    {
        if ($project->visibility === 'public') {
            return true;
        }

        return $this->isOwnerOrMember($user, $project);
    }

    /**
     * Only the project owner can update project settings.
     */
    public function update(User $user, Project $project): bool
    {
        return $project->owner_id === $user->id;
    }

    /**
     * Only the project owner can delete the project.
     */
    public function delete(User $user, Project $project): bool
    {
        return $project->owner_id === $user->id;
    }

    /**
     * Owner and team admins can manage skills, roles, milestones.
     */
    public function manage(User $user, Project $project): bool
    {
        if ($project->owner_id === $user->id) {
            return true;
        }

        return $project->teamMembers()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->whereIn('permissions', ['owner', 'admin'])
            ->exists();
    }

    /**
     * Owner and admins can manage team members.
     */
    public function manageTeam(User $user, Project $project): bool
    {
        return $this->manage($user, $project);
    }

    /**
     * Owner reviews applications.
     */
    public function reviewApplications(User $user, Project $project): bool
    {
        return $project->owner_id === $user->id;
    }

    /**
     * Any active member can view applications list.
     */
    public function viewApplications(User $user, Project $project): bool
    {
        return $project->owner_id === $user->id;
    }

    /**
     * User can apply if not already a member and project is accepting.
     */
    public function apply(User $user, Project $project): bool
    {
        if ($project->owner_id === $user->id) {
            return false; // Owner can't apply to own project
        }

        return $project->is_accepting_applications;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function isOwnerOrMember(User $user, Project $project): bool
    {
        if ($project->owner_id === $user->id) {
            return true;
        }

        return $project->teamMembers()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();
    }
}
