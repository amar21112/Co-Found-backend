<?php

namespace App\Exceptions\Admin;

use RuntimeException;

class ReportNotFoundException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Report not found.');
    }
}
