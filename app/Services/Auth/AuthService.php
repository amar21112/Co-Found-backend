<?php

namespace App\Services\Auth;

use App\DTOs\Auth\AuthTokenDTO;
use App\DTOs\Auth\LoginDTO;
use App\DTOs\Auth\RegisterDTO;
use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Exceptions\Auth\AccountLockedException;
use App\Exceptions\Auth\AccountNotActiveException;
use App\Exceptions\Auth\AccountRestrictedException;
use App\Exceptions\Auth\InvalidCredentialsException;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthService
{
    /** Consecutive failures before account is brute-force locked. */
    private const MAX_LOGIN_ATTEMPTS = 5;

    /** How long to lock the account after max failures, in minutes. */
    private const LOCK_DURATION_MINUTES = 15;

    /** Guest token TTL in days. */
    private const GUEST_TOKEN_TTL_DAYS = 7;

    /** Sanctum token name for regular users. */
    private const TOKEN_NAME = 'api_token';

    /** Sanctum token name for guest sessions. */
    private const GUEST_TOKEN_NAME = 'guest_token';

    public function __construct(
        private readonly UserRepositoryInterface  $userRepository,
        private readonly EmailVerificationService $emailVerificationService,
    ) {}

    // =========================================================================
    // Registration
    // =========================================================================

    /**
     * Register a new user.
     *
     * Account starts as `pending` + `email_verified = false`.
     * A verification email is dispatched immediately.
     * A Sanctum token is returned so the client can call authenticated
     * endpoints right away — write actions remain soft-blocked by the
     * `verified` middleware until email is confirmed.
     *
     * If the request comes from a guest session (bearer token belongs to a
     * guest user), the guest row and its token are cleaned up on registration
     * so the guest session cannot be reused after the account is created.
     */
    public function register(RegisterDTO $dto, ?User $guestUser = null): AuthTokenDTO
    {
        $user = $this->userRepository->create([
            'email'          => $dto->email,
            'username'       => $dto->username,
            'password'       => Hash::make($dto->password),
            'full_name'      => $dto->fullName,
            'role'           => UserRole::RegularUser->value,
            'account_status' => AccountStatus::Pending->value,
            'email_verified' => false,
        ]);

        $this->emailVerificationService->sendVerificationEmail($user);

        $token = $user->createToken(self::TOKEN_NAME)->plainTextToken;

        // ── Clean up guest session if this registration came from a guest ─────
        // Revoke the guest's Sanctum tokens and hard-delete the ephemeral row
        // so the guest account cannot be used after the real account is created.
        if ($guestUser && $guestUser->isGuest()) {
            $guestUser->tokens()->delete();
            $guestUser->forceDelete();
        }

        return new AuthTokenDTO($token, 'Bearer', $user->fresh());
    }

    // =========================================================================
    // Login
    // =========================================================================

    /**
     * Authenticate a user and issue a Sanctum token.
     *
     * Guard order (intentional):
     *   1. User must exist.
     *   2. Brute-force lock check — before password verification so we don't
     *      give timing hints about whether the account exists.
     *   3. Password verification — on failure: increment counter, re-lock if
     *      threshold reached, then throw generic credential error.
     *   4. Admin block check (banned / suspended / deleted) — after password
     *      so we don't leak whether an email belongs to a blocked account.
     *   5. Admin restriction check (UserRestriction table) — active time-based
     *      or permanent restrictions issued by moderators / admins.
     *   6. Issue token, reset attempt counter, record last login.
     */
    public function login(LoginDTO $dto, string $ip): AuthTokenDTO
    {
        $user = $this->userRepository->findAuthenticatableByEmail($dto->email);

        if (! $user) {
            // Constant-time-ish: still throw the same generic error
            throw new InvalidCredentialsException();
        }

        // ── Guard 2: brute-force lock ─────────────────────────────────────────
        $this->assertNotBruteForceLocked($user);

        // ── Guard 3: password ─────────────────────────────────────────────────
        if (! Hash::check($dto->password, $user->password)) {
            $this->handleFailedLoginAttempt($user);
            throw new InvalidCredentialsException();
        }

        // ── Guard 4: admin block (banned / suspended / deleted) ───────────────
        if ($user->isBlocked()) {
            throw new AccountNotActiveException();
        }

        // ── Guard 5: active admin restriction ─────────────────────────────────
        if ($this->userRepository->hasActiveRestriction($user)) {
            throw new AccountRestrictedException();
        }

        // ── Successful login ──────────────────────────────────────────────────
        // Revoke all previous tokens — enforce single active session per user.
        $user->tokens()->delete();

        $this->userRepository->resetLoginAttempts($user);
        $this->userRepository->updateLastLogin($user, $ip);

        $token = $user->createToken(self::TOKEN_NAME)->plainTextToken;

        return new AuthTokenDTO($token, 'Bearer', $user->fresh());
    }

    // =========================================================================
    // Logout
    // =========================================================================

    /**
     * Revoke the token used in this request only.
     * Other device sessions remain active.
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    // =========================================================================
    // Token Refresh
    // =========================================================================

    /**
     * Rotate the current token — revoke the old one and issue a fresh one.
     * Useful for extending sessions without a full re-login.
     */
    public function refresh(User $user): AuthTokenDTO
    {
        $user->currentAccessToken()->delete();

        $tokenName = $user->isGuest() ? self::GUEST_TOKEN_NAME : self::TOKEN_NAME;
        $token     = $user->createToken($tokenName)->plainTextToken;

        return new AuthTokenDTO($token, 'Bearer', $user);
    }

    // =========================================================================
    // Guest Session
    // =========================================================================

    /**
     * Issue an ephemeral guest token.
     *
     * Creates a minimal User row with role=guest so the API can throttle and
     * gate guest actions. The guest account is intentionally not reused — when
     * the guest registers they get a fresh `regular_user` row. Stale guest rows
     * are cleaned up by the PruneGuestAccounts artisan command.
     *
     * Note: IP-level throttling for this endpoint must be applied at the route
     * layer via the `throttle` middleware (e.g. `throttle:10,1` — 10 per minute
     * per IP) to prevent ephemeral account flooding.
     */
    public function issueGuestToken(): AuthTokenDTO
    {
        $guest = $this->userRepository->create([
            'email'          => 'guest_' . Str::uuid() . '@guest.cofound',
            'username'       => 'guest_' . Str::random(12),
            'password'       => Hash::make(Str::random(32)),
            'full_name'      => 'Guest',
            'role'           => UserRole::Guest->value,
            'account_status' => AccountStatus::Active->value,
            'email_verified' => false,
        ]);

        $token = $guest->createToken(
            self::GUEST_TOKEN_NAME,
            ['*'],
            now()->addDays(self::GUEST_TOKEN_TTL_DAYS)
        )->plainTextToken;

        return new AuthTokenDTO($token, 'Bearer', $guest->fresh());
    }

    // =========================================================================
    // Private Helpers
    // =========================================================================

    /**
     * Throw if the account is currently under a brute-force lock.
     * We check this before the password so an attacker can't use timing
     * differences to probe whether the lock is still active.
     */
    private function assertNotBruteForceLocked(User $user): void
    {
        if ($user->isLocked()) {
            throw new AccountLockedException($user->locked_until);
        }
    }

    /**
     * Increment the failed-attempt counter.
     * If the threshold is reached, lock the account for LOCK_DURATION_MINUTES.
     * We use refresh() after increment() because increment() does not update
     * the in-memory model.
     */
    private function handleFailedLoginAttempt(User $user): void
    {
        $this->userRepository->incrementLoginAttempts($user);

        $user->refresh();

        if ($user->login_attempts >= self::MAX_LOGIN_ATTEMPTS) {
            $this->userRepository->lockUntil($user, now()->addMinutes(self::LOCK_DURATION_MINUTES));
        }
    }
}
