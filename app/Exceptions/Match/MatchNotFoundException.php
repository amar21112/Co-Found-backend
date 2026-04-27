<?php

namespace App\Exceptions\Match;

use RuntimeException;

class MatchNotFoundException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Match not found.');
    }
}
