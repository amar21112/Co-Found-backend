<?php

namespace App\Http\Controllers\Api\V1\Verification;

use App\Http\Controllers\Controller;
use App\Http\Requests\Verification\SubmitVerificationRequest;
use App\Http\Resources\Verification\IdentityVerificationResource;
use App\Services\Verification\IdentityVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IdentityVerificationController extends Controller
{
    public function __construct(
        private readonly IdentityVerificationService $verificationService,
    ) {}

    // GET /api/v1/verification
    public function show(Request $request): JsonResponse
    {
        $verification = $this->verificationService->show($request->user());

        return response()->json([
            'status' => 'success',
            'data'   => new IdentityVerificationResource($verification),
        ]);
    }

    // POST /api/v1/verification
    public function submit(SubmitVerificationRequest $request): JsonResponse
    {
        $verification = $this->verificationService->submit(
            user: $request->user(),
            dto:  $request->getDto(),
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Identity verification submitted successfully. Our team will review it shortly.',
            'data'    => new IdentityVerificationResource($verification),
        ], 201);
    }
}
