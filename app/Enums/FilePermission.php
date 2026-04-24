<?php

namespace App\Enums;

enum FilePermission: string
{
    case View     = 'view';
    case Download = 'download';
    case Edit     = 'edit';
}
