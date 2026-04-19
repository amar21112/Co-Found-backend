<?php

namespace App\Exceptions\Auth;

use RuntimeException;

class EmailAlreadyVerifiedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Email address has already been verified.');
    }
}
