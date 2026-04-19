<?php

namespace App\Exceptions\Auth;

use RuntimeException;

class AccountRestrictedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Your account has been restricted by an administrator. Please contact support.');
    }
}
