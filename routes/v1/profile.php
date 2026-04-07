<?php

use App\Http\Controllers\Api\V1\Profile\PortfolioController;
use App\Http\Controllers\Api\V1\Profile\ProfileController;
use App\Http\Controllers\Api\V1\Profile\SkillController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Profile Routes
|--------------------------------------------------------------------------
|
| All routes in this file are prefixed with /api and grouped under the
| 'api' middleware group (see RouteServiceProvider).
|
| Authentication:  Sanctum token (bearer) once the auth module is complete.
|                  During development, the ResolvesUser trait falls back to
|                  user_id sent in the request body.
|
*/

Route::middleware('api')->group(function () {

    // ── Own Profile ──────────────────────────────────────────────────────────

    Route::prefix('profile')->group(function () {

        // View own profile
        Route::get('/', [ProfileController::class, 'show']);

        // Update own profile
        Route::put('/', [ProfileController::class, 'update']);

        // Change password
        Route::post('/change-password', [ProfileController::class, 'changePassword']);

        // ── Skills ───────────────────────────────────────────────────────────

        Route::prefix('skills')->group(function () {
            Route::get('/',         [SkillController::class, 'index']);   // list own skills
            Route::post('/',        [SkillController::class, 'store']);   // add skill
            Route::put('/{skill}',  [SkillController::class, 'update']); // update skill
            Route::delete('/{skill}', [SkillController::class, 'destroy']); // delete skill
        });

        // ── Portfolio ─────────────────────────────────────────────────────────

        Route::prefix('portfolio')->group(function () {
            Route::get('/',                    [PortfolioController::class, 'index']);   // list own items
            Route::post('/',                   [PortfolioController::class, 'store']);   // create item
            Route::put('/{portfolioItem}',     [PortfolioController::class, 'update']); // update item
            Route::delete('/{portfolioItem}',  [PortfolioController::class, 'destroy']); // delete item
        });
    });

    // ── Any User's Public Profile ────────────────────────────────────────────

    Route::prefix('users/{user}')->group(function () {
        Route::get('/',          [ProfileController::class, 'showUser']);              // view public profile
        Route::get('/portfolio', [PortfolioController::class, 'showUserPortfolio']); // view public portfolio
    });

    // ── Skill Endorsements ────────────────────────────────────────────────────
    // Placed at /api/skills/{skill}/endorse so any user can endorse any skill
    // without needing to know the owner's ID.

    Route::prefix('skills/{skill}')->group(function () {
        Route::post('/endorse',   [SkillController::class, 'endorse']);   // endorse a skill
        Route::delete('/endorse', [SkillController::class, 'unendorse']); // remove endorsement
    });
});
