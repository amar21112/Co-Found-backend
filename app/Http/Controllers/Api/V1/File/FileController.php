<?php

namespace App\Http\Controllers\Api\V1\File;

use App\Http\Controllers\Controller;
use App\Http\Requests\File\ShareFileRequest;
use App\Http\Resources\File\FileResource;
use App\Http\Resources\File\SharedFileResource;
use App\Models\File;
use App\Services\File\FileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FileController extends Controller
{
    public function __construct(
        private readonly FileService $service,
    ) {}

    /**
     * POST /api/v1/files
     * Upload a file to local storage. Returns the file metadata.
     * The file is NOT shared anywhere until a shareInConversation call.
     */
    public function upload(Request $request): JsonResponse
    {
        try{
            $request->validate([
                'file' => 'required|file|max:51200', // 50 MB
            ]);
        }catch (\Exception $e){
            return response()->json([
                'message' => 'File validation failed.',
                'data'    => $e->getMessage(),
            ], 422);
        }


        $file = $this->service->upload(
            uploader:     $request->user(),
            uploadedFile: $request->file('file'),
        );

        return response()->json([
            'message' => 'File uploaded.',
            'data'    => new FileResource($file),
        ], 201);
    }

    /**
     * GET /api/v1/files/{file}
     * Get file metadata.
     */
    public function show(File $file): JsonResponse
    {
        return response()->json([
            'data' => new FileResource($file),
        ]);
    }

    /**
     * DELETE /api/v1/files/{file}
     * Delete own file from storage and MySQL.
     */
    public function destroy(Request $request, File $file): JsonResponse
    {
        $this->authorize('delete', $file);

        $this->service->delete($request->user(), $file);

        return response()->json(['message' => 'File deleted.'] ,200);
    }

    /**
     * GET /api/v1/conversations/{conversationId}/files
     * List files shared in a conversation.
     */
    public function indexShared(Request $request, string $conversationId): AnonymousResourceCollection
    {
        $files = $this->service->listSharedInConversation(
            user:           $request->user(),
            conversationId: $conversationId,
            perPage:        (int) $request->input('per_page', 20),
        );

        return SharedFileResource::collection($files);
    }

    /**
     * POST /api/v1/conversations/{conversationId}/files
     * Share an uploaded file into a conversation.
     */
    public function share(ShareFileRequest $request, string $conversationId): JsonResponse
    {
        $shared = $this->service->shareInConversation(
            sharer:         $request->user(),
            conversationId: $conversationId,
            fileId:         $request->validated('file_id'),
            options:        $request->only(['message_id', 'permission_level', 'expires_at']),
        );

        return response()->json([
            'message' => 'File shared.',
            'data'    => new SharedFileResource($shared),
        ], 201);
    }
}
