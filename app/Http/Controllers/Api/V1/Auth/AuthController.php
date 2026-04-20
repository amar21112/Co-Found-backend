<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\DTOs\Auth\AuthTokenDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Services\Auth\AuthService;
use App\Services\Auth\EmailVerificationService;
use App\Services\Auth\PasswordResetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService              $authService,
        private readonly EmailVerificationService $emailVerificationService,
        private readonly PasswordResetService     $passwordResetService,
    ) {}

    // =========================================================================
    // POST /api/v1/auth/register
    // =========================================================================

    public function register(RegisterRequest $request): JsonResponse
    {
        // The register route is public — Sanctum does not resolve $request->user()
        // automatically without auth:sanctum middleware. We manually check for a
        // valid bearer token so we can clean up an existing guest session if the
        // registering client was browsing as a guest.
        $guestUser = $this->resolveGuestFromToken($request);

        $result = $this->authService->register($request->getDto(), $guestUser);

        return response()->json([
            'status'  => 'success',
            'message' => 'Registration successful. Please check your email to verify your account.',
            'data'    => $this->tokenResponse($result),
        ], 201);
    }

    // =========================================================================
    // POST /api/v1/auth/login
    // =========================================================================

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            $request->getDto(),
            $request->ip(),
        );

        return response()->json([
            'status' => 'success',
            'data'   => $this->tokenResponse($result),
        ]);
    }

    // =========================================================================
    // POST /api/v1/auth/logout
    // =========================================================================

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'status'  => 'success',
            'message' => 'Logged out successfully.',
        ]);
    }

    // =========================================================================
    // GET /api/v1/auth/me
    // =========================================================================

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data'   => new UserResource($request->user()),
        ]);
    }

    // =========================================================================
    // POST /api/v1/auth/refresh
    // =========================================================================

    public function refresh(Request $request): JsonResponse
    {
        $result = $this->authService->refresh($request->user());

        return response()->json([
            'status' => 'success',
            'data'   => $this->tokenResponse($result),
        ]);
    }

    // =========================================================================
    // POST /api/v1/auth/guest
    // =========================================================================

    public function guest(): JsonResponse
    {
        $result = $this->authService->issueGuestToken();

        return response()->json([
            'status'  => 'success',
            'message' => 'Guest session started. Create an account to unlock all features.',
            'data'    => $this->tokenResponse($result),
        ], 201);
    }

    // =========================================================================
    // GET /api/v1/auth/email/verify/{token}
    // =========================================================================

    public function verifyEmail(string $token): JsonResponse
    {
        $user = $this->emailVerificationService->verify($token);

        return response()->json([
            'status'  => 'success',
            'message' => 'Email verified successfully. Your account is now active.',
            'data'    => new UserResource($user),
        ]);
    }

    // =========================================================================
    // POST /api/v1/auth/email/resend
    // =========================================================================

    public function resendVerification(Request $request): JsonResponse
    {
        $this->emailVerificationService->sendVerificationEmail($request->user());

        return response()->json([
            'status'  => 'success',
            'message' => 'Verification email sent. Please check your inbox.',
        ]);
    }

    // =========================================================================
    // POST /api/v1/auth/password/forgot
    // =========================================================================

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->passwordResetService->sendResetLink($request->getDto());

        // Always return 200 — no enumeration
        return response()->json([
            'status'  => 'success',
            'message' => 'If an account exists with that email, a reset link has been sent.',
        ]);
    }

    // =========================================================================
    // POST /api/v1/auth/password/reset
    // =========================================================================

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $this->passwordResetService->resetPassword($request->getDto());

        return response()->json([
            'status'  => 'success',
            'message' => 'Password reset successfully. Please log in with your new password.',
        ]);
    }

    // =========================================================================
    // Private Helpers
    // =========================================================================

    private function tokenResponse(AuthTokenDTO $dto): array
    {
        return [
            'access_token' => $dto->token,
            'token_type'   => $dto->tokenType,
            'user'         => new UserResource($dto->user),
        ];
    }

    /**
     * Attempt to resolve a guest User from the request's bearer token
     * without requiring auth:sanctum middleware on the route.
     *
     * Used only on the public register route: if the client was browsing
     * as a guest and sends their guest token alongside the registration
     * payload, we clean up the ephemeral guest row on success.
     *
     * Returns null when no token is present, the token is invalid/expired,
     * or the token belongs to a non-guest user.
     */
    private function resolveGuestFromToken(Request $request): ?User
    {
        $bearer = $request->bearerToken();

        if (! $bearer) {
            return null;
        }

        // Sanctum stores tokens hashed; find by the unhashed prefix (token ID)
        [$id] = explode('|', $bearer, 2);
        $token = PersonalAccessToken::find($id);

        if (! $token) {
            return null;
        }

        $user = $token->tokenable;

        if (! $user instanceof User || ! $user->isGuest()) {
            return null;
        }

        return $user;
    }
}
