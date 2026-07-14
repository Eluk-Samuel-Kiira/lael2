<?php
// database/migrations/2026_07_14_000003_create_production_order_outputs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_order_outputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained()->onDelete('cascade');
            
            // What finished good is being produced
            $table->foreignId('product_variant_id')->constrained('product_variants')->onDelete('restrict');
            
            // ✅ This IS the batch number (from production order)
            $table->string('batch_no', 50)->nullable()->comment('Auto-generated from production number');
            
            // Quantities
            $table->decimal('planned_quantity', 12, 4);
            $table->decimal('actual_quantity', 12, 4)->default(0);
            $table->decimal('defective_quantity', 12, 4)->default(0);
            $table->string('unit', 20)->default('pcs');
            
            // Packaging
            $table->string('packaging_type', 50)->nullable();
            $table->decimal('packaging_weight', 10, 4)->nullable();
            
            // Cost & Pricing
            $table->bigInteger('production_cost')->default(0);
            $table->bigInteger('selling_price')->nullable();
            
            // Quality
            $table->enum('quality_status', ['pending', 'approved', 'rejected'])->default('pending');
            
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('production_order_id');
            $table->index('product_variant_id');
            $table->index('batch_no');
            $table->unique(['production_order_id', 'product_variant_id'], 'unique_production_output');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_order_outputs');
    }
};