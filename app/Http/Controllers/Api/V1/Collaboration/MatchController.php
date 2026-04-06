<?php

namespace App\Http\Controllers\Api\V1\Collaboration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Collaboration\SubmitFeedbackRequest;
use App\Http\Resources\MatchResource;
use App\Models\MatchModel;
use App\Services\MatchService;
use App\Traits\ResolvesUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MatchController extends Controller
{
    use ResolvesUser;

    public function __construct(private readonly MatchService $matchService) {}

    // =========================================================================
    // GET /api/matches
    // =========================================================================

    /**
     * List all matches for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user    = $this->resolveUser($request);
        $matches = $this->matchService->list($user);

        return response()->json([
            'status' => 'success',
            'data'   => MatchResource::collection($matches),
        ]);
    }

    // =========================================================================
    // PATCH /api/matches/{match}/view
    // =========================================================================

    /**
     * Mark a match as viewed.
     */
    public function markViewed(Request $request, MatchModel $match): JsonResponse
    {
        $user  = $this->resolveUser($request);
        $match = $this->matchService->markViewed($user, $match);

        return response()->json([
            'status'  => 'success',
            'message' => 'Match marked as viewed.',
            'data'    => new MatchResource($match),
        ]);
    }

    // =========================================================================
    // PATCH /api/matches/{match}/save
    // =========================================================================

    /**
     * Toggle the saved state of a match.
     */
    public function save(Request $request, MatchModel $match): JsonResponse
    {
        $user  = $this->resolveUser($request);
        $match = $this->matchService->toggleSave($user, $match);

        $message = $match->saved ? 'Match saved successfully.' : 'Match unsaved successfully.';

        return response()->json([
            'status'  => 'success',
            'message' => $message,
            'data'    => new MatchResource($match),
        ]);
    }

    // =========================================================================
    // POST /api/matches/{match}/feedback
    // =========================================================================

    /**
     * Submit feedback for a match.
     */
    public function feedback(SubmitFeedbackRequest $request, MatchModel $match): JsonResponse
    {
        $user     = $this->resolveUser($request);
        $feedback = $this->matchService->submitFeedback($user, $match, $request->validated());

        return response()->json([
            'status'  => 'success',
            'message' => 'Feedback submitted successfully.',
            'data'    => [
                'id'            => $feedback->id,
                'match_id'      => $feedback->match_id,
                'feedback_type' => $feedback->feedback_type,
                'created_at'    => $feedback->created_at,
            ],
        ], 201);
    }
}
