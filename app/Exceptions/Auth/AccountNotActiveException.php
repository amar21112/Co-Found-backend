<?php

namespace App\Exceptions\Auth;

use RuntimeException;

class AccountNotActiveException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Your account is not active. Please verify your email to continue.');
    }
}
