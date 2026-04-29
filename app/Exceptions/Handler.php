<?php

namespace App\Exceptions;

use App\Exceptions\Admin\AdminUserNotFoundException;
use App\Exceptions\Admin\CannotDeleteSelfException;
use App\Exceptions\Admin\ReportNotFoundException;
use App\Exceptions\Admin\RestrictionAlreadyLiftedException;
use App\Exceptions\Admin\RestrictionNotFoundException;
use App\Exceptions\Admin\SettingNotFoundException;
use App\Exceptions\Admin\VerificationAlreadyReviewedException;
use App\Exceptions\Admin\VerificationNotClaimableException;
use App\Exceptions\Admin\VerificationNotEscalatableException;
use App\Exceptions\Admin\VerificationNotFoundException;
use App\Exceptions\Auth\AccountLockedException;
use App\Exceptions\Auth\AccountNotActiveException;
use App\Exceptions\Auth\AccountRestrictedException;
use App\Exceptions\Auth\EmailAlreadyVerifiedException;
use App\Exceptions\Auth\InvalidCredentialsException;
use App\Exceptions\Auth\InvalidPasswordResetTokenException;
use App\Exceptions\Auth\InvalidVerificationTokenException;
use App\Exceptions\Call\CallAlreadyEndedException;
use App\Exceptions\Call\CallNotFoundException;
use App\Exceptions\Call\CallNotJoinableException;
use App\Exceptions\Call\NotACallParticipantException;
use App\Exceptions\Call\NotCallHostException;
use App\Exceptions\Match\FeedbackAlreadySubmittedException;
use App\Exceptions\Match\MatchNotFoundException;
use App\Exceptions\Verification\DuplicateIdentityCardException;
use App\Exceptions\Verification\NoVerificationSubmittedException;
use App\Exceptions\Verification\VerificationAlreadyExistsException;
use App\Exceptions\Verification\VerificationAttemptLimitException;
use DateTimeInterface;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * Exception types with custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [];

    /**
     * Exception types that should NOT be reported to the log.
     * These are all domain exceptions that result in clean HTTP responses.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        // Auth
        InvalidCredentialsException::class,
        AccountLockedException::class,
        AccountNotActiveException::class,
        AccountRestrictedException::class,
        EmailAlreadyVerifiedException::class,
        InvalidVerificationTokenException::class,
        InvalidPasswordResetTokenException::class,

        // Admin — verification
        VerificationNotFoundException::class,
        VerificationAlreadyReviewedException::class,
        VerificationNotClaimableException::class,
        VerificationNotEscalatableException::class,

        // Admin — restrictions
        RestrictionNotFoundException::class,
        RestrictionAlreadyLiftedException::class,

        // Admin — reports
        ReportNotFoundException::class,

        // Admin — users
        AdminUserNotFoundException::class,
        CannotDeleteSelfException::class,

        // Admin — settings
        SettingNotFoundException::class,

        // Call exceptions
        CallNotFoundException::class,
        CallAlreadyEndedException::class,
        CallNotJoinableException::class,
        NotACallParticipantException::class,
        NotCallHostException::class,
        // Match exceptions
        MatchNotFoundException::class,
        FeedbackAlreadySubmittedException::class,
        // Verification exceptions
        NoVerificationSubmittedException::class,
        VerificationAlreadyExistsException::class,
        VerificationAttemptLimitException::class,
        DuplicateIdentityCardException::class,
    ];

    /**
     * Inputs that should never be flashed to the session on validation errors.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register exception handling callbacks.
     */
    public function register(): void
    {
        // ── Auth exceptions ───────────────────────────────────────────────────

        $this->renderable(fn(InvalidCredentialsException $e) =>
            $this->error($e->getMessage(), 401)
        );

        $this->renderable(fn(AccountNotActiveException $e) =>
            $this->error($e->getMessage(), 403)
        );

        $this->renderable(fn(AccountRestrictedException $e) =>
            $this->error($e->getMessage(), 403)
        );

        $this->renderable(fn(AccountLockedException $e) =>
            response()->json([
                'status'       => 'error',
                'message'      => $e->getMessage(),
                'locked_until' => $e->lockedUntil->format(DateTimeInterface::ATOM),
            ], 423)
        );

        $this->renderable(fn(InvalidVerificationTokenException $e) =>
            $this->error($e->getMessage(), 400)
        );

        $this->renderable(fn(InvalidPasswordResetTokenException $e) =>
            $this->error($e->getMessage(), 400)
        );

        $this->renderable(fn(EmailAlreadyVerifiedException $e) =>
            $this->error($e->getMessage(), 409)
        );

        // ── Admin — verification exceptions ───────────────────────────────────

        $this->renderable(fn(VerificationNotFoundException $e) =>
            $this->error($e->getMessage(), 404)
        );

        $this->renderable(fn(VerificationAlreadyReviewedException $e) =>
            $this->error($e->getMessage(), 409)
        );

        $this->renderable(fn(VerificationNotClaimableException $e) =>
            $this->error($e->getMessage(), 409)
        );

        $this->renderable(fn(VerificationNotEscalatableException $e) =>
            $this->error($e->getMessage(), 409)
        );

        // ── Admin — restriction exceptions ────────────────────────────────────

        $this->renderable(fn(RestrictionNotFoundException $e) =>
            $this->error($e->getMessage(), 404)
        );

        $this->renderable(fn(RestrictionAlreadyLiftedException $e) =>
            $this->error($e->getMessage(), 409)
        );

        // ── Admin — report exceptions ─────────────────────────────────────────

        $this->renderable(fn(ReportNotFoundException $e) =>
            $this->error($e->getMessage(), 404)
        );

        // ── Admin — user exceptions ───────────────────────────────────────────

        $this->renderable(fn(AdminUserNotFoundException $e) =>
            $this->error($e->getMessage(), 404)
        );

        $this->renderable(fn(CannotDeleteSelfException $e) =>
            $this->error($e->getMessage(), 422)
        );

        // ── Admin — setting exceptions ────────────────────────────────────────

        $this->renderable(fn(SettingNotFoundException $e) =>
            $this->error($e->getMessage(), 404)
        );

        // ── Call Exceptions ───────────────────────────────────────────────────

        $this->renderable(fn(CallNotFoundException $e) =>
            $this->error($e->getMessage(), 404)
        );

        $this->renderable(fn(CallAlreadyEndedException $e) =>
            $this->error($e->getMessage(), 409)
        );

        $this->renderable(fn(CallNotJoinableException $e) =>
            $this->error($e->getMessage(), 409)
        );

        $this->renderable(fn(NotACallParticipantException $e) =>
            $this->error($e->getMessage(), 403)
        );

        $this->renderable(fn(NotCallHostException $e) =>
            $this->error($e->getMessage(), 403)
        );

        // ── Match Exceptions ──────────────────────────────────────────────────
        $this->renderable(fn(MatchNotFoundException $e) =>
            $this->error($e->getMessage(), 404)
        );

        $this->renderable(fn(FeedbackAlreadySubmittedException $e) =>
            $this->error($e->getMessage(), 409)
        );

        // ── Verification Exceptions ───────────────────────────────────────────
        $this->renderable(fn(NoVerificationSubmittedException $e) =>
            $this->error($e->getMessage(), 404)
        );

        $this->renderable(fn(VerificationAlreadyExistsException $e) =>
            $this->error($e->getMessage(), 409)
        );

        $this->renderable(fn(VerificationAttemptLimitException $e) =>
            $this->error($e->getMessage(), 429)
        );

        $this->renderable(fn(DuplicateIdentityCardException $e) =>
            $this->error($e->getMessage(), 409)
        );

        $this->reportable(function (Throwable $e) {
            //
        });
    }

    // ── Private helper ────────────────────────────────────────────────────────

    private function error(string $message, int $status): JsonResponse
    {
        return response()->json(['status' => 'error', 'message' => $message], $status);
    }
}
