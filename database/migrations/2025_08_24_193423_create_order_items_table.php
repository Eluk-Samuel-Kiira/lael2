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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('item_name');
            $table->string('sku')->nullable();
            
            // 👇 Changed to BIGINT for storing in smallest currency unit
            $table->bigInteger('unit_price')->comment('Stored in smallest currency unit');
            $table->integer('quantity');
            $table->bigInteger('discount')->default(0)->comment('Stored in smallest currency unit');
            $table->bigInteger('tax_amount')->default(0)->comment('Stored in smallest currency unit');
            $table->bigInteger('total_price')->comment('Stored in smallest currency unit');
            
            $table->json('inventory_data')->nullable(); 
            $table->json('tax_data')->nullable(); 
            $table->json('promotion_data')->nullable(); 
            $table->timestampsTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};