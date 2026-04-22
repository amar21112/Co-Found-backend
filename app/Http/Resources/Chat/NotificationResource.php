<?php

namespace App\Http\Resources\Chat;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'type'         => $this->type,
            'title'        => $this->title,
            'body'         => $this->body,
            'data'         => $this->data,
            'priority'     => $this->priority,
            'read'         => $this->read,
            'read_at'      => $this->read_at?->toISOString(),
            'delivered_at' => $this->delivered_at?->toISOString(),
            'created_at'   => $this->created_at?->toISOString(),
        ];
    }
}
