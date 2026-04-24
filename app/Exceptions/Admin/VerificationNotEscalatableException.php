<?php

namespace App\Exceptions\Admin;

use RuntimeException;

class VerificationNotEscalatableException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Only verifications under review can be escalated.');
    }
}
