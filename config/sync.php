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
        // Product Catalog (Centrally Managed)
        'categories',
        'product_categories', 
        'products',
        'product_variants',
        'variant_taxes',
        'unit_of_measures',
        
        // Tax Configuration
        'taxes',
        'tax_product',
        
        // Promotions
        'promotions',
        'promotion_products',
        
        // Master Data
        'currencies',
        'payment_methods',
        'expense_categories',
        'customer_groups',
        'billing_plans',
        'locations',
        'departments',
        
        // Financial Templates
        'financial_report_templates',
        'report_sections',
        
        // Roles & Permissions (Pull from central)
        'roles',
        'permissions',
        'role_has_permissions',
        
        // System Tables (Never Sync)
        // 'migrations',           // Already in skip list
        // 'failed_jobs',          // Already in skip list
        // 'jobs',                 // Already in skip list
        // 'job_batches',          // Already in skip list
        // 'sessions',             // Already in skip list
        // 'cache',                // Already in skip list
        // 'cache_locks',          // Already in skip list
        // 'password_reset_tokens',// Already in skip list
        // 'change_log',           // Already in skip list
        // 'sync_status',          // Already in skip list
    ],

    // Transactional tables - pushed from local to remote
    'transactional_tables' => [
        'orders', 'order_items', 'order_payments', 'order_taxes',
        'purchase_orders', 'purchase_order_items', 'purchase_receipts',
        'purchase_receipt_items', 'received_product_variants',
        'inventory_adjustments', 'inventory_items', 'inventory_transactions',
        'single_shop_inventory_logs', 'accounting_periods', 'account_balances',
        'chart_of_accounts', 'general_ledger', 'journal_entries',
        'journal_entry_lines', 'expenses', 'expense_categories', 'employees',
        'employee_advances', 'employee_documents', 'employee_payments',
        'leaves', 'customers', 'customer_groups', 'suppliers',
        'supplier_tax_liabilities', 'products', 'product_variants',
        'variant_taxes', 'promotions', 'promotion_products', 'currencies',
        'payment_methods', 'payment_transaction_logs', 'tax_liabilities',
        'tenants', 'tenant_configurations', 'tenant_settings',
        'tenant_usage_tracking', 'users', 'billing_plans', 'settings',
        'departments', 'department_product', 'department_user', 'locations',
        'location_product', 'financial_report_templates', 'report_sections',
        'tax_product', 'model_has_roles', 'model_has_permissions',
    ],
];