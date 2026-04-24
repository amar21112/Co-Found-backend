<?php

namespace App\Exceptions;

class ConversationNotFoundException extends ChatException
{
    public function __construct()
    {
        parent::__construct('Conversation not found.', 404);
    }
}
