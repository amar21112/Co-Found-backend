<?php

namespace App\Exceptions\Auth;

use RuntimeException;

class InvalidVerificationTokenException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The verification token is invalid or has expired.');
    }
}
