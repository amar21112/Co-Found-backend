<?php

namespace App\Exceptions\Verification;

use RuntimeException;

class NoVerificationSubmittedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('You have not submitted an identity verification yet.');
    }
}
