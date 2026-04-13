<?php
// test-api.php - Run with: php test-api.php

$remoteUrl = 'https://lael-pos.stardena.org';
$token = 'your-hardcoded-token-here'; // Use same token as in .env
$tenantId = 1;

echo "Testing API connectivity...\n\n";

// Test 1: Check status endpoint
echo "Test 1: GET /sync/status\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $remoteUrl . '/sync/status');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-Sync-Token: ' . $token,
    'X-Tenant-Id: ' . $tenantId
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: " . $httpCode . "\n";
echo "Response: " . $response . "\n\n";

// Test 2: Check if domain resolves
echo "Test 2: DNS resolution\n";
$ip = gethostbyname('lael-pos.stardena.org');
echo "IP Address: " . $ip . "\n";

if ($ip === 'lael-pos.stardena.org') {
    echo "❌ DNS resolution failed\n";
} else {
    echo "✅ DNS resolved\n";
}