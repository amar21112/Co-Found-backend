<?php

namespace App\Http\Controllers\Api\V1\Chat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\AddReactionRequest;
use App\Http\Requests\Chat\EditMessageRequest;
use App\Http\Requests\Chat\SendMessageRequest;
use App\Http\Resources\Chat\MessageResource;
use App\Models\Conversation;
use App\Models\Message;
use App\Repositories\Contracts\MessageRepositoryInterface;
use App\Services\Chat\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MessageController extends Controller
{
    public function __construct(
        private readonly MessageService             $service,
    ) {}

    /**
     * GET /api/v1/conversations/{conversation}/messages
     * Paginate messages (cursor-based via `before` message ID for infinite scroll).
     */
    public function index(Request $request, Conversation $conversation): AnonymousResourceCollection
    {
        $messages = $this->service->list(
            user:         $request->user(),
            conversation: $conversation,
            filters:      $request->only(['before']),
            perPage:      (int) $request->input('per_page', 30),
        );

        return MessageResource::collection($messages);
    }

    /**
     * POST /api/v1/conversations/{conversation}/messages
     * Send a message. Syncs to Firebase RTDB for real-time delivery.
     */
    public function store(SendMessageRequest $request, Conversation $conversation): JsonResponse
    {
        $message = $this->service->send(
            sender:       $request->user(),
            conversation: $conversation,
            data:         $request->validated(),
        );

        return response()->json([
            'message' => 'Message sent.',
            'data'    => new MessageResource($message),
        ], 201);
    }

    /**
     * PUT /api/v1/conversations/{conversation}/messages/{message}
     * Edit own message. Updates RTDB node so clients see the change instantly.
     */
    public function update(EditMessageRequest $request, Conversation $conversation, Message $message): JsonResponse
    {
        $this->authorize('update', $message);

        $updated = $this->service->edit(
            editor:  $request->user(),
            message: $message,
            newContent: $request->validated('content'),
        );

        return response()->json([
            'message' => 'Message updated.',
            'data'    => new MessageResource($updated),
        ]);
    }

    /**
     * DELETE /api/v1/conversations/{conversation}/messages/{message}
     * Soft-delete own message. RTDB node content is cleared but node preserved.
     */
    public function destroy(Request $request, Conversation $conversation, Message $message): JsonResponse
    {
        $this->authorize('delete', $message);

        $this->service->delete($request->user(), $message);

        return response()->json(['message' => 'Message deleted.']);
    }

    /**
     * PATCH /api/v1/conversations/{conversation}/messages/{message}/pin
     * Pin or unpin a message. Admin/creator only.
     */
    public function pin(Request $request, Conversation $conversation, Message $message): JsonResponse
    {
        $request->validate(['is_pinned' => 'required|boolean']);

        $updated = $this->service->pin(
            actor:        $request->user(),
            conversation: $conversation,
            message:      $message,
            pin:          (bool) $request->input('is_pinned'),
        );

        return response()->json([
            'message' => $updated->is_pinned ? 'Message pinned.' : 'Message unpinned.',
            'data'    => new MessageResource($updated),
        ]);
    }

    /**
     * POST /api/v1/conversations/{conversation}/messages/{message}/read
     * Mark a single message as read. Updates RTDB read_count on the message node.
     */
    public function markRead(Request $request, Conversation $conversation, Message $message): JsonResponse
    {
        $this->service->markRead($request->user(), $message);

        return response()->json(['message' => 'Message marked as read.']);
    }

    /**
     * POST /api/v1/conversations/{conversation}/read-all
     * Mark all messages in a conversation as read.
     */
    public function markAllRead(Request $request, Conversation $conversation): JsonResponse
    {
        $this->service->markAllRead($request->user(), $conversation);

        return response()->json(['message' => 'All messages marked as read.']);
    }

    /**
     * POST /api/v1/conversations/{conversation}/messages/{message}/reactions
     * Add emoji reaction. RTDB reactions_summary refreshed instantly.
     */
    public function addReaction(AddReactionRequest $request, Conversation $conversation, Message $message): JsonResponse
    {
        $this->authorize('view', $conversation);

        $this->service->addReaction(
            user:     $request->user(),
            message:  $message,
            reaction: $request->validated('reaction'),
        );

        return response()->json(['message' => 'Reaction added.']);
    }

    /**
     * DELETE /api/v1/conversations/{conversation}/messages/{message}/reactions
     * Remove own reaction.
     */
    public function removeReaction(Request $request, Conversation $conversation, Message $message): JsonResponse
    {
        $request->validate(['reaction' => 'required|string|max:50']);

        $this->authorize('view', $conversation);

        $this->service->removeReaction(
            user:     $request->user(),
            message:  $message,
            reaction: $request->input('reaction'),
        );

        return response()->json(['message' => 'Reaction removed.']);
    }

    /**
     * POST /api/v1/conversations/{conversation}/typing
     * Set typing indicator in Firebase (ephemeral, no MySQL write).
     */
    public function typing(Request $request, Conversation $conversation): JsonResponse
    {
        $this->service->setTyping($request->user(), $conversation);

        return response()->json(['message' => 'Typing indicator set.']);
    }

    /**
     * DELETE /api/v1/conversations/{conversation}/typing
     * Clear typing indicator.
     */
    public function clearTyping(Request $request, Conversation $conversation): JsonResponse
    {
        $this->service->clearTyping($request->user(), $conversation);

        return response()->json(['message' => 'Typing indicator cleared.']);
    }
}
