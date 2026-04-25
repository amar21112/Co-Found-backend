<?php

namespace App\Exceptions\Call;

use RuntimeException;

class CallNotJoinableException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This call is not available to join.');
    }
}
