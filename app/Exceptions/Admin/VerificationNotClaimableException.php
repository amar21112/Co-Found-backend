<?php

namespace App\Exceptions\Admin;

use RuntimeException;

class VerificationNotClaimableException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This verification is not in a claimable state. Only pending or escalated verifications can be claimed.');
    }
}
