<?php

namespace App\Http\Resources\Call;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VideoCallResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $viewer   = $request->user();
        $isPartic = $viewer && $this->hasParticipant($viewer->id);

        return [
            'id'               => $this->id,
            'call_type'        => $this->call_type->value,
            'status'           => $this->status->value,
            'room_name'        => $this->room_name,

            // room_url only returned to active participants — prevents
            // non-participants from accessing the Jitsi room directly.
            'room_url'         => $isPartic ? $this->room_url : null,

            'start_time'       => $this->start_time?->toISOString(),
            'end_time'         => $this->end_time?->toISOString(),
            'duration_seconds' => $this->duration_seconds,
            'recording_url'    => $this->recording_url,
            'created_at'       => $this->created_at?->toISOString(),

            'conversation_id'  => $this->conversation_id,
            'project_id'       => $this->project_id,

            'initiator' => $this->whenLoaded('initiator', fn() => [
                'id'                  => $this->initiator->id,
                'username'            => $this->initiator->username,
                'full_name'           => $this->initiator->full_name,
                'profile_picture_url' => $this->initiator->profile_picture_url,
            ]),

            'participants'         => CallParticipantResource::collection(
                $this->whenLoaded('participants')
            ),
            'active_participants_count' => $this->whenLoaded(
                'participants',
                fn() => $this->participants->whereNull('left_at')->count()
            ),
        ];
    }
}
