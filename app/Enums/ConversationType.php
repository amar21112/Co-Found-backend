<?php

namespace App\Enums;

enum ConversationType: string
{
    case Direct  = 'direct';
    case Group   = 'group';
    case Project = 'project';
}
