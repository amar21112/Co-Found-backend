<?php

namespace App\Repositories\Eloquent;

use App\Models\File;
use App\Models\SharedFile;
use App\Repositories\Contracts\FileRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FileRepository implements FileRepositoryInterface
{
    public function findById(string $id): File
    {
        return File::with('uploader:id,username,full_name,profile_picture_url')->find($id);
    }

    public function create(array $data): File
    {
        return File::create($data);
    }

    public function delete(File $file): void
    {
        $file->delete();
    }

    public function shareInConversation(string $fileId, string $conversationId, string $sharedBy, array $options = []): SharedFile
    {
        return SharedFile::create([
            'file_id'          => $fileId,
            'conversation_id'  => $conversationId,
            'message_id'       => $options['message_id'] ?? null,
            'shared_by'        => $sharedBy,
            'permission_level' => $options['permission_level'] ?? 'view',
            'expires_at'       => $options['expires_at'] ?? null,
        ]);
    }

    public function paginateSharedInConversation(string $conversationId, int $perPage): LengthAwarePaginator
    {
        return SharedFile::where('conversation_id', $conversationId)
            ->with([
                'file.uploader:id,username,full_name,profile_picture_url',
                'sharedBy:id,username,full_name',
            ])
            ->latest()
            ->paginate($perPage);
    }
}
