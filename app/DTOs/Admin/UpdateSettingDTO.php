<?php

namespace App\DTOs\Admin;

final readonly class UpdateSettingDTO
{
    public function __construct(
        public mixed   $settingValue,
        public ?string $changeReason,
    ) {}
}
