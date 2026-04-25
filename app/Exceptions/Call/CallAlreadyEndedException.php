<?php

namespace App\Exceptions\Call;

use RuntimeException;

class CallAlreadyEndedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This call has already ended or been cancelled.');
    }
}
