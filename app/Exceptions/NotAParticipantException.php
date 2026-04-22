<?php

namespace App\Exceptions;

class NotAParticipantException extends ChatException
{
    public function __construct()
    {
        parent::__construct('You are not a participant of this conversation.', 403);
    }
}
