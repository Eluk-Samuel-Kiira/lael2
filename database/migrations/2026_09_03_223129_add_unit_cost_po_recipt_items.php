<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ============================================
        // 1. Add unit_cost to purchase_receipt_items
        // ============================================
        if (!Schema::hasColumn('purchase_receipt_items', 'unit_cost')) {
            Schema::table('purchase_receipt_items', function (Blueprint $table) {
                $table->decimal('unit_cost', 15, 2)->nullable()
                    ->after('quantity_remaining')
                    ->comment('Actual unit cost from supplier at time of receipt');
                
                $table->index('unit_cost');
            });
        }

        // ============================================
        // 2. Update tenants status enum
        // ============================================
        // Check if the table exists and the column exists
        if (Schema::hasTable('tenants') && Schema::hasColumn('tenants', 'status')) {
            // Get current column type to check if it needs updating
            $columnType = DB::select("SHOW COLUMNS FROM tenants WHERE Field = 'status'");
            
            if (!empty($columnType)) {
                $type = $columnType[0]->Type;
                
                // Only modify if the enum doesn't already include all values
                $needsUpdate = true;
                
                // Check if all new values are already in the enum
                if (str_contains($type, "'expired'") && str_contains($type, "'inactive'")) {
                    $needsUpdate = false;
                }
                
                if ($needsUpdate) {
                    // Use a raw query to safely modify the enum
                    DB::statement("ALTER TABLE tenants MODIFY COLUMN status ENUM('active', 'suspended', 'trial', 'inactive', 'expired') DEFAULT 'trial'");
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // ============================================
        // 1. Drop unit_cost from purchase_receipt_items
        // ============================================
        if (Schema::hasColumn('purchase_receipt_items', 'unit_cost')) {
            Schema::table('purchase_receipt_items', function (Blueprint $table) {
                // Drop index first if it exists
                $table->dropIndex(['unit_cost']);
                $table->dropColumn('unit_cost');
            });
        }

        // ============================================
        // 2. Rollback tenants status enum
        // ============================================
        if (Schema::hasTable('tenants') && Schema::hasColumn('tenants', 'status')) {
            $columnType = DB::select("SHOW COLUMNS FROM tenants WHERE Field = 'status'");
            
            if (!empty($columnType)) {
                $type = $columnType[0]->Type;
                
                // Only rollback if the new values exist
                if (str_contains($type, "'expired'") || str_contains($type, "'inactive'")) {
                    DB::statement("ALTER TABLE tenants MODIFY COLUMN status ENUM('active', 'suspended', 'trial') DEFAULT 'trial'");
                }
            }
        }
    }
};