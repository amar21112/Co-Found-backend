<?php

namespace App\Repositories\Contracts;

use App\DTOs\Call\InitiateCallDTO;
use App\Models\CallParticipant;
use App\Models\User;
use App\Models\VideoCall;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface VideoCallRepositoryInterface
{
    public function findById(string $id): ?VideoCall;

    public function findActiveByRoomName(string $roomName): ?VideoCall;

    public function paginateForUser(string $userId, array $filters, int $perPage): LengthAwarePaginator;

    public function create(User $initiator, InitiateCallDTO $dto, string $roomName, string $roomUrl): VideoCall;

    public function addParticipant(VideoCall $call, User $user, string $role, string $jti): CallParticipant;

    public function rejoinParticipant(CallParticipant $participant, string $jti): CallParticipant;

    public function updateParticipantJti(CallParticipant $participant, string $jti): void;

    public function markParticipantLeft(CallParticipant $participant): CallParticipant;

    public function findParticipant(VideoCall $call, string $userId): ?CallParticipant;

    public function activeParticipantCount(VideoCall $call): int;

    public function updateStatus(VideoCall $call, string $status): VideoCall;

    public function endCall(VideoCall $call): VideoCall;

    public function cancelCall(VideoCall $call): VideoCall;
}
