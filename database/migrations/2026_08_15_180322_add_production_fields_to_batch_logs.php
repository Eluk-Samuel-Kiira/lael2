<?php
// database/migrations/2026_08_15_180322_add_production_fields_to_batch_logs.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('batch_logs', function (Blueprint $table) {
            // ✅ Add production order columns
            $table->unsignedBigInteger('production_order_id')->nullable()->after('purchase_receipt_id');
            $table->unsignedBigInteger('production_order_input_id')->nullable()->after('production_order_id');
            $table->unsignedBigInteger('production_order_output_id')->nullable()->after('production_order_input_id');
            
            // ✅ Foreign keys
            $table->foreign('production_order_id')->references('id')->on('production_orders')->onDelete('set null');
            $table->foreign('production_order_input_id')->references('id')->on('production_order_inputs')->onDelete('set null');
            $table->foreign('production_order_output_id')->references('id')->on('production_order_outputs')->onDelete('set null');
            
            // ✅ Indexes
            $table->index('production_order_id');
            $table->index('production_order_input_id');
            $table->index('production_order_output_id');
            
            // ✅ Drop and recreate the type column to add new enum values
            // First, drop the existing index on type (if it exists)
            try {
                $table->dropIndex('batch_logs_type_index');
            } catch (\Exception $e) {
                // Index might not exist or already dropped
            }
            
            // Now change the column
            $table->enum('type', [
                'received', 'depleted', 'transferred', 'adjusted', 
                'consumed', 'produced', 'returned'
            ])->default('received')->change();
            
            // Re-add the index (optional - if you need it)
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::table('batch_logs', function (Blueprint $table) {
            // ✅ Drop foreign keys
            $table->dropForeign(['production_order_id']);
            $table->dropForeign(['production_order_input_id']);
            $table->dropForeign(['production_order_output_id']);
            
            // ✅ Drop columns
            $table->dropColumn(['production_order_id', 'production_order_input_id', 'production_order_output_id']);
            
            // ✅ Revert type enum
            try {
                $table->dropIndex('batch_logs_type_index');
            } catch (\Exception $e) {
                // Index might not exist
            }
            
            $table->enum('type', ['received', 'depleted', 'transferred', 'adjusted'])->default('received')->change();
        });
    }
};