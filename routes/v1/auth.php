<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes  —  prefix: /api/auth
|--------------------------------------------------------------------------
|
| Middleware tiers:
|
|  Public (no token required)
|    POST /auth/register           → throttle:10,1  (10/min per IP)
|    POST /auth/login              → throttle:10,1
|    POST /auth/guest              → throttle:5,1   (5/min per IP — flood guard)
|    POST /auth/password/forgot    → throttle:5,1
|    POST /auth/password/reset     → throttle:5,1
|    GET  /auth/email/verify/{token}
|
|  Authenticated — any valid Sanctum token (guest OR real user)
|    GET  /auth/me
|    POST /auth/logout
|    POST /auth/refresh
|
|  Authenticated — real user only (no.guest blocks role=guest)
|    POST /auth/email/resend       → throttle:3,1
|
*/

Route::prefix('auth')->name('auth.')->group(function () {

    // ── Public ────────────────────────────────────────────────────────────────
    Route::post('register',
        [AuthController::class, 'register']
    )->middleware('throttle:10,1')->name('register');

    Route::post('login',
        [AuthController::class, 'login']
    )->middleware('throttle:10,1')->name('login');

    Route::post('guest',
        [AuthController::class, 'guest']
    )->middleware('throttle:5,1')->name('guest');

    Route::post('password/forgot',
        [AuthController::class, 'forgotPassword']
    )->middleware('throttle:5,1')->name('password.forgot');

    Route::post('password/reset',
        [AuthController::class, 'resetPassword']
    )->middleware('throttle:5,1')->name('password.reset');

    Route::get('email/verify/{token}',
        [AuthController::class, 'verifyEmail']
    )->name('email.verify');

    // ── Authenticated (guest + real users) ────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me',      [AuthController::class, 'me'])->name('me');
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::post('refresh',[AuthController::class, 'refresh'])->name('refresh');
    });

    // ── Authenticated real users only (no guests) ─────────────────────────────
    Route::middleware(['auth:sanctum', 'no.guest'])->group(function () {
        Route::post('email/resend',
            [AuthController::class, 'resendVerification']
        )->middleware('throttle:3,1')->name('email.resend');
    });
});
