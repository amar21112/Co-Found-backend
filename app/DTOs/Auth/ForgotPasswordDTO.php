<?php

namespace App\DTOs\Auth;

final readonly class ForgotPasswordDTO
{
    public function __construct(
        public string $email,
    ) {}
}
