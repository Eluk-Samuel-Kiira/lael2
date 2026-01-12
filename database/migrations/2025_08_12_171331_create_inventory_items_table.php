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
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->integer('quantity_on_hand')->default(0);
            $table->integer('quantity_allocated')->default(0);
            $table->integer('quantity_on_order')->default(0);
            $table->integer('reorder_point')->nullable();
            $table->integer('preferred_stock_level')->default(0);
            $table->string('batch_number', 50)->nullable();
            $table->date('expiry_date')->nullable();

            $table->foreignId('variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->timestampsTz();

            // ✅ Enforce uniqueness only within department + location
            $table->unique(['variant_id', 'department_id', 'location_id'], 'unique_variant_location_department');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
