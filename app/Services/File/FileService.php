<?php

namespace App\Services\File;

use App\Exceptions\ChatException;
use App\Exceptions\FileUploadException;
use App\Firebase\FirebaseSyncService;
use App\Models\File;
use App\Models\User;
use App\Repositories\Contracts\ConversationRepositoryInterface;
use App\Repositories\Contracts\FileRepositoryInterface;
use App\Services\Chat\MessageService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileService
{
    use \App\Traits\FileUploadTrait;

    public function __construct(
        private readonly FileRepositoryInterface         $fileRepo,
        private readonly ConversationRepositoryInterface $conversationRepo,
        private readonly FirebaseSyncService             $firebase,
        private readonly MessageService                  $messageService,
    ) {}

    /**
     * Upload a file to storage and record it in MySQL.
     * Returns the File model — the caller decides where to share it.
     */
    public function upload(User $uploader, UploadedFile $uploadedFile): File
    {
        $hash = hash_file('sha256', $uploadedFile->getRealPath());

        // Deduplicate: if this exact file was uploaded before, reuse it
        $existing = \App\Models\File::where('file_hash', $hash)
            ->where('uploader_id', $uploader->id)
            ->first();

        if ($existing) return $existing;

        $extension = $uploadedFile->getClientOriginalExtension();
        $directory = 'uploads/' . $uploader->id;
        $filename = Str::uuid() . '.' . $extension;

        $storagePath = $this->uploadFile($uploadedFile, $directory, 'public', $filename);

        if (!$storagePath) {
            throw new FileUploadException('Could not store the file. Please try again.');
        }

        $file = $this->fileRepo->create([
            'uploader_id'      => $uploader->id,
            'file_name'        => $uploadedFile->getClientOriginalName(),
            'file_size'        => $uploadedFile->getSize(),
            'mime_type'        => $uploadedFile->getMimeType(),
            'storage_path'     => $storagePath,
            'public_url'       => $this->getFileUrl($storagePath, 'public'),
            'file_hash'        => $hash,
            'upload_completed' => true,
        ]);

        return $file;
    }

    /**
     * Share an already-uploaded file into a conversation.
     * Also sends a file-type message so it appears in the chat thread.
     */
    public function shareInConversation(
        User   $sharer,
        string $conversationId,
        string $fileId,
        array  $options = []
    ): \App\Models\SharedFile {
        $file = $this->fileRepo->findById($fileId);

        if (!$file) throw new ChatException('File not found.', 404);

        if (!$this->conversationRepo->isParticipant($conversationId, $sharer->id)) {
            throw new \App\Exceptions\NotAParticipantException();
        }

        if (empty($options['message_id'])) {
            $conversation = \App\Models\Conversation::findOrFail($conversationId);
            $message = $this->messageService->send($sharer, $conversation, [
                'message_type' => \App\Enums\MessageType::File->value,
                'content'      => $file->file_name ?? 'Shared a file',
            ]);
            $options['message_id'] = $message->id;
        }

        $shared = $this->fileRepo->shareInConversation(
            $fileId, $conversationId, $sharer->id, $options
        );

        return $shared->load(['file.uploader:id,username,full_name', 'sharedBy:id,username,full_name']);
    }

    public function listSharedInConversation(
        User   $user,
        string $conversationId,
        int    $perPage
    ): LengthAwarePaginator {
        if (!$this->conversationRepo->isParticipant($conversationId, $user->id)) {
            throw new \App\Exceptions\NotAParticipantException();
        }

        return $this->fileRepo->paginateSharedInConversation($conversationId, $perPage);
    }

    public function delete(User $deleter, File $file): void
    {
        if ($file->uploader_id !== $deleter->id) {
            throw new ChatException('You can only delete your own files.', 403);
        }

        $this->deleteFile($file->storage_path, 'public');
        $this->fileRepo->delete($file);
    }
}
