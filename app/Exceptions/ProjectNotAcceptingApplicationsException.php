<?php

namespace App\Exceptions;

class ProjectNotAcceptingApplicationsException extends ProjectException
{
    public function __construct()
    {
        parent::__construct('This project is not currently accepting applications.', 422);
    }
}
