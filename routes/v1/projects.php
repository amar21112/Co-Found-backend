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
| Access tiers:
|
|  guest.content  — guests allowed, but page-capped + data stripped in resource
|  no.guest       — requires a real registered account (any status)
|  verified       — requires email-verified active account
|
*/

Route::prefix('v1')->group(function () {

    // ── Guest-accessible browse (page-capped, data stripped in resource) ────── done
    Route::middleware(['auth:sanctum', 'guest.content'])->group(function () {
        Route::get('projects',           [ProjectController::class, 'index']);
        Route::get('projects/{project}', [ProjectController::class, 'show']);

        // Guests can see what roles a project is hiring for (good UX hook)
        // but NOT milestones (internal roadmap) or team members (contact info)
        Route::get('projects/{project}/roles', [ProjectRoleController::class, 'index']);
    });

    // ── Milestones and team — real users only (internal project info) ─────────done
    Route::middleware(['auth:sanctum', 'no.guest'])->group(function () {
        Route::get('projects/{project}/milestones', [ProjectMilestoneController::class, 'index']);
        Route::get('projects/{project}/team',       [ProjectTeamController::class, 'index']);
    });

    // ── Write / action endpoints — verified real users only ───────────────────
    Route::middleware(['auth:sanctum', 'no.guest', 'verified'])->group(function () {

        // Projects CRUD
        Route::post('projects',             [ProjectController::class, 'store']);
        Route::put('projects/{project}',    [ProjectController::class, 'update']);
        Route::delete('projects/{project}', [ProjectController::class, 'destroy']);

        // Project Skills
        Route::prefix('projects/{project}/skills')->group(function () {
            Route::post('/',            [ProjectSkillController::class, 'store']);
            Route::put('/{skillId}',    [ProjectSkillController::class, 'update']);
            Route::delete('/{skillId}', [ProjectSkillController::class, 'destroy']);
        });

        // Project Roles
        Route::prefix('projects/{project}/roles')->group(function () {
            Route::post('/',            [ProjectRoleController::class, 'store']);
            Route::put('/{roleId}',     [ProjectRoleController::class, 'update']);
            Route::delete('/{roleId}',  [ProjectRoleController::class, 'destroy']);
        });

        // Project Milestones
        Route::prefix('projects/{project}/milestones')->group(function () {
            Route::post('/',               [ProjectMilestoneController::class, 'store']);
            Route::put('/{milestoneId}',   [ProjectMilestoneController::class, 'update']);
            Route::delete('/{milestoneId}',[ProjectMilestoneController::class, 'destroy']);
        });

        // Project Team
        Route::prefix('projects/{project}/team')->group(function () {
            Route::post('/leave',      [ProjectTeamController::class, 'leave']);
            Route::put('/{userId}',    [ProjectTeamController::class, 'update']);
            Route::delete('/{userId}', [ProjectTeamController::class, 'destroy']);
        });

        // Applications
        Route::prefix('projects/{project}/applications')->group(function () {
            Route::get('/',                         [ProjectApplicationController::class, 'index']);
            Route::post('/',                        [ProjectApplicationController::class, 'store']);
            Route::get('/{applicationId}',          [ProjectApplicationController::class, 'show']);
            Route::patch('/{applicationId}/review', [ProjectApplicationController::class, 'review']);
        });

        // My Applications
        Route::prefix('applications')->group(function () {
            Route::get('/mine',                       [MyApplicationController::class, 'index']);
            Route::patch('/{applicationId}/withdraw', [MyApplicationController::class, 'withdraw']);
        });
    });
});
