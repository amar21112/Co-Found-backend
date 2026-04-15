<?php

namespace App\Enums;

enum ProjectVisibility: string
{
    case Public   = 'public';
    case Private  = 'private';
    case Unlisted = 'unlisted';
}
