<?php

Route::get('/test-remote-db', function () {
    try {
        $pdo = DB::connection('mysql_remote')->getPdo();
        return "✅ Connected successfully to remote database!";
    } catch (\Exception $e) {
        return "❌ Connection failed: " . $e->getMessage();
    }
});