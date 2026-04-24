<?php

use App\Http\Controllers\Api\V1\Admin\AdminRestrictionController;
use App\Http\Controllers\Api\V1\Admin\AdminVerificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Module Routes — /api/v1/admin
|--------------------------------------------------------------------------
|
| All routes require:
|   auth:sanctum  — valid token
|   no.guest      — no ephemeral guest accounts
|   verified      — email verified
|
| Authorization (moderator vs admin) is enforced via AdminPolicy
| inside each controller using $this->authorize('moderate', ...).
|
| Identity Verification status machine:
|   pending ──► under_review (claim)
|   under_review ──► verified|rejected|pending (review)
|   under_review ──► escalated (escalate)
|   escalated ──► under_review (claim again)
|
*/

Route::middleware(['auth:sanctum', 'no.guest', 'verified'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // ── Identity Verification Queue ───────────────────────────────────────
        Route::prefix('verifications')->name('verifications.')->group(function () {
            Route::get('/',                [AdminVerificationController::class, 'index'])->name('index');
            Route::get('/{id}',            [AdminVerificationController::class, 'show'])->name('show');
            Route::patch('/{id}/claim',    [AdminVerificationController::class, 'claim'])->name('claim');
            Route::patch('/{id}/escalate', [AdminVerificationController::class, 'escalate'])->name('escalate');
            Route::post('/{id}/review',    [AdminVerificationController::class, 'review'])->name('review');
        });

        // ── User Restrictions ─────────────────────────────────────────────────
        Route::prefix('restrictions')->name('restrictions.')->group(function () {
            Route::get('/',           [AdminRestrictionController::class, 'index'])->name('index');
            Route::post('/',          [AdminRestrictionController::class, 'store'])->name('store');
            Route::get('/{id}',       [AdminRestrictionController::class, 'show'])->name('show');
            Route::patch('/{id}/lift',[AdminRestrictionController::class, 'lift'])->name('lift');
        });

        // ── User-scoped restriction history ───────────────────────────────────
        Route::get('users/{userId}/restrictions',
            [AdminRestrictionController::class, 'userRestrictions']
        )->name('users.restrictions');
    });
