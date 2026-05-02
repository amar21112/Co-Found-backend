<?php

namespace App\Exceptions\Admin;

use RuntimeException;

class SettingNotFoundException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('System setting not found.');
    }
}
