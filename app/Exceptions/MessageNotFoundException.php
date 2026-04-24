<?php

namespace App\Exceptions;

class MessageNotFoundException extends ChatException
{
    public function __construct()
    {
        parent::__construct('Message not found.', 404);
    }
}
