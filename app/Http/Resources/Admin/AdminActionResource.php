<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminActionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'action_type' => $this->action_type,
            'target_type' => $this->target_type,
            'target_id'   => $this->target_id,
            'details'     => $this->details,
            'ip_address'  => $this->ip_address,
            'created_at'  => $this->created_at?->toISOString(),
            'admin'       => $this->whenLoaded('admin', fn() => [
                'id'                  => $this->admin->id,
                'username'            => $this->admin->username,
                'full_name'           => $this->admin->full_name,
                'profile_picture_url' => $this->admin->profile_picture_url,
            ]),
        ];
    }
}
