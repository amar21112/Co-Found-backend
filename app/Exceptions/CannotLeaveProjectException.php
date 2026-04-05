<?php

namespace App\Exceptions;

class CannotLeaveProjectException extends ProjectException
{
    public function __construct(string $reason = 'You cannot leave this project.')
    {
        parent::__construct($reason, 422);
    }
}
