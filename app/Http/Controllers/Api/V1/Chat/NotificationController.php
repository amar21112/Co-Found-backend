<?php

namespace App\Http\Controllers\Api\V1\Chat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\UpdateNotificationPreferencesRequest;
use App\Http\Resources\Chat\NotificationPreferenceResource;
use App\Http\Resources\Chat\NotificationResource;
use App\Services\Chat\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $service,
    ) {}

    /**
     * GET /api/v1/notifications
     * List notifications for the authenticated user.
     * Includes unread_count in the response envelope.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'read'     => 'nullable|boolean',
            'priority' => 'nullable|string|in:high,normal,low',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        ['paginator' => $paginator, 'unreadCount' => $unreadCount] = $this->service->list(
            user:    $request->user(),
            filters: $request->only(['read', 'priority']),
            perPage: (int) $request->input('per_page', 20),
        );

        $paginatedData = NotificationResource::collection($paginator)->response()->getData();

        return response()->json([
            'unread_count' => $unreadCount,
            'data'         => $paginatedData->data,      // ← Just the notifications array
            'meta'         => $paginatedData->meta,       // ← Pagination meta
            'links'        => $paginatedData->links,      // ← Pagination links
        ]);
    }

    /**
     * PATCH /api/v1/notifications/{id}/read
     * Mark a single notification as read. Syncs to Firebase.
     */
    public function markRead(Request $request, string $id): JsonResponse
    {
        $notification = $this->service->markRead($request->user(), $id);

        return response()->json([
            'message' => 'Notification marked as read.',
            'data'    => new NotificationResource($notification),
        ]);
    }

    /**
     * POST /api/v1/notifications/read-all
     * Mark all unread notifications as read. Bulk syncs to Firebase.
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $this->service->markAllRead($request->user());

        return response()->json(['message' => 'All notifications marked as read.']);
    }

    /**
     * GET /api/v1/notifications/preferences
     * Get the user's notification preferences.
     */
    public function preferences(Request $request): JsonResponse
    {
        $prefs = $this->service->getPreferences($request->user());

        return response()->json([
            'data' => $prefs ? new NotificationPreferenceResource($prefs) : null,
        ]);
    }

    /**
     * PUT /api/v1/notifications/preferences
     * Update notification preferences.
     */
    public function updatePreferences(UpdateNotificationPreferencesRequest $request): JsonResponse
    {
        $prefs = $this->service->updatePreferences(
            $request->user(),
            $request->validated(),
        );

        return response()->json([
            'message' => 'Preferences updated.',
            'data'    => new NotificationPreferenceResource($prefs),
        ]);
    }
}
