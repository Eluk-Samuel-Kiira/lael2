<?php
use App\Http\Controllers\Api\{ SyncController };

Route::post('/sync/push', [SyncController::class, 'push']);
Route::post('/sync/pull', [SyncController::class, 'pull']);
Route::get('/sync/status', [SyncController::class, 'status']);

Route::get('/test-remote-db', function () {
    try {
        // Use the correct connection name from database.php
        $pdo = DB::connection('mysql_remote')->getPdo();
        return "✅ Connected successfully to remote database!";
    } catch (\Exception $e) {
        return "❌ Connection failed: " . $e->getMessage();
    }
});