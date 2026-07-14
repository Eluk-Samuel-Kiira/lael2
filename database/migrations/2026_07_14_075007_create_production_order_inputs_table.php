<?php
// database/migrations/2026_07_14_000002_create_production_order_inputs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_order_inputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained()->onDelete('cascade');
            
            // What raw material is being consumed
            $table->foreignId('product_variant_id')->constrained('product_variants')->onDelete('restrict');
            
            // ✅ Optional: track which supplier batch this came from
            $table->string('batch_no', 50)->nullable()->comment('Supplier batch number');
            $table->foreignId('purchase_order_item_id')->nullable()->constrained()->onDelete('set null');
            
            // Quantities
            $table->decimal('planned_quantity', 12, 4);
            $table->decimal('actual_quantity', 12, 4)->default(0);
            $table->decimal('waste_quantity', 12, 4)->default(0);
            $table->string('unit', 20)->default('kg');
            
            // Cost
            $table->bigInteger('estimated_cost')->default(0);
            $table->bigInteger('actual_cost')->default(0);
            
            // Quality
            $table->enum('quality_status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->text('quality_notes')->nullable();
            
            $table->timestamps();

            $table->index('production_order_id');
            $table->index('product_variant_id');
            $table->index('batch_no');
            $table->unique(['production_order_id', 'product_variant_id'], 'unique_production_input');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_order_inputs');
    }
};