<?php

namespace App\Repositories\Eloquent;

use App\DTOs\Call\InitiateCallDTO;
use App\Enums\CallParticipantRole;
use App\Enums\CallStatus;
use App\Models\CallParticipant;
use App\Models\User;
use App\Models\VideoCall;
use App\Repositories\Contracts\VideoCallRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class VideoCallRepository implements VideoCallRepositoryInterface
{
    public function findById(string $id): ?VideoCall
    {
        return VideoCall::with(['initiator', 'participants.user'])->find($id);
    }

    public function paginateForUser(string $userId, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = VideoCall::with(['initiator', 'participants.user'])
            ->where(function ($q) use ($userId) {
                // Calls the user initiated or participated in
                $q->where('initiated_by', $userId)
                    ->orWhereHas('participants', fn($p) => $p->where('user_id', $userId));
            })
            ->orderByDesc('created_at');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($perPage);
    }

    public function create(
        User            $initiator,
        InitiateCallDTO $dto,
        string          $roomName,
        string          $roomUrl
    ): VideoCall {
        return VideoCall::create([
            'call_type'       => $dto->callType->value,
            'conversation_id' => $dto->conversationId,
            'project_id'      => $dto->projectId,
            'initiated_by'    => $initiator->id,
            'room_name'       => $roomName,
            'room_url'        => $roomUrl,
            'start_time'      => $dto->startTime,
            'status'          => $dto->status->value,
        ]);
    }

    public function addParticipant(VideoCall $call, User $user, string $role): CallParticipant
    {
        return CallParticipant::create([
            'call_id'   => $call->id,
            'user_id'   => $user->id,
            'role'      => $role,
            'joined_at' => now(),
        ]);
    }

    public function rejoinParticipant(CallParticipant $participant): CallParticipant
    {
        $participant->update([
            'joined_at'        => now(),
            'left_at'          => null,
            'duration_seconds' => null,
        ]);

        return $participant->fresh();
    }

    public function markParticipantLeft(CallParticipant $participant): CallParticipant
    {
        $joinedAt = $participant->joined_at;
        $leftAt   = now();
        $duration = $joinedAt ? (int) $joinedAt->diffInSeconds($leftAt) : null;

        $participant->update([
            'left_at'          => $leftAt,
            'duration_seconds' => $duration,
        ]);

        return $participant->fresh();
    }

    public function findParticipant(VideoCall $call, string $userId): ?CallParticipant
    {
        return CallParticipant::where('call_id', $call->id)
            ->where('user_id', $userId)
            ->first();
    }

    public function updateStatus(VideoCall $call, string $status): VideoCall
    {
        $call->update(['status' => $status]);
        return $call->fresh(['initiator', 'participants.user']);
    }

    public function endCall(VideoCall $call): VideoCall
    {
        $endTime = now();

        // Mark any participants still in the call as left
        $activeParticipants = $call->activeParticipants()->get();
        foreach ($activeParticipants as $participant) {
            $this->markParticipantLeft($participant);
        }

        $startTime       = $call->start_time ?? $call->created_at;
        $durationSeconds = $startTime ? (int) $startTime->diffInSeconds($endTime) : null;

        $call->update([
            'status'           => CallStatus::Ended->value,
            'end_time'         => $endTime,
            'duration_seconds' => $durationSeconds,
        ]);

        return $call->fresh(['initiator', 'participants.user']);
    }

    public function cancelCall(VideoCall $call): VideoCall
    {
        $call->update([
            'status'  => CallStatus::Cancelled->value,
            'end_time'=> now(),
        ]);

        return $call->fresh(['initiator', 'participants.user']);
    }
}
