<?php

// ═══════════════════════════════════════════════════════════════════════════════
// config/sync.php
// ═══════════════════════════════════════════════════════════════════════════════

return [

    // ── Local machine settings (used by SyncToRemote command) ────────────────

    'remote_url' => env('SYNC_REMOTE_URL', 'https://suite.stardena.org'),
    'token' => env('SYNC_TOKEN', ''),
    'tenant_id' => (int) env('TENANT_ID', 2),
    'is_local' => filter_var(env('IS_LOCAL_POS', false), FILTER_VALIDATE_BOOLEAN),
    'batch_size' => (int) env('SYNC_BATCH_SIZE', 500),
    'max_retries' => (int) env('SYNC_MAX_RETRIES', 5),

    // ─── TENANT TOKENS ──────────────────────────────────────────────────────────
    // ✅ FIX: Read from .env using the SYNC_TOKEN_TENANT_{id} format
    'tenant_tokens' => [
        1 => env('SYNC_TOKEN_TENANT_1', ''),
        2 => env('SYNC_TOKEN_TENANT_2', ''),
        3 => env('SYNC_TOKEN_TENANT_3', ''),
        4 => env('SYNC_TOKEN_TENANT_4', ''),
        5 => env('SYNC_TOKEN_TENANT_5', ''),
        6 => env('SYNC_TOKEN_TENANT_6', ''),
        7 => env('SYNC_TOKEN_TENANT_7', ''),
        8 => env('SYNC_TOKEN_TENANT_8', ''),
        9 => env('SYNC_TOKEN_TENANT_9', ''),
        10 => env('SYNC_TOKEN_TENANT_10', ''),
    ],

    // ─── Table Lists ────────────────────────────────────────────────────────────

    'forbidden_tables' => [
        'change_log', 'sync_status', 'migrations', 'password_reset_tokens',
        'sessions', 'jobs', 'failed_jobs',
    ],

    'master_tables' => [
        'categories', 'product_categories', 'products', 'product_variants',
        'variant_taxes', 'unit_of_measures', 'taxes', 'tax_product',
        'promotions', 'promotion_products', 'currencies', 'payment_methods',
        'expense_categories', 'customer_groups', 'billing_plans', 'locations',
        'departments', 'financial_report_templates', 'report_sections',
        'roles', 'permissions', 'role_has_permissions',
    ],

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
        'batch_logs', 'invoices', 'invoice_payments', 'invoice_payment_webhooks',
        'invoice_sends', 'recipes', 'recipe_ingredients', 'serial_numbers',
    ],
];