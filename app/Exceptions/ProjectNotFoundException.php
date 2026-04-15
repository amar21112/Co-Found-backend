<?php

namespace App\Exceptions;

class ProjectNotFoundException extends ProjectException
{
    public function __construct()
    {
        parent::__construct('Project not found.', 404);
    }
}
