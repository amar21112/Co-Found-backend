<?php

namespace App\Exceptions;

class DirectConversationExistsException extends ChatException
{
    public function __construct()
    {
        parent::__construct('A direct conversation with this user already exists.', 409);
    }
}
