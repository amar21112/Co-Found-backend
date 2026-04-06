<?php

namespace App\Http\Controllers\Api\V1\Collaboration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Collaboration\StoreRatingRequest;
use App\Http\Requests\Collaboration\UpdateRatingRequest;
use App\Http\Resources\RatingResource;
use App\Models\CollaborationRating;
use App\Models\User;
use App\Services\RatingService;
use App\Traits\ResolvesUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    use ResolvesUser;

    public function __construct(private readonly RatingService $ratingService) {}

    // =========================================================================
    // GET /api/users/{user}/ratings
    // =========================================================================

    /**
     * List ratings received by a specific user.
     * Non-owners only see public ratings.
     */
    public function index(Request $request, User $user): JsonResponse
    {
        $viewer  = $this->resolveUser($request);
        $ratings = $this->ratingService->list($viewer, $user);

        return response()->json([
            'status' => 'success',
            'data'   => RatingResource::collection($ratings),
        ]);
    }

    // =========================================================================
    // POST /api/ratings
    // =========================================================================

    /**
     * Rate a collaborator.
     */
    public function store(StoreRatingRequest $request): JsonResponse
    {
        $rater  = $this->resolveUser($request);
        $rating = $this->ratingService->store($rater, $request->validated());

        return response()->json([
            'status'  => 'success',
            'message' => 'Rating submitted successfully.',
            'data'    => new RatingResource(
                $rating->load(['rater', 'ratedUser', 'project'])
            ),
        ], 201);
    }

    // =========================================================================
    // PUT /api/ratings/{rating}
    // =========================================================================

    /**
     * Update an existing rating.
     */
    public function update(UpdateRatingRequest $request, CollaborationRating $rating): JsonResponse
    {
        $rater   = $this->resolveUser($request);
        $updated = $this->ratingService->update($rater, $rating, $request->validated());

        return response()->json([
            'status'  => 'success',
            'message' => 'Rating updated successfully.',
            'data'    => new RatingResource($updated),
        ]);
    }

    // =========================================================================
    // DELETE /api/ratings/{rating}
    // =========================================================================

    /**
     * Delete a rating you submitted.
     */
    public function destroy(Request $request, CollaborationRating $rating): JsonResponse
    {
        $rater = $this->resolveUser($request);
        $this->ratingService->delete($rater, $rating);

        return response()->json([
            'status'  => 'success',
            'message' => 'Rating deleted successfully.',
        ]);
    }
}
