<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LogModerationActionRequest;
use App\Http\Resources\Admin\ContentModerationResource;
use App\Models\ContentModeration;
use App\Services\Admin\AdminModerationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminModerationController extends Controller
{
    public function __construct(
        private readonly AdminModerationService $moderationService,
    ) {}

    // GET /api/v1/admin/moderation

    /**
     * @throws AuthorizationException
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('moderate', ContentModeration::class);

        $actions = $this->moderationService->list(
            filters: $request->only(['content_type', 'moderation_type', 'action_taken', 'moderator_id']),
            perPage: min((int) $request->input('per_page', 15), 50),
        );

        return response()->json([
            'status' => 'success',
            'data'   => ContentModerationResource::collection($actions->items()),
            'meta'   => [
                'current_page' => $actions->currentPage(),
                'last_page'    => $actions->lastPage(),
                'per_page'     => $actions->perPage(),
                'total'        => $actions->total(),
                'from'         => $actions->firstItem(),
                'to'           => $actions->lastItem(),
            ],
            'links'  => [
                'first' => $actions->url(1),
                'last'  => $actions->url($actions->lastPage()),
                'prev'  => $actions->previousPageUrl(),
                'next'  => $actions->nextPageUrl(),
            ],
        ]);
    }

    // POST /api/v1/admin/moderation

    /**
     * @throws AuthorizationException
     */
    public function store(LogModerationActionRequest $request): JsonResponse
    {
        $this->authorize('moderate', ContentModeration::class);

        $moderation = $this->moderationService->log(
            dto:   $request->getDto($request->user()->id),
            admin: $request->user(),
            ip:    $request->ip(),
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Moderation action logged successfully.',
            'data'    => new ContentModerationResource($moderation),
        ], 201);
    }
}
