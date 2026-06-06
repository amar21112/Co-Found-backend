<?php

namespace App\Exceptions\Call;

use RuntimeException;

class CallParticipantNotAllowedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This user is not allowed to join this call.');
    }
}
