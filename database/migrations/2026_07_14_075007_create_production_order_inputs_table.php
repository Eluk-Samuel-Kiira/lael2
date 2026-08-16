<?php
// database/migrations/2026_01_15_000002_create_production_order_inputs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_order_inputs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_order_id');
            $table->unsignedBigInteger('product_variant_id');
            
            // ✅ Source tracking - where did this raw material come from?
            $table->unsignedBigInteger('purchase_receipt_item_id')->nullable();
            $table->unsignedBigInteger('inventory_item_id')->nullable();
            $table->unsignedBigInteger('serial_number_id')->nullable();
            $table->string('serial_number', 100)->nullable();
            
            // ✅ Quantities
            $table->decimal('planned_quantity', 15, 4)->default(0);
            $table->decimal('actual_quantity', 15, 4)->default(0);
            $table->decimal('waste_quantity', 15, 4)->default(0);
            $table->string('unit', 20)->default('kg');
            
            // ✅ Costs (stored as integers for base currency)
            $table->bigInteger('estimated_cost')->default(0);
            $table->bigInteger('actual_cost')->default(0);
            
            // ✅ Quality
            $table->enum('quality_status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->text('quality_notes')->nullable();
            
            // ✅ Source type
            $table->string('source_type', 50)->nullable();
            
            $table->timestamps();
            
            // ✅ Indexes (no foreign keys for optional fields)
            $table->index('production_order_id');
            $table->index('product_variant_id');
            $table->index('purchase_receipt_item_id');
            $table->index('inventory_item_id');
            $table->index('serial_number_id');
            $table->index('quality_status');
            
            // ✅ Foreign keys (only for required relationships)
            $table->foreign('production_order_id')->references('id')->on('production_orders')->onDelete('cascade');
            $table->foreign('product_variant_id')->references('id')->on('product_variants');
            
            // ❌ REMOVE these optional foreign keys to avoid errors
            // $table->foreign('purchase_receipt_item_id')->references('id')->on('purchase_receipt_items')->onDelete('set null');
            // $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('set null');
            // $table->foreign('serial_number_id')->references('id')->on('serial_numbers')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_order_inputs');
    }
};