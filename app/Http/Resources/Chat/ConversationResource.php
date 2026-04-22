<?php

namespace App\Http\Resources\Chat;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'conversation_type'  => $this->conversation_type,
            'title'              => $this->title,
            'project_id'         => $this->project_id,
            'project'            => $this->when($this->project, fn() => [
                'id'    => $this->project->id,
                'title' => $this->project->title,
                'slug'  => $this->project->slug,
            ]),
            'creator'            => [
                'id'                  => $this->creator->id,
                'username'            => $this->creator->username,
                'full_name'           => $this->creator->full_name,
                'profile_picture_url' => $this->creator->profile_picture_url,
            ],
            'participants'       => ParticipantResource::collection($this->whenLoaded('activeParticipants')),
            'participants_count' => $this->activeParticipants_count ?? $this->activeParticipants->count(),
            'latest_message'     => $this->when($this->latestMessage, fn() => new MessageResource($this->latestMessage)),
            'last_message_at'    => $this->last_message_at?->toISOString(),
            'created_at'         => $this->created_at?->toISOString(),
            'updated_at'         => $this->updated_at?->toISOString(),
        ];
    }
}
