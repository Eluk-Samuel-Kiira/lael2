<?php
// test-sync.php - Run with: php test-sync.php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== POS Sync Test Suite ===\n\n";

// Test 1: Check if sync tables exist
echo "Test 1: Checking sync tables...\n";
try {
    $hasChangeLog = Schema::hasTable('change_log');
    $hasSyncStatus = Schema::hasTable('sync_status');
    
    echo $hasChangeLog ? "✅ change_log table exists\n" : "❌ change_log table missing\n";
    echo $hasSyncStatus ? "✅ sync_status table exists\n" : "❌ sync_status table missing\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

// Test 2: Create a test change log entry
echo "\nTest 2: Creating test change log entry...\n";
try {
    $testId = DB::table('change_log')->insertGetId([
        'table_name' => 'test_sync',
        'row_id' => 999,
        'operation' => 'INSERT',
        'payload' => json_encode(['test' => 'data', 'created_at' => now()]),
        'tenant_id' => 1,
        'logged_at' => now(),
        'retry_count' => 0
    ]);
    
    echo "✅ Created test entry with ID: {$testId}\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

// Test 3: Check pending sync items
echo "\nTest 3: Checking pending sync items...\n";
try {
    $pending = DB::table('change_log')
        ->whereNull('synced_at')
        ->count();
    
    echo "📊 Pending items: {$pending}\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

// Test 4: Test remote API connectivity
echo "\nTest 4: Testing remote API connectivity...\n";
$remoteUrl = env('SYNC_REMOTE_URL', 'https://lael-pos.stardena.org');
$token = env('SYNC_TOKEN', 'your-hardcoded-token-here');
$tenantId = env('TENANT_ID', 1);

try {
    $client = new GuzzleHttp\Client(['timeout' => 10]);
    $response = $client->get($remoteUrl . '/api/sync/status', [
        'headers' => [
            'X-Sync-Token' => $token,
            'X-Tenant-Id' => $tenantId
        ]
    ]);
    
    if ($response->getStatusCode() === 200) {
        echo "✅ Remote API is reachable\n";
        $data = json_decode($response->getBody(), true);
        echo "   Server time: " . ($data['server_time'] ?? 'unknown') . "\n";
    } else {
        echo "⚠️ Remote returned status: " . $response->getStatusCode() . "\n";
    }
} catch (Exception $e) {
    echo "❌ Cannot reach remote API: " . $e->getMessage() . "\n";
}

// Test 5: Check sync_status table
echo "\nTest 5: Checking sync_status...\n";
try {
    $status = DB::table('sync_status')->where('tenant_id', 1)->first();
    if ($status) {
        echo "✅ Sync status found:\n";
        echo "   Status: {$status->status}\n";
        echo "   Pending: {$status->pending_count}\n";
        echo "   Last synced: {$status->last_synced_at}\n";
    } else {
        echo "ℹ️ No sync_status record for tenant 1\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";