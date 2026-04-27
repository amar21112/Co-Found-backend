<?php

namespace App\DTOs\Admin;

final readonly class UpdateAdminUserDTO
{
    public function __construct(
        public ?string $role,
        public ?string $accountStatus,
    ) {}
}
