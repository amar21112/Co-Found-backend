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

            // Bare room URL — only visible to existing participants.
            // This alone is NOT enough to enter the room on a JWT-secured
            // Jitsi instance. The frontend must use join_token instead.
            'room_url'         => $isPartic ? $this->room_url : null,

            // Short-lived Jitsi JWT — present only on the /join response.
            // Minted by VideoCallService::join() and stored as a transient
            // attribute on the model (never persisted to the database).
            // The frontend constructs the final URL as:
            //   {room_url}?jwt={join_token}
            'join_token'       => $this->when(
                isset($this->resource->join_token),
                fn() => $this->resource->join_token
            ),

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

            'participants' => CallParticipantResource::collection(
                $this->whenLoaded('participants')
            ),

            'active_participants_count' => $this->whenLoaded(
                'participants',
                fn() => $this->participants->whereNull('left_at')->count()
            ),
        ];
    }
}
