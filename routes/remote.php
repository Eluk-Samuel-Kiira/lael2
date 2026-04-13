<?php

use App\Http\Controllers\Api\SyncController;

// Sync routes (no /api prefix)
Route::post('/sync/push', [SyncController::class, 'push']);
Route::post('/sync/pull', [SyncController::class, 'pull']);
Route::get('/sync/status', [SyncController::class, 'status']);

// Route::get('/test-remote-db', function () {
//     try {
//         $pdo = DB::connection('mysql_remote')->getPdo();
//         return "✅ Connected successfully to remote database!";
//     } catch (\Exception $e) {
//         return "❌ Connection failed: " . $e->getMessage();
//     }
// });