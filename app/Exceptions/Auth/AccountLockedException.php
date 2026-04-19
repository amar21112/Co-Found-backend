<?php

namespace App\Exceptions\Auth;

use DateTimeInterface;
use RuntimeException;

class AccountLockedException extends RuntimeException
{
    public function __construct(public readonly DateTimeInterface $lockedUntil)
    {
        parent::__construct('Account is temporarily locked due to too many failed login attempts.');
    }
}
