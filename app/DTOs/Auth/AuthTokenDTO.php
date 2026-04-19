<?php

namespace App\DTOs\Auth;

use App\Models\User;

final readonly class AuthTokenDTO
{
    public function __construct(
        public string $token,
        public string $tokenType,
        public User   $user,
    ) {}
}
