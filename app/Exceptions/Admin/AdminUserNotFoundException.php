<?php

namespace App\Exceptions\Admin;

use RuntimeException;

class AdminUserNotFoundException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('User not found.');
    }
}
