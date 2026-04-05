<?php

namespace App\Exceptions;

class ApplicationAlreadyExistsException extends ProjectException
{
    public function __construct()
    {
        parent::__construct('You have already applied to this project.', 409);
    }
}
