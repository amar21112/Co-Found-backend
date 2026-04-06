<?php

namespace App\Http\Controllers\Api\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\StorePortfolioItemRequest;
use App\Http\Requests\Profile\UpdatePortfolioItemRequest;
use App\Http\Resources\PortfolioItemResource;
use App\Models\PortfolioItem;
use App\Models\User;
use App\Services\PortfolioService;
use App\Traits\ResolvesUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortfolioController extends Controller
{
    use ResolvesUser;

    public function __construct(private readonly PortfolioService $portfolioService) {}

    // =========================================================================
    // GET /api/profile/portfolio
    // =========================================================================

    /**
     * List the authenticated user's own portfolio items.
     */
    public function index(Request $request): JsonResponse
    {
        $user  = $this->resolveUser($request);
        $items = $this->portfolioService->listItems($user, $user);

        return response()->json([
            'status' => 'success',
            'data'   => PortfolioItemResource::collection($items),
        ]);
    }

    // =========================================================================
    // POST /api/profile/portfolio
    // =========================================================================

    /**
     * Create a new portfolio item for the authenticated user.
     */
    public function store(StorePortfolioItemRequest $request): JsonResponse
    {
        $user = $this->resolveUser($request);
        $item = $this->portfolioService->store($user, $request->validated());

        return response()->json([
            'status'  => 'success',
            'message' => 'Portfolio item created successfully.',
            'data'    => new PortfolioItemResource($item),
        ], 201);
    }

    // =========================================================================
    // PUT /api/profile/portfolio/{portfolioItem}
    // =========================================================================

    /**
     * Update an owned portfolio item.
     */
    public function update(UpdatePortfolioItemRequest $request, PortfolioItem $portfolioItem): JsonResponse
    {
        $user    = $this->resolveUser($request);
        $updated = $this->portfolioService->update($user, $portfolioItem, $request->validated());

        return response()->json([
            'status'  => 'success',
            'message' => 'Portfolio item updated successfully.',
            'data'    => new PortfolioItemResource($updated),
        ]);
    }

    // =========================================================================
    // DELETE /api/profile/portfolio/{portfolioItem}
    // =========================================================================

    /**
     * Delete an owned portfolio item.
     */
    public function destroy(Request $request, PortfolioItem $portfolioItem): JsonResponse
    {
        $user = $this->resolveUser($request);
        $this->portfolioService->delete($user, $portfolioItem);

        return response()->json(null, 204);
    }

    // =========================================================================
    // GET /api/users/{user}/portfolio
    // =========================================================================

    /**
     * View another user's public portfolio items.
     */
    public function showUserPortfolio(Request $request, User $user): JsonResponse
    {
        $viewer = $this->resolveUser($request);
        $items  = $this->portfolioService->listItems($viewer, $user);

        return response()->json([
            'status' => 'success',
            'data'   => PortfolioItemResource::collection($items),
        ]);
    }
}
