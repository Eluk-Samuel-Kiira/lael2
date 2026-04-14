<?php

// ═══════════════════════════════════════════════════════════════════════════════
// config/sync.php
//
// WHY THIS EXISTS:
// .env does not support JSON values reliably — special characters like ; % ! in
// passwords cause json_decode to fail silently, leaving tenantTokens empty.
// Defining tokens here avoids all quoting issues.
//
// PRODUCTION (cPanel) — add to config/sync.php directly:
//   'tenant_tokens' => [
//       '1' => 'your-super-secret-67890',
//       '2' => '123456789003',
//   ],
//
// LOCAL MACHINE — .env can override individual tokens:
//   SYNC_TOKEN=123456789003
//   TENANT_ID=2
// ═══════════════════════════════════════════════════════════════════════════════

return [

    // ── Tenant token map (used by SyncController on the remote/cPanel app) ───
    // Key   = tenant_id as a string
    // Value = the secret token the local machine sends in X-Sync-Token header
    //
    // Add one entry per tenant. Generate tokens with: php artisan tinker -> Str::random(64)
    'tenant_tokens' => array_filter([
        '1' => env('SYNC_TOKEN_TENANT_1', 'your-super-secret-67890'),
        '2' => env('SYNC_TOKEN_TENANT_2', '123456789003'),
        // Add more tenants:
        // '3' => env('SYNC_TOKEN_TENANT_3', ''),
    ]),

    // ── Local machine settings (used by SyncToRemote command) ────────────────

    // The URL of the remote/cPanel application
    'remote_url' => env('SYNC_REMOTE_URL', 'https://lael-pos.stardena.org'),

    // The token this machine uses to authenticate (matches one entry in tenant_tokens above)
    'token' => env('SYNC_TOKEN', ''),

    // The tenant this machine belongs to
    'tenant_id' => (int) env('TENANT_ID', 2),

    // Whether this is a local POS machine (true) or the remote/cPanel app (false)
    // Set IS_LOCAL_POS=true in local .env only
    'is_local' => filter_var(env('IS_LOCAL_POS', false), FILTER_VALIDATE_BOOLEAN),

    // How many change_log rows to push per sync run
    'batch_size' => (int) env('SYNC_BATCH_SIZE', 500),

    // Max retry attempts before a row is abandoned
    'max_retries' => (int) env('SYNC_MAX_RETRIES', 5),

    // Tables never pushed to remote
    'forbidden_tables' => [
        'change_log',
        'sync_status',
        'migrations',
        'password_reset_tokens',
        'sessions',
        'jobs',
        'failed_jobs',
    ],

    // Tables pulled from remote → local (master/catalog data only)
    'master_tables' => [
        'categories',
        'product_categories',
        'products',
        'taxes',
        'promotions',
        'unit_of_measures',
        'locations',
        'departments',
    ],
];