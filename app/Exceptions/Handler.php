<?php

namespace App\Exceptions;

use App\Exceptions\Auth\AccountLockedException;
use App\Exceptions\Auth\AccountNotActiveException;
use App\Exceptions\Auth\AccountRestrictedException;
use App\Exceptions\Auth\EmailAlreadyVerifiedException;
use App\Exceptions\Auth\InvalidCredentialsException;
use App\Exceptions\Auth\InvalidPasswordResetTokenException;
use App\Exceptions\Auth\InvalidVerificationTokenException;
use DateTimeInterface;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        InvalidCredentialsException::class,
        AccountLockedException::class,
        AccountNotActiveException::class,
        AccountRestrictedException::class,
        EmailAlreadyVerifiedException::class,
        InvalidVerificationTokenException::class,
        InvalidPasswordResetTokenException::class,
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        // ── 401 Auth Exceptions ───────────────────────────────────────────────
        $this->renderable(fn(InvalidCredentialsException $e) =>
        $this->authError($e->getMessage(), 401)
        );

        $this->renderable(fn(AccountNotActiveException $e) =>
        $this->authError($e->getMessage(), 403)
        );

        $this->renderable(fn(AccountRestrictedException $e) =>
        $this->authError($e->getMessage(), 403)
        );

        $this->renderable(fn(AccountLockedException $e) =>
        response()->json([
            'status'  => 'error',
            'message' => $e->getMessage(),
            'locked_until' => $e->lockedUntil->format(DateTimeInterface::ATOM),
        ], 423)
        );

        // ── 400 Token Exceptions ──────────────────────────────────────────────
        $this->renderable(fn(InvalidVerificationTokenException $e) =>
        $this->authError($e->getMessage(), 400)
        );

        $this->renderable(fn(InvalidPasswordResetTokenException $e) =>
        $this->authError($e->getMessage(), 400)
        );

        $this->renderable(fn(EmailAlreadyVerifiedException $e) =>
        $this->authError($e->getMessage(), 409)
        );

        $this->reportable(function (Throwable $e) {
            //
        });
    }

    private function authError(string $message, int $status): JsonResponse
    {
        return response()->json(['status' => 'error', 'message' => $message], $status);
    }
}
