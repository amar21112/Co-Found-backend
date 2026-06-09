<?php

namespace App\Services\Auth;

use App\DTOs\Auth\ForgotPasswordDTO;
use App\DTOs\Auth\ResetPasswordDTO;
use App\Exceptions\Auth\InvalidPasswordResetTokenException;
use App\Mail\Auth\ResetPasswordMail;
use App\Repositories\Contracts\PasswordResetRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordResetService
{
    /** Reset token validity in minutes. */
    private const TOKEN_TTL_MINUTES = 60;

    public function __construct(
        private readonly UserRepositoryInterface          $userRepository,
        private readonly PasswordResetRepositoryInterface $passwordResetRepository,
    ) {}

    // =========================================================================
    // Forgot Password
    // =========================================================================

    /**
     * Generate a password reset token and dispatch the email.
     *
     * Security decisions:
     * - Always returns silently regardless of whether the email exists —
     *   prevents user enumeration attacks.
     * - Deletes ALL existing reset tokens for the user (including valid ones)
     *   before issuing a new one. This prevents a scenario where an attacker
     *   with an old token races to use it after the user requests a new link.
     *   Only one valid reset token can exist per user at any time.
     */
    public function sendResetLink(ForgotPasswordDTO $dto): void
    {
        $user = $this->userRepository->findByEmail($dto->email);

        if (! $user) {
            // Silent return — security by design, no enumeration
            return;
        }

        // Invalidate ALL existing tokens (expired + valid) before issuing a new one.
        // This guarantees only one outstanding reset token per user at any time.
        $this->passwordResetRepository->deleteAllForUser($user);

        $token     = Str::random(64);
        $expiresAt = now()->addMinutes(self::TOKEN_TTL_MINUTES);

        $this->passwordResetRepository->createForUser($user, $token, $expiresAt);

        Mail::to($user->email)->send(new ResetPasswordMail($user, $token));

        Log::info('[Co-Found] Password reset token', [
            'user_id' => $user->id,
            'email'   => $user->email,
            'token'   => $token,
            'expires' => $expiresAt->toDateTimeString(),
        ]);
    }

    // =========================================================================
    // Reset Password
    // =========================================================================

    /**
     * Validate the reset token and update the user's password.
     *
     * On success:
     * - The token is marked as used (audit trail — we don't hard-delete it).
     * - All remaining reset tokens for the user are deleted (cleanup).
     * - All active Sanctum tokens are revoked — forces re-login on all devices,
     *   ensuring a compromised session cannot persist after a password change.
     */
    public function resetPassword(ResetPasswordDTO $dto): void
    {
        $reset = $this->passwordResetRepository->findValidToken($dto->token);

        if (! $reset) {
            throw new InvalidPasswordResetTokenException();
        }

        $user = $reset->user;

        $this->userRepository->update($user, [
            'password' => Hash::make($dto->password),
        ]);

        // Mark this specific token as used (keeps audit trail).
        $this->passwordResetRepository->markUsed($reset);

        // Delete all OTHER reset tokens for this user — exclude the one we just
        // marked used so the audit record is preserved for the session.
        $this->passwordResetRepository->deleteOtherTokensForUser($user, $reset->id);

        // Revoke all Sanctum tokens — force re-login on every device.
        $user->tokens()->delete();
    }
}
