<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;

/**
 * Resolves the currently authenticated user from the request.
 *
 * Resolution order (single source of truth):
 *   1. auth()->user()  — Sanctum / session guard (primary)
 *   2. $request->user() — fallback for guard-agnostic resolution
 *   3. throw AuthenticationException — never return null silently
 *
 * Usage:
 *   use ResolvesUser;
 *   $user = $this->resolveUser($request);
 */
trait ResolvesUser
{
    /**
     * @throws AuthenticationException
     */
    public function resolveUser(Request $request): User
    {
        /** @var User|null $user */
        $user = auth()->user() ?? $request->user();

        if (! $user) {
            throw new AuthenticationException();
        }

        return $user;
    }
}
