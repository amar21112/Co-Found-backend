<?php

namespace App\Repositories\Contracts;

use App\Models\File;
use App\Models\SharedFile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface FileRepositoryInterface
{
    public function findById(string $id): ?File;

    public function create(array $data): File;

    public function delete(File $file): void;

    public function shareInConversation(string $fileId, string $conversationId, string $sharedBy, array $options = []): SharedFile;

    public function paginateSharedInConversation(string $conversationId, int $perPage): LengthAwarePaginator;
}
