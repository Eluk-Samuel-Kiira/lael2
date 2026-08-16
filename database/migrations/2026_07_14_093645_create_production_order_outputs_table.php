<?php
// database/migrations/2026_01_15_000003_create_production_order_outputs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_order_outputs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_order_id');
            $table->unsignedBigInteger('product_variant_id');
            
            // ✅ Output batch tracking (optional fields - no foreign keys)
            $table->string('batch_number', 100)->nullable()->index();
            $table->unsignedBigInteger('serial_number_id')->nullable()->index();
            $table->string('serial_number', 100)->nullable()->index();
            
            // ✅ Quantities
            $table->decimal('planned_quantity', 15, 4)->default(0);
            $table->decimal('actual_quantity', 15, 4)->default(0);
            $table->decimal('defective_quantity', 15, 4)->default(0);
            $table->string('unit', 20)->default('kg');
            
            // ✅ Costs (stored as integers for base currency)
            $table->bigInteger('production_cost')->default(0);
            $table->bigInteger('selling_price')->default(0);
            $table->bigInteger('total_cost')->default(0);
            
            // ✅ Inventory strategy for the output product
            $table->enum('inventory_strategy', ['quantity', 'batch', 'serial'])->default('quantity');
            
            // ✅ Packaging
            $table->string('packaging_type', 100)->nullable();
            $table->decimal('packaging_weight', 15, 4)->nullable();
            
            // ✅ Quality
            $table->enum('quality_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // ✅ Indexes
            $table->index('production_order_id');
            $table->index('product_variant_id');
            $table->index('serial_number_id');
            $table->index('quality_status');
            $table->index('inventory_strategy');
            
            // ✅ Foreign keys - ONLY for required relationships
            $table->foreign('production_order_id')->references('id')->on('production_orders')->onDelete('cascade');
            $table->foreign('product_variant_id')->references('id')->on('product_variants');
            
            // ❌ REMOVE optional foreign keys to avoid errors
            // $table->foreign('serial_number_id')->references('id')->on('serial_numbers')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_order_outputs');
    }
};