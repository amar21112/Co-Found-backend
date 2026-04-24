<?php

namespace App\Http\Resources\Chat;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SharedFileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'file'             => new FileResource($this->file),
            'conversation_id'  => $this->conversation_id,
            'message_id'       => $this->message_id,
            'shared_by'        => $this->when($this->sharedBy, fn() => [
                'id'       => $this->sharedBy->id,
                'username' => $this->sharedBy->username,
                'full_name'=> $this->sharedBy->full_name,
            ]),
            'permission_level' => $this->permission_level,
            'expires_at'       => $this->expires_at?->toISOString(),
            'is_expired'       => $this->isExpired(),
            'created_at'       => $this->created_at?->toISOString(),
        ];
    }
}
