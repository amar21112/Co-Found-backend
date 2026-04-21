<?php

namespace App\Http\Controllers\Api\V1\Chat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\CreateConversationRequest;
use App\Http\Requests\Chat\UpdateConversationRequest;
use App\Http\Resources\Chat\ConversationResource;
use App\Models\Conversation;
use App\Services\Chat\ConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ConversationController extends Controller
{
    public function __construct(
        private readonly ConversationService $service,
    ) {}

    /**
     * GET /api/v1/conversations
     * List the auth user's conversations, newest activity first.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $conversations = $this->service->list(
            user:    $request->user(),
            filters: $request->only(['type']),
            perPage: (int) $request->input('per_page', 20),
        );

        return ConversationResource::collection($conversations);
    }

    /**
     * POST /api/v1/conversations
     * Create a direct, group, or project conversation.
     */
    public function store(CreateConversationRequest $request): JsonResponse
    {
        $conversation = $this->service->create(
            creator: $request->user(),
            data:    $request->validated(),
        );

        return response()->json([
            'message' => 'Conversation created.',
            'data'    => new ConversationResource($conversation),
        ], 201);
    }

    /**
     * GET /api/v1/conversations/{conversation}
     * Show conversation detail with participants.
     */
    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $full = $this->service->show($request->user(), $conversation->id);

        return response()->json([
            'data' => new ConversationResource($full),
        ]);
    }

    /**
     * PATCH /api/v1/conversations/{conversation}
     * Update title or mute settings. Admin/creator only.
     */
    public function update(UpdateConversationRequest $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('update', $conversation);

        $updated = $this->service->update(
            user:         $request->user(),
            conversation: $conversation,
            data:         $request->validated(),
        );

        return response()->json([
            'message' => 'Conversation updated.',
            'data'    => new ConversationResource($updated),
        ]);
    }

    /**
     * POST /api/v1/conversations/{conversation}/participants
     * Add a participant to a group/project conversation.
     */
    public function addParticipant(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('manageParticipants', $conversation);

        $request->validate(['user_id' => 'required|uuid|exists:users,id']);

        $this->service->addParticipant(
            actor:        $request->user(),
            conversation: $conversation,
            userId:       $request->input('user_id'),
        );

        return response()->json(['message' => 'Participant added.'], 201);
    }

    /**
     * DELETE /api/v1/conversations/{conversation}/participants/{userId}
     * Remove a participant. Admin/creator only.
     */
    public function removeParticipant(Request $request, Conversation $conversation, string $userId): JsonResponse
    {
        $this->authorize('manageParticipants', $conversation);

        $this->service->removeParticipant(
            actor:        $request->user(),
            conversation: $conversation,
            userId:       $userId,
        );

        return response()->json(['message' => 'Participant removed.']);
    }

    /**
     * POST /api/v1/conversations/{conversation}/leave
     * Current user leaves the conversation.
     */
    public function leave(Request $request, Conversation $conversation): JsonResponse
    {
        $this->service->leave($request->user(), $conversation);

        return response()->json(['message' => 'You have left the conversation.']);
    }

    /**
     * GET /api/v1/firebase-token
     * Returns a Firebase custom token so the frontend can authenticate
     * to Firebase RTDB as the currently signed-in Laravel user.
     * Refresh this token every 55 minutes (Firebase tokens expire in 1 hour).
     */
    public function firebaseToken(Request $request): JsonResponse
    {
        $token = $this->service->getFirebaseToken($request->user());

        return response()->json(['firebase_token' => $token]);
    }
}
