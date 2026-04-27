<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReviewVerificationRequest;
use App\Http\Resources\Admin\IdentityVerificationDetailResource;
use App\Http\Resources\Admin\IdentityVerificationResource;
use App\Models\IdentityVerification;

use App\Services\Admin\AdminVerificationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminVerificationController extends Controller
{
    public function __construct(
        private readonly AdminVerificationService $verificationService,
    ) {}

    // GET /api/v1/admin/verifications

    /**
     * @throws AuthorizationException
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('moderate', IdentityVerification::class);

        $verifications = $this->verificationService->list(
            filters: $request->only(['status']),
            perPage: min((int) $request->input('per_page', 15), 50),
        );

        return response()->json([
            'status' => 'success',
            'data'   => IdentityVerificationResource::collection($verifications->items()),
            'meta'   => [
                'current_page' => $verifications->currentPage(),
                'per_page'     => $verifications->perPage(),
                'total'        => $verifications->total(),
                'last_page'    => $verifications->lastPage(),
            ],
        ]);
    }

    // GET /api/v1/admin/verifications/{id}

    /**
     * @throws AuthorizationException
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $this->authorize('moderate', IdentityVerification::class);

        $verification = $this->verificationService->show($id);

        return response()->json([
            'status' => 'success',
            'data'   => new IdentityVerificationResource($verification),
        ]);
    }

    // PATCH /api/v1/admin/verifications/{id}/claim

    /**
     * @throws AuthorizationException
     */
    public function claim(Request $request, string $id): JsonResponse
    {
        $this->authorize('moderate', IdentityVerification::class);

        $verification = $this->verificationService->show($id);

        $updated = $this->verificationService->claim(
            verification: $verification,
            moderator:    $request->user(),
            ip:           $request->ip(),
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Verification claimed. It is now marked as under review.',
            'data'    => new IdentityVerificationResource($updated),
        ]);
    }

    // PATCH /api/v1/admin/verifications/{id}/escalate

    /**
     * @throws AuthorizationException
     */
    public function escalate(Request $request, string $id): JsonResponse
    {
        $this->authorize('moderate', IdentityVerification::class);

        $verification = $this->verificationService->show($id);

        $updated = $this->verificationService->escalate(
            verification: $verification,
            moderator:    $request->user(),
            notes:        $request->input('notes'),
            ip:           $request->ip(),
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Verification escalated to administrator review.',
            'data'    => new IdentityVerificationResource($updated),
        ]);
    }

    // POST /api/v1/admin/verifications/{id}/review

    /**
     * @throws AuthorizationException
     */
    public function review(ReviewVerificationRequest $request, string $id): JsonResponse
    {
        $this->authorize('moderate', IdentityVerification::class);

        $verification = $this->verificationService->show($id);

        $updated = $this->verificationService->review(
            verification: $verification,
            reviewer:     $request->user(),
            dto:          $request->getDto(),
            ip:           $request->ip(),
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Verification reviewed successfully.',
            'data'    => new IdentityVerificationResource($updated),
        ]);
    }
}
