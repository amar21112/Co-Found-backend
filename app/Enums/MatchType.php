<?php

namespace App\Enums;

enum MatchType: string
{
    case Collaborator = 'collaborator';
    case Project      = 'project';

    public function isUserMatch(): bool
    {
        return $this === self::Collaborator;
    }

    public function isProjectMatch(): bool
    {
        return $this === self::Project;
    }
}
