<?php

namespace App\Repositories\Eloquent;

use App\Models\ContentModeration;
use App\Repositories\Contracts\AdminModerationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminModerationRepository implements AdminModerationRepositoryInterface
{
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = ContentModeration::with([
            'moderator:id,username,full_name,profile_picture_url',
        ])->orderByDesc('created_at');

        if (!empty($filters['content_type'])) {
            $query->where('content_type', $filters['content_type']);
        }

        if (!empty($filters['moderation_type'])) {
            $query->where('moderation_type', $filters['moderation_type']);
        }

        if (!empty($filters['action_taken'])) {
            $query->where('action_taken', $filters['action_taken']);
        }

        if (!empty($filters['moderator_id'])) {
            $query->where('moderator_id', $filters['moderator_id']);
        }

        return $query->paginate($perPage);
    }

    public function create(array $data): ContentModeration
    {
        return ContentModeration::create($data);
    }
}
