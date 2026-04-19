<?php

namespace App\Services\Auth;

use App\Exceptions\Auth\EmailAlreadyVerifiedException;
use App\Exceptions\Auth\InvalidVerificationTokenException;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EmailVerificationService
{
    /** Verification token validity in hours. */
    private const TOKEN_TTL_HOURS = 24;

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    // =========================================================================
    // Send / Resend
    // =========================================================================

    /**
     * Generate a fresh token and (re)send the verification email.
     *
     * Idempotent — safe to call on both registration and explicit resend
     * requests. Each call replaces the previous token, so old verification
     * links are immediately invalidated.
     *
     * Throws EmailAlreadyVerifiedException if the user's email is already
     * confirmed — prevents unnecessary email sends and gives the client a
     * clear error to show.
     */
    public function sendVerificationEmail(User $user): void
    {
        if ($user->isEmailVerified()) {
            throw new EmailAlreadyVerifiedException();
        }

        $token     = Str::random(64);
        $expiresAt = now()->addHours(self::TOKEN_TTL_HOURS);

        $this->userRepository->update($user, [
            'email_verification_token'   => $token,
            'email_verification_expires' => $expiresAt,
        ]);

        // TODO: swap with a proper Mailable once the mail module is built.
        // Mail::to($user->email)->send(new VerifyEmailMail($token));
        Log::info('[Co-Found] Email verification token', [
            'user_id' => $user->id,
            'email'   => $user->email,
            'token'   => $token,
            'expires' => $expiresAt->toDateTimeString(),
        ]);
    }

    // =========================================================================
    // Verify
    // =========================================================================

    /**
     * Consume a verification token and activate the user account.
     *
     * The repository query already enforces token expiry — if the token
     * is not found (expired or invalid), we throw a single generic error
     * to avoid distinguishing expired vs. non-existent tokens.
     */
    public function verify(string $token): User
    {
        $user = $this->userRepository->findByEmailVerificationToken($token);

        if (! $user) {
            throw new InvalidVerificationTokenException();
        }

        // Guard against double-submission (e.g. user clicks the link twice)
        if ($user->isEmailVerified()) {
            throw new EmailAlreadyVerifiedException();
        }

        return $this->userRepository->markEmailVerified($user);
    }
}
