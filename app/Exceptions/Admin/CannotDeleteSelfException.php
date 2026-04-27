<?php

namespace App\Exceptions\Admin;

use RuntimeException;

class CannotDeleteSelfException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Administrators cannot delete their own account through this endpoint.');
    }
}
