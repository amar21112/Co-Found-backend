<?php

namespace App\Exceptions;

class ApplicationNotWithdrawableException extends ProjectException
{
    public function __construct()
    {
        parent::__construct('This application cannot be withdrawn in its current state.', 422);
    }
}
