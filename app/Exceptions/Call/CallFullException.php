<?php

namespace App\Exceptions\Call;

use RuntimeException;

class CallFullException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This call is full. All available slots are currently occupied.');
    }
}
