<?php

use App\Http\Controllers\Api\V1\Profile\PortfolioController;
use App\Http\Controllers\Api\V1\Profile\ProfileController;
use App\Http\Controllers\Api\V1\Profile\SkillController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Profile Routes — v1
|--------------------------------------------------------------------------
*/

Route::middleware('api')->group(function () {

    // ── Own Profile ──────────────────────────────────────────────────────────

    Route::prefix('profile')->group(function () {
        Route::get('/',                [ProfileController::class, 'show']);
        Route::put('/',                [ProfileController::class, 'update']);
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

    // ── User Discovery ───────────────────────────────────────────────────────

    // Search / browse all public users 
    Route::get('users', [ProfileController::class, 'index']);

    // Any specific user's public profile and portfolio
    Route::prefix('users/{user}')->group(function () {
        Route::get('/',          [ProfileController::class,  'showUser']);
        Route::get('/portfolio', [PortfolioController::class, 'showUserPortfolio']);
    });

    // ── Skill Endorsements ────────────────────────────────────────────────────

    Route::prefix('skills/{skill}')->group(function () {
        Route::post('/endorse',   [SkillController::class, 'endorse']);
        Route::delete('/endorse', [SkillController::class, 'unendorse']);
    });
});
