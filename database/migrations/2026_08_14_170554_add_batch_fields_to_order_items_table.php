<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // ✅ Add batch tracking fields
            $table->unsignedBigInteger('batch_id')->nullable()->after('variant_id');
            $table->string('batch_number', 100)->nullable()->after('batch_id');
            
            // ✅ Add serial tracking fields
            $table->unsignedBigInteger('serial_id')->nullable()->after('batch_number');
            $table->string('serial_number', 255)->nullable()->after('serial_id');
            
            // ✅ Add foreign key constraints (optional - if you want to enforce relationships)
            // $table->foreign('batch_id')->references('id')->on('purchase_receipt_items')->onDelete('set null');
            // $table->foreign('serial_id')->references('id')->on('serial_numbers')->onDelete('set null');
            
            // ✅ Add indexes for performance
            $table->index('batch_id');
            $table->index('batch_number');
            $table->index('serial_id');
            $table->index('serial_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // ✅ Drop foreign keys first if added
            // $table->dropForeign(['batch_id']);
            // $table->dropForeign(['serial_id']);
            
            // ✅ Drop columns
            $table->dropColumn([
                'batch_id', 
                'batch_number',
                'serial_id',
                'serial_number'
            ]);
        });
    }
};