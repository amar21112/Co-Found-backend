<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvitationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'invitation_type'  => $this->invitation_type,
            'role'             => $this->role,
            'message'          => $this->message,
            'status'           => $this->status,
            'expires_at'       => $this->expires_at,
            'responded_at'     => $this->responded_at,
            'response_message' => $this->response_message,
            'sender'           => new UserResource($this->whenLoaded('sender')),
            'recipient'        => new UserResource($this->whenLoaded('recipient')),
            'project'          => $this->whenLoaded('project'),
            'created_at'       => $this->created_at,
            'updated_at'       => $this->updated_at,
        ];
    }
}
