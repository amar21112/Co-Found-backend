<?php

namespace App\Enums;

enum CallParticipantRole: string
{
    case Host        = 'host';
    case Participant = 'participant';
}
