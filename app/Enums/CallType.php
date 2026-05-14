<?php

namespace App\Enums;

enum CallType: string
{
    case Conversation = 'conversation'; // 1-to-1 or group conversation call
    case Project      = 'project';      // project team call
}
