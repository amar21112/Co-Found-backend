<?php

namespace App\Http\Resources\Chat;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isDeleted = $this->trashed();

        return [
            'id'                    => $this->id,
            'conversation_id'       => $this->conversation_id,
            'sender'                => $this->when(!$isDeleted && $this->sender, fn() => [
                'id'                  => $this->sender->id,
                'username'            => $this->sender->username,
                'full_name'           => $this->sender->full_name,
                'profile_picture_url' => $this->sender->profile_picture_url,
            ]),
            'message_type'          => $this->message_type,
            'content'               => $isDeleted ? null : $this->content,
            'formatted_content'     => $isDeleted ? null : $this->formatted_content,
            'replied_to_message_id' => $this->replied_to_message_id,
            'is_pinned'             => $this->is_pinned,
            'is_edited'             => $this->is_edited,
            'is_deleted'            => $isDeleted,
            'reactions_summary'     => $this->when(!$isDeleted, fn() =>
                collect($this->reactions ?? [])
                    ->groupBy('reaction')
                    ->map(fn($group, $emoji) => [
                        'reaction' => $emoji,
                        'count'    => $group->count(),
                    ])
                    ->values()
            ),
            'read_count'            => $this->readReceipts?->count() ?? 0,
            'files'                 => SharedFileResource::collection($this->whenLoaded('sharedFiles')),
            'created_at'            => $this->created_at?->toISOString(),
            'updated_at'            => $this->updated_at?->toISOString(),
        ];
    }
}
