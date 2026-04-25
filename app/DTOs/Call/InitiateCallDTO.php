<?php

namespace App\DTOs\Call;

use App\Enums\CallStatus;
use App\Enums\CallType;

final readonly class InitiateCallDTO
{
    public function __construct(
        public CallType   $callType,
        public ?string    $conversationId,
        public ?string    $projectId,
        public ?string    $startTime,
        public CallStatus $status,
    ) {}
}
