<?php

namespace App\Exceptions\Call;

use RuntimeException;

class NotACallParticipantException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('You are not a participant in this call.');
    }
}
