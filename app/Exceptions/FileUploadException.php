<?php

namespace App\Exceptions;

class FileUploadException extends ChatException
{
    public function __construct(string $reason = 'File upload failed.')
    {
        parent::__construct($reason, 500);
    }
}
