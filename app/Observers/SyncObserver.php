<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SyncObserver
{
    /**
     * Tables that sync: LOCAL → REMOTE (transactional data)
     * Changes to these tables are logged and pushed.
     */
    private array $pushTables = [
        // Sales & Orders
        'orders',
        'order_items',
        'order_payments',
        'order_taxes',
        
        // Purchasing
        'purchase_orders',
        'purchase_order_items',
        'purchase_receipts',
        'purchase_receipt_items',
        'received_product_variants',
        
        // Inventory
        'inventory_adjustments',
        'inventory_items',
        'inventory_transactions',
        'single_shop_inventory_logs',
        
        // Accounting
        'accounting_periods',
        'account_balances',
        'chart_of_accounts',
        'general_ledger',
        'journal_entries',
        'journal_entry_lines',
        
        // Expenses
        'expenses',
        'expense_categories',
        
        // HR / Employees
        'employees',
        'employee_advances',
        'employee_documents',
        'employee_payments',
        'leaves',
        
        // Customers & Suppliers
        'customers',
        'customer_groups',
        'suppliers',
        'supplier_tax_liabilities',
        
        // Products (Transactional changes - stock levels, prices)
        'products',
        'product_variants',
        'variant_taxes',
        
        // Promotions
        'promotions',
        'promotion_products',
        
        // Financial
        'currencies',
        'payment_methods',
        'payment_transaction_logs',
        'tax_liabilities',
        
        // Tenant related
        'tenants',
        'tenant_configurations',
        'tenant_settings',
        'tenant_usage_tracking',
        
        // Users & Auth
        'users',
        'model_has_roles',
        'model_has_permissions',
        
        
        // Departments & Locations (Transactional - assignments)
        'departments',
        'department_product',
        'department_user',
        'locations',
        'location_product',
        
        // Financial Reports
        'financial_report_templates',
        'report_sections',
        
        // Taxes (Transactional)
        'tax_product',
        

        
        // Advanced Variants
        'batch_logs',
        'invoices',
        'invoice_payments',
        'invoice_payment_webhooks',
        'invoice_sends',
        'recipes',
        'recipe_ingredients',
        'serial_numbers',

        // Productions
        'production_orders',
        'production_order_inputs',
        'production_order_outputs',

    ];

    /**
     * Tables that sync: REMOTE → LOCAL ONLY (master/catalog data)
     * Changes to these tables on LOCAL are IGNORED (never pushed upstream).
     * These are pulled from remote and should not be edited locally.
     */
    private array $pullOnlyTables = [
        // Product Catalog (Centrally Managed)
        'categories',
        'product_categories',
        
        // Tax Configuration
        'taxes',
        
        // Unit of Measures
        'unit_of_measures',
        'settings',
        
        
        // Master Data (Read-only from remote)
        'roles',
        'permissions',
        'role_has_permissions',

        // Sales & Orders
        'orders',
        'order_items',
        'order_payments',
        'order_taxes',
        
        // Purchasing
        'purchase_orders',
        'purchase_order_items',
        'purchase_receipts',
        'purchase_receipt_items',
        'received_product_variants',
        
        // Inventory
        'inventory_adjustments',
        'inventory_items',
        'inventory_transactions',
        'single_shop_inventory_logs',
        
        // Accounting
        'accounting_periods',
        'account_balances',
        'chart_of_accounts',
        'general_ledger',
        'journal_entries',
        'journal_entry_lines',
        
        // Expenses
        'expenses',
        'expense_categories',
        
        // HR / Employees
        'employees',
        'employee_advances',
        'employee_documents',
        'employee_payments',
        'leaves',
        
        // Customers & Suppliers
        'customers',
        'customer_groups',
        'suppliers',
        'supplier_tax_liabilities',
        
        // Products (Transactional changes - stock levels, prices)
        'products',
        'product_variants',
        'variant_taxes',
        
        // Promotions
        'promotions',
        'promotion_products',
        
        // Financial
        'currencies',
        'payment_methods',
        'payment_transaction_logs',
        'tax_liabilities',
        
        // Tenant related
        'tenants',
        'tenant_configurations',
        'tenant_settings',
        'tenant_usage_tracking',
        
        // Users & Auth
        'users',
        'model_has_roles',
        'model_has_permissions',
        
        
        // Departments & Locations (Transactional - assignments)
        'departments',
        'department_product',
        'department_user',
        'locations',
        'location_product',
        
        // Financial Reports
        'financial_report_templates',
        'report_sections',
        
        // Taxes (Transactional)
        'tax_product',
        

        
        // Advanced Variants
        'batch_logs',
        'invoices',
        'invoice_payments',
        'invoice_payment_webhooks',
        'invoice_sends',
        'recipes',
        'recipe_ingredients',
        'serial_numbers',
    ];

    /**
     * System tables - NEVER sync (ignore completely)
     */
    private array $systemTables = [
        'change_log',
        'sync_status',
        'migrations',
        'failed_jobs',
        'jobs',
        'job_batches',
        'billing_plans',
        'sessions',
        'cache',
        'cache_locks',
        'password_reset_tokens',
        'personal_access_tokens',
    ];

    public function created(Model $model): void
    {
        $this->logChange($model, 'INSERT');
    }

    public function updated(Model $model): void
    {
        // Only log if something actually changed
        if ($model->wasChanged()) {
            $this->logChange($model, 'UPDATE', $model->getOriginal());
        }
    }

    public function deleted(Model $model): void
    {
        $this->logChange($model, 'DELETE', $model->getOriginal());
    }

    /**
     * Core logging logic — transaction-safe via model events.
     */
    private function logChange(Model $model, string $operation, array $old = null): void
    {
        $table = $model->getTable();

        // Skip if not a tracked table
        if (!in_array($table, $this->pushTables, true)) {
            return;
        }

        // Never push local changes to pull-only (master) tables
        if (in_array($table, $this->pullOnlyTables, true)) {
            return;
        }

        // ── Skip if not a tracked table ─────────────────────────────────────
        $isPushTable = in_array($table, $this->pushTables, true);
        $isPullOnly = in_array($table, $this->pullOnlyTables, true);

        if (!$isPushTable && !$isPullOnly) {
            // Unknown table - log warning but don't sync
            \Log::debug("SyncObserver: Untracked table '{$table}' - change not logged");
            return;
        }

        // ── CRITICAL: Never push local changes to pull-only tables ──────────
        if ($isPullOnly) {
            \Log::debug("SyncObserver: Ignored local change to pull-only table '{$table}' (ID: {$model->getKey()})");
            return;
        }

        // ── Respect per-model shouldSync() logic ───────────────────────────
        if (method_exists($model, 'shouldSync') && !$model->shouldSync()) {
            return;
        }

        // ── Prepare payload ────────────────────────────────────────────────
        $attributes = $model->getAttributes();
        $tenantId = $attributes['tenant_id'] ?? config('sync.tenant_id', 2);
        $locationId = $attributes['location_id'] ?? null;

        // Remove sensitive/internal fields from payload
        $payload = array_diff_key($attributes, array_flip([
            'password', 'remember_token', 'api_token', '_skip_sync'
        ]));

        // ── Write to change_log (INSIDE the same transaction) ──────────────
        // If power fails here, BOTH the model change AND this log roll back
        try {
            DB::table('change_log')->insert([
                'table_name'  => $table,
                'row_id'      => $model->getKey(),
                'operation'   => $operation,
                'payload'     => json_encode($payload, JSON_THROW_ON_ERROR),
                'old_payload' => $old ? json_encode(array_diff_key($old, ['password' => 1, 'remember_token' => 1])) : null,
                'tenant_id'   => $tenantId,
                'location_id' => $locationId,
                'logged_at'   => now(),
                'retry_count' => 0,
                'synced_at'   => null,
                'sync_error'  => null,
            ]);
        } catch (\Throwable $e) {
            // Log but don't fail the main operation — sync is best-effort
            \Log::error("SyncObserver failed to log {$operation} for {$table}#{$model->getKey()}: " . $e->getMessage());
        }
    }
}