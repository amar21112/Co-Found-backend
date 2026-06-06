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
use App\Exceptions\Call\CallFullException;
use App\Exceptions\Call\CallNotFoundException;
use App\Exceptions\Call\CallNotJoinableException;
use App\Exceptions\Call\CallParticipantNotAllowedException;
use App\Exceptions\Call\CallReservationDeniedException;
use App\Exceptions\Call\NotACallParticipantException;
use App\Exceptions\Call\NotCallHostException;
use App\Exceptions\Match\FeedbackAlreadySubmittedException;
use App\Exceptions\Match\MatchNotFoundException;
use App\Exceptions\Skill\DuplicateEndorsementException;
use App\Exceptions\Skill\DuplicateSkillException;
use App\Exceptions\Skill\EndorsementNotFoundException;
use App\Exceptions\Verification\DuplicateIdentityCardException;
use App\Exceptions\Verification\NoVerificationSubmittedException;
use App\Exceptions\Verification\VerificationAlreadyExistsException;
use App\Exceptions\Verification\VerificationAttemptLimitException;
use DateTimeInterface;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
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
        CallFullException::class,
        CallNotJoinableException::class,
        CallParticipantNotAllowedException::class,
        CallReservationDeniedException::class,
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

        //Skill exceptions
        DuplicateEndorsementException::class,
        DuplicateSkillException::class,
        EndorsementNotFoundException::class,

        //General exceptions
        ConflictException::class,
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
            $this->error($e->getMessage(), Response::HTTP_UNAUTHORIZED)
        );

        $this->renderable(fn(AccountNotActiveException $e) =>
            $this->error($e->getMessage(), Response::HTTP_FORBIDDEN)
        );

        $this->renderable(fn(AccountRestrictedException $e) =>
            $this->error($e->getMessage(), Response::HTTP_FORBIDDEN)
        );

        $this->renderable(fn(AccountLockedException $e) =>
            response()->json([
                'status'       => 'error',
                'message'      => $e->getMessage(),
                'locked_until' => $e->lockedUntil->format(DateTimeInterface::ATOM),
            ], Response::HTTP_LOCKED)
        );

        $this->renderable(fn(InvalidVerificationTokenException $e) =>
            $this->error($e->getMessage(), Response::HTTP_BAD_REQUEST)
        );

        $this->renderable(fn(InvalidPasswordResetTokenException $e) =>
            $this->error($e->getMessage(), Response::HTTP_BAD_REQUEST)
        );

        $this->renderable(fn(EmailAlreadyVerifiedException $e) =>
            $this->error($e->getMessage(), Response::HTTP_CONFLICT)
        );

        // ── Admin — verification exceptions ───────────────────────────────────

        $this->renderable(fn(VerificationNotFoundException $e) =>
            $this->error($e->getMessage(), Response::HTTP_NOT_FOUND)
        );

        $this->renderable(fn(VerificationAlreadyReviewedException $e) =>
            $this->error($e->getMessage(), Response::HTTP_CONFLICT)
        );

        $this->renderable(fn(VerificationNotClaimableException $e) =>
            $this->error($e->getMessage(), Response::HTTP_CONFLICT)
        );

        $this->renderable(fn(VerificationNotEscalatableException $e) =>
            $this->error($e->getMessage(), Response::HTTP_CONFLICT)
        );

        // ── Admin — restriction exceptions ────────────────────────────────────

        $this->renderable(fn(RestrictionNotFoundException $e) =>
            $this->error($e->getMessage(), Response::HTTP_NOT_FOUND)
        );

        $this->renderable(fn(RestrictionAlreadyLiftedException $e) =>
            $this->error($e->getMessage(), Response::HTTP_CONFLICT)
        );

        // ── Admin — report exceptions ─────────────────────────────────────────

        $this->renderable(fn(ReportNotFoundException $e) =>
            $this->error($e->getMessage(), Response::HTTP_NOT_FOUND)
        );

        // ── Admin — user exceptions ───────────────────────────────────────────

        $this->renderable(fn(AdminUserNotFoundException $e) =>
            $this->error($e->getMessage(), Response::HTTP_NOT_FOUND)
        );

        $this->renderable(fn(CannotDeleteSelfException $e) =>
            $this->error($e->getMessage(), Response::HTTP_CONFLICT)
        );

        // ── Admin — setting exceptions ────────────────────────────────────────

        $this->renderable(fn(SettingNotFoundException $e) =>
            $this->error($e->getMessage(), Response::HTTP_NOT_FOUND)
        );

        // ── Call Exceptions ───────────────────────────────────────────────────

        $this->renderable(fn(CallNotFoundException $e) =>
            $this->error($e->getMessage(), Response::HTTP_NOT_FOUND)
        );

        $this->renderable(fn(CallAlreadyEndedException $e) =>
            $this->error($e->getMessage(), Response::HTTP_CONFLICT)
        );

        $this->renderable(fn(CallNotJoinableException $e) =>
            $this->error($e->getMessage(), Response::HTTP_CONFLICT)
        );

        $this->renderable(fn(CallFullException $e) =>
            $this->error($e->getMessage(), Response::HTTP_CONFLICT)
        );

        // 403 → Prosody reads this as "deny room creation"
        $this->renderable(fn(CallReservationDeniedException $e) =>
            $this->error($e->getMessage(), Response::HTTP_FORBIDDEN)
        );

        // 403 → Prosody reads this as "deny participant join"
        $this->renderable(fn(CallParticipantNotAllowedException $e) =>
            $this->error($e->getMessage(), Response::HTTP_FORBIDDEN)
        );

        $this->renderable(fn(NotACallParticipantException $e) =>
            $this->error($e->getMessage(), Response::HTTP_FORBIDDEN)
        );

        $this->renderable(fn(NotCallHostException $e) =>
            $this->error($e->getMessage(), Response::HTTP_FORBIDDEN)
        );

        // ── Match Exceptions ──────────────────────────────────────────────────
        $this->renderable(fn(MatchNotFoundException $e) =>
            $this->error($e->getMessage(), Response::HTTP_NOT_FOUND)
        );

        $this->renderable(fn(FeedbackAlreadySubmittedException $e) =>
            $this->error($e->getMessage(), Response::HTTP_CONFLICT)
        );

        // ── Verification Exceptions ───────────────────────────────────────────
        $this->renderable(fn(NoVerificationSubmittedException $e) =>
            $this->error($e->getMessage(), Response::HTTP_NOT_FOUND)
        );

        $this->renderable(fn(VerificationAlreadyExistsException $e) =>
            $this->error($e->getMessage(), Response::HTTP_CONFLICT)
        );

        $this->renderable(fn(VerificationAttemptLimitException $e) =>
            $this->error($e->getMessage(), Response::HTTP_TOO_MANY_REQUESTS)
        );

        $this->renderable(fn(DuplicateIdentityCardException $e) =>
            $this->error($e->getMessage(), Response::HTTP_CONFLICT)
        );

        // ── Skill Exceptions ───────────────────────────────────────────
        $this->renderable(fn(DuplicateEndorsementException $e) =>
            $this->error($e->getMessage(), Response::HTTP_CONFLICT)
        );

        $this->renderable(fn(DuplicateSkillException $e) =>
            $this->error($e->getMessage(), Response::HTTP_CONFLICT)
        );

        $this->renderable(fn(EndorsementNotFoundException $e) =>
            $this->error($e->getMessage(), Response::HTTP_NOT_FOUND)
        );

        // ── General Exceptions ───────────────────────────────────────────
        $this->renderable(fn(ConflictException $e) =>
            $this->error($e->getMessage(), Response::HTTP_CONFLICT)
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
