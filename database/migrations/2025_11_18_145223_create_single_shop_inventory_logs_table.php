<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('single_shop_inventory_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')->constrained('product_variants')->onDelete('cascade');
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            
            // Quantity tracking
            $table->integer('quantity_before');
            $table->integer('quantity_after');
            $table->integer('quantity_change'); // Positive for additions, negative for deductions
            
            // Context
            $table->string('reason'); // pos_sale, stock_adjustment, return, etc.
            $table->text('notes')->nullable();
            
            // Metadata
            $table->string('source')->default('pos'); // pos, manual, import, etc.
            $table->json('metadata')->nullable(); // Additional context data
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['variant_id', 'created_at']);
            $table->index(['order_id', 'variant_id']);
            $table->index(['tenant_id', 'reason']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('single_shop_inventory_logs');
    }
};