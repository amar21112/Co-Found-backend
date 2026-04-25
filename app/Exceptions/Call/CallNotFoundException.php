<?php

namespace App\Exceptions\Call;

use RuntimeException;

class CallNotFoundException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Video call not found.');
    }
}
