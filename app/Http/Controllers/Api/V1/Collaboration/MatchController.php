<?php

namespace App\Http\Controllers\Api\V1\Collaboration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Collaboration\SubmitFeedbackRequest;
use App\Http\Resources\MatchResource;
use App\Services\MatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MatchController extends Controller
{
    public function __construct(
        private readonly MatchService $matchService,
    ) {}

    // GET /api/v1/matches
    public function index(Request $request): JsonResponse
    {
        $matches = $this->matchService->list(
            user:    $request->user(),
            filters: $request->only([
                'match_type', 'viewed', 'saved',
                'min_score', 'sort_by', 'sort_dir', 'per_page',
            ]),
        );

        return response()->json([
            'status' => 'success',
            'data'   => MatchResource::collection($matches->items()),
            'meta'   => [
                'current_page' => $matches->currentPage(),
                'per_page'     => $matches->perPage(),
                'total'        => $matches->total(),
                'last_page'    => $matches->lastPage(),
            ],
        ]);
    }

    // PATCH /api/v1/matches/{id}/view
    public function view(Request $request, string $id): JsonResponse
    {
        $match   = $this->matchService->show($id, $request->user());
        $updated = $this->matchService->markViewed($request->user(), $match);

        return response()->json([
            'status'  => 'success',
            'message' => 'Match marked as viewed.',
            'data'    => new MatchResource($updated),
        ]);
    }

    // PATCH /api/v1/matches/{id}/save
    public function save(Request $request, string $id): JsonResponse
    {
        $match   = $this->matchService->show($id, $request->user());
        $updated = $this->matchService->toggleSave($request->user(), $match);

        return response()->json([
            'status'  => 'success',
            'message' => $updated->saved ? 'Match saved.' : 'Match unsaved.',
            'data'    => new MatchResource($updated),
        ]);
    }

    // POST /api/v1/matches/{id}/feedback
    public function feedback(SubmitFeedbackRequest $request, string $id): JsonResponse
    {
        $match    = $this->matchService->show($id, $request->user());
        $feedback = $this->matchService->submitFeedback(
            user:  $request->user(),
            match: $match,
            dto:   $request->getDto(),
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Feedback submitted. Thank you for helping us improve your matches.',
            'data'    => [
                'id'            => $feedback->id,
                'feedback_type' => $feedback->feedback_type->value,
                'created_at'    => $feedback->created_at?->toISOString(),
            ],
        ], 201);
    }
}
