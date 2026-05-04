<?php

use App\Http\Controllers\Api\SyncController;

// ── Public sync API (authenticated via X-Sync-Token header) ──────────────────
// These are called by the local machine's SyncToRemote command.
// No Laravel auth middleware — token validation happens inside the controller.
Route::prefix('sync')->group(function () {
    Route::get( '/status', [SyncController::class, 'status']);
    Route::post('/push',   [SyncController::class, 'push']);
    Route::post('/pull',   [SyncController::class, 'pull']);
});

// ── Frontend routes (require Laravel session auth) ────────────────────────────
// These are called by the browser JS on the LOCAL machine only.
Route::middleware(['auth'])->prefix('sync')->group(function () {

    // Polled every 15s by the badge — reads local sync_status table
    Route::get('/frontend-status', [SyncController::class, 'getFrontendStatus']);

    // Manual sync button — fires artisan pos:sync in background
    // Blocked on remote/cPanel by isLocalMachine() check inside controller
    Route::post('/trigger', [SyncController::class, 'trigger']);
});



use App\Http\Controllers\Api\DependentController;

Route::get('/api/dependent/options', [DependentController::class, 'getOptions'])->name('api.dependent.options');
Route::get('/get-departments', [DependentController::class, 'getDepartmentsByLocation'])->name('get.departments');



