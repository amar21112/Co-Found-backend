<?php

namespace App\Exceptions\Verification;

use RuntimeException;

class DuplicateIdentityCardException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(
            'This identity document is already associated with another verified account. ' .
            'Each ID card can only be used to verify one account. ' .
            'If you believe this is an error, please contact support.'
        );
    }
}
