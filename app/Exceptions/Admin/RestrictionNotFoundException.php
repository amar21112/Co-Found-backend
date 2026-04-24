<?php

namespace App\Exceptions\Admin;

use RuntimeException;

class RestrictionNotFoundException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Restriction record not found.');
    }
}
