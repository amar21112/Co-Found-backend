<?php

namespace App\Exceptions\Call;

use RuntimeException;

class NotCallHostException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Only the call host can perform this action.');
    }
}
