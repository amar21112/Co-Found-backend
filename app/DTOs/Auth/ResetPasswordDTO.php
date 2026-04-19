<?php

namespace App\DTOs\Auth;

final readonly class ResetPasswordDTO
{
    public function __construct(
        public string $token,
        public string $password,
    ) {}
}
