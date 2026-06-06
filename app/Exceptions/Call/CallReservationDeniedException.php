<?php

namespace App\Exceptions\Call;

use RuntimeException;

class CallReservationDeniedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('No active call found for this room.');
    }
}
