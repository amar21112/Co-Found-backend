<?php

namespace App\DTOs\Match;

final readonly class GenerateDatasetDTO
{
    public function __construct(
        public int  $users,
        public int  $projects,
        public int  $collaboratorPairs,
        public int  $projectPairs,
        public bool $fresh,
    ) {}
}
