<?php

namespace App\Enums;

enum MessageType: string
{
    case Text   = 'text';
    case File   = 'file';
    case System = 'system';
    case Poll   = 'poll';
}
