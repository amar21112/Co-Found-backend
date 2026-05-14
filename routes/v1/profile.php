<?php

use App\Http\Controllers\Api\V1\Profile\PortfolioController;
use App\Http\Controllers\Api\V1\Profile\ProfileController;
use App\Http\Controllers\Api\V1\Profile\SkillController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Profile Routes — v1
|--------------------------------------------------------------------------
|
| Access tiers:
|
|  guest.content  — guests can browse user directory + public profiles,
|                   page-capped and data stripped (no email/contact links)
|  no.guest       — requires a real registered account
|  verified       — requires email-verified active account
|
*/

// ── Guest-accessible browse (page-capped, data stripped in resource) ──────────
Route::middleware(['auth:sanctum', 'guest.content'])->group(function () {
    // Guests can browse the user directory — but see limited fields
    Route::get('users', [ProfileController::class, 'index']);

    // Guests can view a public profile — but no email/contact links
    Route::get('users/{user}', [ProfileController::class, 'showUser']);
});

// ── Portfolio browse — real users only ────────────────────────────────────────
// Guests should not see full portfolio details (too much info without registering)
Route::middleware(['auth:sanctum', 'no.guest'])->group(function () {
    Route::get('users/{user}/portfolio', [PortfolioController::class, 'showUserPortfolio']);
});

// ── Own profile + write actions — verified real users only ───────────────────
Route::middleware(['auth:sanctum', 'no.guest', 'verified'])->group(function () {

    Route::prefix('profile')->group(function () {
        Route::get('/',                 [ProfileController::class, 'show']);
        Route::match(['put', 'post'], '/', [ProfileController::class, 'update']);
        Route::post('/change-password', [ProfileController::class, 'changePassword']);

        // Skills
        Route::prefix('skills')->group(function () {
            Route::get('/',           [SkillController::class, 'index']);
            Route::post('/',          [SkillController::class, 'store']);
            Route::put('/{skill}',    [SkillController::class, 'update']);
            Route::delete('/{skill}', [SkillController::class, 'destroy']);
        });

        // Portfolio
        Route::prefix('portfolio')->group(function () {
            Route::get('/',                   [PortfolioController::class, 'index']);
            Route::post('/',                  [PortfolioController::class, 'store']);
            Route::put('/{portfolioItem}',    [PortfolioController::class, 'update']);
            Route::delete('/{portfolioItem}', [PortfolioController::class, 'destroy']);
        });
    });

    // Skill Endorsements
    Route::prefix('skills/{skill}')->group(function () {
        Route::post('/endorse',   [SkillController::class, 'endorse']);
        Route::delete('/endorse', [SkillController::class, 'unendorse']);
    });
});
