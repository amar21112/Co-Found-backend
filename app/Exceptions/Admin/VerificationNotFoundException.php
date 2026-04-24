<?php

namespace App\Exceptions\Admin;

use RuntimeException;

class VerificationNotFoundException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Identity verification record not found.');
    }
}
