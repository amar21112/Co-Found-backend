<?php

use App\Http\Controllers\Api\V1\Admin\AdminActionLogController;
use App\Http\Controllers\Api\V1\Admin\AdminModerationController;
use App\Http\Controllers\Api\V1\Admin\AdminReportController;
use App\Http\Controllers\Api\V1\Admin\AdminRestrictionController;
use App\Http\Controllers\Api\V1\Admin\AdminSettingController;
use App\Http\Controllers\Api\V1\Admin\AdminSystemLogController;
use App\Http\Controllers\Api\V1\Admin\AdminUserController;
use App\Http\Controllers\Api\V1\Admin\AdminVerificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Module Routes — /api/v1/admin
|--------------------------------------------------------------------------
|
| All routes require:
|   auth:sanctum  — valid Sanctum token
|   no.guest      — no ephemeral guest accounts
|   verified      — email address verified
|
| Authorization is split into two tiers, enforced via AdminPolicy
| inside each controller using $this->authorize(...):
|
|   'moderate'    — moderator OR administrator
|   'administrate'— administrator only
|
| Access matrix:
|   Endpoint group                | Required tier
|   ─────────────────────────────────────────────
|   Verification queue            | moderate
|   Restrictions                  | moderate
|   Reports                       | moderate
|   Moderation log                | moderate
|   Action audit log              | moderate
|   System logs                   | moderate
|   User management               | administrate
|   System settings               | administrate
|
*/

Route::middleware(['auth:sanctum', 'no.guest', 'verified'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // ── Identity Verification Queue ───────────────────────────────────────
        // Implemented by teammate — routes preserved unchanged.
        Route::prefix('verifications')->name('verifications.')->group(function () {
            Route::get('/',                          [AdminVerificationController::class, 'index'])->name('index');
            Route::get('/{id}',                      [AdminVerificationController::class, 'show'])->name('show');
            Route::patch('/{id}/claim',              [AdminVerificationController::class, 'claim'])->name('claim');
            Route::patch('/{id}/escalate',           [AdminVerificationController::class, 'escalate'])->name('escalate');
            Route::post('/{id}/review',              [AdminVerificationController::class, 'review'])->name('review');
        });

        // ── User Restrictions ─────────────────────────────────────────────────
        // Implemented by teammate — routes preserved unchanged.
        Route::prefix('restrictions')->name('restrictions.')->group(function () {
            Route::get('/',            [AdminRestrictionController::class, 'index'])->name('index');
            Route::post('/',           [AdminRestrictionController::class, 'store'])->name('store');
            Route::get('/{id}',        [AdminRestrictionController::class, 'show'])->name('show');
            Route::patch('/{id}/lift', [AdminRestrictionController::class, 'lift'])->name('lift');
        });

        // User-scoped restriction history (teammate's route)
        Route::get(
            'users/{userId}/restrictions',
            [AdminRestrictionController::class, 'userRestrictions']
        )->name('users.restrictions');

        // ── Reports ───────────────────────────────────────────────────────────
        // moderate tier — moderators and administrators
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/',         [AdminReportController::class, 'index'])->name('index');
            Route::get('/{id}',     [AdminReportController::class, 'show'])->name('show');
            Route::patch('/{id}',   [AdminReportController::class, 'update'])->name('update');
        });

        // ── Content Moderation Log ────────────────────────────────────────────
        // moderate tier
        Route::prefix('moderation')->name('moderation.')->group(function () {
            Route::get('/',   [AdminModerationController::class, 'index'])->name('index');
            Route::post('/',  [AdminModerationController::class, 'store'])->name('store');
        });

        // ── Admin Action Audit Log ────────────────────────────────────────────
        // moderate tier — read-only
        Route::get('action-logs', [AdminActionLogController::class, 'index'])->name('action-logs.index');

        // ── System Logs ───────────────────────────────────────────────────────
        // moderate tier — read-only
        Route::get('system-logs', [AdminSystemLogController::class, 'index'])->name('system-logs.index');

        // ── User Management ───────────────────────────────────────────────────
        // administrate tier — administrators only, except /reports which is moderate
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/',            [AdminUserController::class, 'index'])->name('index');
            Route::get('/{userId}',    [AdminUserController::class, 'show'])->name('show');
            Route::patch('/{userId}',  [AdminUserController::class, 'update'])->name('update');
            Route::delete('/{userId}', [AdminUserController::class, 'destroy'])->name('destroy');

            // Identity verification for any user — full doc + review history
            // administrate tier (document images are sensitive)
            Route::get('/{userId}/verification', [AdminUserController::class, 'verification'])->name('verification');

            // Reports filed against a user — moderate tier (same as /admin/reports)
            Route::get('/{userId}/reports', [AdminUserController::class, 'reports'])->name('reports');
        });

        // ── System Settings ───────────────────────────────────────────────────
        // administrate tier — administrators only
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/',                  [AdminSettingController::class, 'index'])->name('index');
            Route::get('/{key}',             [AdminSettingController::class, 'show'])->name('show');
            Route::patch('/{key}',           [AdminSettingController::class, 'update'])->name('update');
            Route::get('/{key}/history',     [AdminSettingController::class, 'history'])->name('history');
        });
    });
