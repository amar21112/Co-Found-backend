<?php

use App\Http\Controllers\Api\V1\Application\MyApplicationController;
use App\Http\Controllers\Api\V1\Application\ProjectApplicationController;
use App\Http\Controllers\Api\V1\Project\ProjectController;
use App\Http\Controllers\Api\V1\Project\ProjectMilestoneController;
use App\Http\Controllers\Api\V1\Project\ProjectRoleController;
use App\Http\Controllers\Api\V1\Project\ProjectSkillController;
use App\Http\Controllers\Api\V1\Project\ProjectTeamController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Project Module Routes — /api/v1
|--------------------------------------------------------------------------
|
| All routes are protected by Sanctum auth middleware.
| Route model binding resolves Project by UUID automatically.
|
*/

Route::prefix('v1')->group(function () {
    //Projects end points -----------------------------------------------------
    // ── Projects ────────────────────────────────────────────────────────────── api test done
    Route::apiResource('projects', ProjectController::class);

    // ── Project Skills ──────────────────────────────────────────────────────── api test done
    Route::prefix('projects/{project}/skills')->group(function () {
        Route::post('/',           [ProjectSkillController::class, 'store']);
        Route::put('/{skillId}',   [ProjectSkillController::class, 'update']);
        Route::delete('/{skillId}',[ProjectSkillController::class, 'destroy']);
    });

    // ── Project Roles ───────────────────────────────────────────────────────── api test done
    Route::prefix('projects/{project}/roles')->group(function () {
        Route::get('/',           [ProjectRoleController::class, 'index']);
        Route::post('/',          [ProjectRoleController::class, 'store']);
        Route::put('/{roleId}',   [ProjectRoleController::class, 'update']);
        Route::delete('/{roleId}',[ProjectRoleController::class, 'destroy']);
    });

    // ── Project Milestones ──────────────────────────────────────────────────── api test done
    Route::prefix('projects/{project}/milestones')->group(function () {
        Route::get('/',                   [ProjectMilestoneController::class, 'index']);
        Route::post('/',                  [ProjectMilestoneController::class, 'store']);
        Route::put('/{milestoneId}',      [ProjectMilestoneController::class, 'update']);
        Route::delete('/{milestoneId}',   [ProjectMilestoneController::class, 'destroy']);
    });

    // ── Project Team Members ────────────────────────────────────────────────── api test done
    Route::prefix('projects/{project}/team')->group(function () {
        Route::get('/',              [ProjectTeamController::class, 'index']);
        Route::post('/leave',        [ProjectTeamController::class, 'leave']); // not test because auth user
        Route::put('/{userId}',      [ProjectTeamController::class, 'update']);
        Route::delete('/{userId}',   [ProjectTeamController::class, 'destroy']);
    });

    // Application collection endpoints ---------------------------------------------------------------------------

    // ── Project Applications (project-scoped) ─────────────────────────────────
    Route::prefix('projects/{project}/applications')->group(function () {
        Route::get('/',                            [ProjectApplicationController::class, 'index']);
        Route::post('/',                           [ProjectApplicationController::class, 'store']);
        Route::get('/{applicationId}',             [ProjectApplicationController::class, 'show']);
        Route::patch('/{applicationId}/review',    [ProjectApplicationController::class, 'review']);
    });

    // ── My Applications (user-scoped) ─────────────────────────────────────────
    Route::prefix('applications')->group(function () {
        Route::get('/mine',                       [MyApplicationController::class, 'index']);
        Route::patch('/{applicationId}/withdraw', [MyApplicationController::class, 'withdraw']);
    });
});
