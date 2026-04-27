<?php

namespace App\Exceptions\Match;

use RuntimeException;

class FeedbackAlreadySubmittedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('You have already submitted feedback for this match.');
    }
}
