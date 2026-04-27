<?php

use App\Http\Controllers\Api\V1\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| User Reports Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->prefix('reports')->name('reports.')->group(function () {
    Route::get('/', [ReportController::class, 'index'])->name('index');
    Route::post('/', [ReportController::class, 'store'])->name('store');
    Route::get('/{id}', [ReportController::class, 'show'])->name('show');
    Route::patch('/{id}', [ReportController::class, 'update'])->name('update');
    Route::delete('/{id}', [ReportController::class, 'destroy'])->name('destroy');
});
