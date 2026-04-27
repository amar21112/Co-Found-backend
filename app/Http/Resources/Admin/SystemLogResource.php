<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SystemLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'log_level'  => $this->log_level,
            'component'  => $this->component,
            'event_type' => $this->event_type,
            'message'    => $this->message,
            'details'    => $this->details,
            'ip_address' => $this->ip_address,
            'created_at' => $this->created_at?->toISOString(),
            'user'       => $this->whenLoaded('user', fn() =>
                $this->user ? [
                    'id'       => $this->user->id,
                    'username' => $this->user->username,
                    'full_name'=> $this->user->full_name,
                ] : null
            ),
        ];
    }
}
