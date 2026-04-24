<?php

namespace App\Exceptions\Admin;

use RuntimeException;

class VerificationAlreadyReviewedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This verification has already been approved or rejected and cannot be reviewed again.');
    }
}
