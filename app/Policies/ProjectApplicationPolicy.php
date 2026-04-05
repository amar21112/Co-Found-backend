<?php

namespace App\Policies;

use App\Models\ProjectApplication;
use App\Models\User;

class ProjectApplicationPolicy
{
    /**
     * Applicant or project owner can view a specific application.
     */
    public function view(User $user, ProjectApplication $application): bool
    {
        return $user->id === $application->applicant_id
            || $user->id === $application->project->owner_id;
    }

    /**
     * Only the applicant can withdraw their own application.
     */
    public function withdraw(User $user, ProjectApplication $application): bool
    {
        return $user->id === $application->applicant_id;
    }
}
