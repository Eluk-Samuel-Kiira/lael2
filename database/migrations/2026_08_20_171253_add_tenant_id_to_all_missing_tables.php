<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * ONLY truly global/system tables that should NEVER have tenant_id
     * All other tables get tenant_id
     */
    protected array $excludedTables = [
        // Laravel system - truly global
        'migrations',
        'failed_jobs',
        'personal_access_tokens',
        
        // Cache system
        'cache',
        'cache_locks',
        
        // Queue system
        'jobs',
        'job_batches',
        
        // Telescope (debug)
        'telescope_entries',
        'telescope_entries_tags',
        'telescope_monitoring',
        
        // Spatie permission (global - shared across tenants)
        'permissions',
        'roles',
        'model_has_permissions',
        'model_has_roles',
        'role_has_permissions',
        
        // Global reference
        'tenants',
    ];

    public function up(): void
    {
        $tables = $this->getAllTablesNeedingTenantId();
        
        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable();
                $table->index('tenant_id');
            });
            
            echo "✅ Added tenant_id to: {$table}\n";
        }
        
        $this->logChanges($tables);
    }

    public function down(): void
    {
        $tables = $this->getAllTablesNeedingTenantId();
        
        foreach ($tables as $table) {
            if (Schema::hasColumn($table, 'tenant_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropIndex(['tenant_id']);
                    $table->dropColumn('tenant_id');
                });
                
                echo "❌ Removed tenant_id from: {$table}\n";
            }
        }
    }

    protected function getAllTablesNeedingTenantId(): array
    {
        $allTables = collect(Schema::getTableListing())
            ->map(fn ($t) => Str::afterLast($t, '.'))
            ->unique()
            ->values()
            ->toArray();

        $needingTenantId = [];

        foreach ($allTables as $table) {
            // Skip excluded tables
            if (in_array($table, $this->excludedTables, true)) {
                continue;
            }

            // Skip if already has tenant_id
            if (Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            // All other tables get tenant_id
            $needingTenantId[] = $table;
        }

        return $needingTenantId;
    }

    protected function logChanges(array $tables): void
    {
        if (empty($tables)) {
            echo "📌 No tables needed tenant_id - all good!\n";
            return;
        }

        $logFile = storage_path('logs/tenant_id_migration.log');
        $content = "=== Tenant ID Migration - " . now()->format('Y-m-d H:i:s') . " ===\n";
        $content .= "Total tables updated: " . count($tables) . "\n";
        $content .= "Tables:\n" . implode("\n", $tables) . "\n";
        $content .= "==========================================\n\n";
        
        file_put_contents($logFile, $content, FILE_APPEND);
    }
};