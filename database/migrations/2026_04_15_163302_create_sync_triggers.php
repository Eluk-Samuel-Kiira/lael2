<?php
// database/migrations/2026_04_16_000000_create_sync_triggers.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration
{
    private array $rawTables = [
        // Inventory logs (often written via raw queries for performance)
        'stock_ledger', 'inventory_snapshots', 'stock_transfer_logs',
        
        // Accounting journals (bulk inserts)
        'ledger_entries', 'trial_balance_temp',
        
        // Audit / activity logs
        'user_activity_logs', 'pos_session_logs', 'device_heartbeats',
        
        // Temporary / staging tables
        'import_staging', 'export_queue', 'sync_buffer',
        
        // Add your 20-30 raw tables here
    ];

    public function up(): void
    {
        $driver = DB::getDriverName();
        $tenantId = (int) config('sync.tenant_id', 2); // Default fallback

        foreach ($this->rawTables as $table) {
            if (!Schema::hasTable($table)) continue;

            if ($driver === 'mysql') {
                $this->createMysqlTriggers($table, $tenantId);
            } elseif ($driver === 'pgsql') {
                $this->createPostgresTriggers($table, $tenantId);
            }
            // SQLite: skip triggers (use Observer-only mode for local dev)
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        
        foreach ($this->rawTables as $table) {
            if ($driver === 'mysql') {
                DB::unprepared("DROP TRIGGER IF EXISTS `{$table}_ai`");
                DB::unprepared("DROP TRIGGER IF EXISTS `{$table}_au`");
                DB::unprepared("DROP TRIGGER IF EXISTS `{$table}_ad`");
            } elseif ($driver === 'pgsql') {
                DB::unprepared("DROP TRIGGER IF EXISTS {$table}_sync_trigger ON {$table}");
                DB::unprepared("DROP FUNCTION IF EXISTS log_{$table}_change()");
            }
        }
    }

    private function createMysqlTriggers(string $table, int $defaultTenantId): void
    {
        // AFTER INSERT
        DB::unprepared("
            CREATE TRIGGER `{$table}_ai` AFTER INSERT ON `{$table}`
            FOR EACH ROW
            BEGIN
                INSERT INTO change_log 
                (table_name, row_id, operation, payload, tenant_id, location_id, logged_at)
                VALUES 
                (
                    '{$table}',
                    NEW.id,
                    'INSERT',
                    JSON_OBJECT(
                        'id', NEW.id,
                        'tenant_id', IFNULL(NEW.tenant_id, {$defaultTenantId}),
                        'location_id', IFNULL(NEW.location_id, NULL)
                        -- Add more fields as needed, or use JSON_MERGE for full row
                    ),
                    IFNULL(NEW.tenant_id, {$defaultTenantId}),
                    IFNULL(NEW.location_id, NULL),
                    NOW()
                );
            END
        ");

        // AFTER UPDATE
        DB::unprepared("
            CREATE TRIGGER `{$table}_au` AFTER UPDATE ON `{$table}`
            FOR EACH ROW
            BEGIN
                IF OLD.id IS NOT NULL THEN
                    INSERT INTO change_log 
                    (table_name, row_id, operation, payload, old_payload, tenant_id, location_id, logged_at)
                    VALUES 
                    (
                        '{$table}',
                        NEW.id,
                        'UPDATE',
                        JSON_OBJECT('id', NEW.id, 'tenant_id', IFNULL(NEW.tenant_id, {$defaultTenantId})),
                        JSON_OBJECT('id', OLD.id, 'tenant_id', IFNULL(OLD.tenant_id, {$defaultTenantId})),
                        IFNULL(NEW.tenant_id, {$defaultTenantId}),
                        IFNULL(NEW.location_id, NULL),
                        NOW()
                    );
                END IF;
            END
        ");

        // AFTER DELETE
        DB::unprepared("
            CREATE TRIGGER `{$table}_ad` AFTER DELETE ON `{$table}`
            FOR EACH ROW
            BEGIN
                INSERT INTO change_log 
                (table_name, row_id, operation, old_payload, tenant_id, location_id, logged_at)
                VALUES 
                (
                    '{$table}',
                    OLD.id,
                    'DELETE',
                    NULL,
                    JSON_OBJECT('id', OLD.id, 'tenant_id', IFNULL(OLD.tenant_id, {$defaultTenantId})),
                    IFNULL(OLD.tenant_id, {$defaultTenantId}),
                    IFNULL(OLD.location_id, NULL),
                    NOW()
                );
            END
        ");
    }

    private function createPostgresTriggers(string $table, int $defaultTenantId): void
    {
        // Function that handles all 3 operations
        DB::unprepared("
            CREATE OR REPLACE FUNCTION log_{$table}_change()
            RETURNS TRIGGER AS \$\$
            DECLARE
                payload_json JSONB;
                old_json JSONB;
                tenant_val BIGINT;
                location_val BIGINT;
            BEGIN
                -- Determine tenant_id from NEW or OLD or fallback
                tenant_val := COALESCE(
                    (SELECT NEW.tenant_id), 
                    (SELECT OLD.tenant_id), 
                    {$defaultTenantId}
                );
                location_val := COALESCE(
                    (SELECT NEW.location_id), 
                    (SELECT OLD.location_id)
                );

                IF TG_OP = 'INSERT' THEN
                    payload_json := jsonb_build_object('id', NEW.id, 'tenant_id', tenant_val);
                    INSERT INTO change_log (table_name, row_id, operation, payload, tenant_id, location_id, logged_at)
                    VALUES ('{$table}', NEW.id, 'INSERT', payload_json, tenant_val, location_val, NOW());
                    RETURN NEW;
                    
                ELSIF TG_OP = 'UPDATE' THEN
                    payload_json := jsonb_build_object('id', NEW.id, 'tenant_id', tenant_val);
                    old_json := jsonb_build_object('id', OLD.id, 'tenant_id', COALESCE(OLD.tenant_id, {$defaultTenantId}));
                    INSERT INTO change_log (table_name, row_id, operation, payload, old_payload, tenant_id, location_id, logged_at)
                    VALUES ('{$table}', NEW.id, 'UPDATE', payload_json, old_json, tenant_val, location_val, NOW());
                    RETURN NEW;
                    
                ELSIF TG_OP = 'DELETE' THEN
                    old_json := jsonb_build_object('id', OLD.id, 'tenant_id', COALESCE(OLD.tenant_id, {$defaultTenantId}));
                    INSERT INTO change_log (table_name, row_id, operation, old_payload, tenant_id, location_id, logged_at)
                    VALUES ('{$table}', OLD.id, 'DELETE', NULL, old_json, COALESCE(OLD.tenant_id, {$defaultTenantId}), COALESCE(OLD.location_id, NULL), NOW());
                    RETURN OLD;
                END IF;
                RETURN NULL;
            END;
            \$\$ LANGUAGE plpgsql
        ");

        // Attach trigger to table
        DB::unprepared("
            CREATE TRIGGER {$table}_sync_trigger
            AFTER INSERT OR UPDATE OR DELETE ON {$table}
            FOR EACH ROW EXECUTE FUNCTION log_{$table}_change()
        ");
    }
};