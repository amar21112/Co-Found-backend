<?php

namespace App\DTOs\Auth;

final readonly class RegisterDTO
{
    public function __construct(
        public string $email,
        public string $username,
        public string $password,
        public string $fullName,
    ) {}
}
