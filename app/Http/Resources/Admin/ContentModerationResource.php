<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContentModerationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'content_type'        => $this->content_type,
            'content_id'          => $this->content_id,
            'moderation_type'     => $this->moderation_type,
            'original_content'    => $this->original_content,
            'moderated_content'   => $this->moderated_content,
            'action_taken'        => $this->action_taken,
            'reason'              => $this->reason,
            'guideline_referenced'=> $this->guideline_referenced,
            'created_at'          => $this->created_at?->toISOString(),
            'moderator'           => $this->whenLoaded('moderator', fn() => [
                'id'                  => $this->moderator->id,
                'username'            => $this->moderator->username,
                'full_name'           => $this->moderator->full_name,
                'profile_picture_url' => $this->moderator->profile_picture_url,
            ]),
        ];
    }
}
