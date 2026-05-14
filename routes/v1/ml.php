<?php

use App\Http\Controllers\Api\V1\ML\MLController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ML Service Routes  —  /api/v1/ml
|--------------------------------------------------------------------------
|
| Protected by the 'auth.ml' middleware (shared secret, not Sanctum).
| Exclusively for the internal ML service — not end users.
|
| Route map:
|   GET  /ml/dataset/stats     → Dataset summary (counts, score distribution)
|   POST /ml/dataset/generate  → Generate synthetic training data
|   GET  /ml/dataset/export    → Export flattened training rows (JSON or CSV)
|   POST /ml/matches/ingest    → Push scored matches back to the platform
|
| Typical ML cycle:
|   1. GET  /ml/dataset/stats      — decide if more data is needed
|   2. POST /ml/dataset/generate   — (optional) generate synthetic data
|   3. GET  /ml/dataset/export     — pull training rows with features + labels
|   4. Train / fine-tune model externally
|   5. POST /ml/matches/ingest     — push scored matches; users see them at GET /matches
|
*/

Route::prefix('ml')
    ->middleware('auth.ml')
    ->group(function () {

        Route::prefix('dataset')->group(function () {
            Route::get('stats',     [MLController::class, 'stats']);
            Route::post('generate', [MLController::class, 'generate']);
            Route::get('export',    [MLController::class, 'export']);
        });

        Route::post('matches/ingest', [MLController::class, 'ingest']);
    });
