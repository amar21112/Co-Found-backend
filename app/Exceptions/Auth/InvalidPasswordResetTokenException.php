<?php

namespace App\Exceptions\Auth;

use RuntimeException;

class InvalidPasswordResetTokenException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The password reset token is invalid or has expired.');
    }
}
