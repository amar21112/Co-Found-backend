<?php

namespace App\Exceptions;

class CannotEditMessageException extends ChatException
{
    public function __construct()
    {
        parent::__construct('You can only edit your own messages.', 403);
    }
}
