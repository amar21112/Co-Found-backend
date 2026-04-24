<?php

namespace App\Http\Resources\Chat;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'file_name'        => $this->file_name,
            'file_size'        => $this->file_size,
            'mime_type'        => $this->mime_type,
            'public_url'       => $this->public_url,
            'thumbnail_url'    => $this->thumbnail_url,
            'upload_completed' => $this->upload_completed,
            'uploader'         => $this->when($this->uploader, fn() => [
                'id'                  => $this->uploader->id,
                'username'            => $this->uploader->username,
                'full_name'           => $this->uploader->full_name,
                'profile_picture_url' => $this->uploader->profile_picture_url,
            ]),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
