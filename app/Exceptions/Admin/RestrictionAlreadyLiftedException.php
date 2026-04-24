<?php

namespace App\Exceptions\Admin;

use RuntimeException;

class RestrictionAlreadyLiftedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This restriction has already been lifted.');
    }
}
