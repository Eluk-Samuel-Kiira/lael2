<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('received_product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('purchase_receipt_id')->constrained()->onDelete('cascade');
            $table->foreignId('purchase_order_item_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_variant_id')->constrained()->onDelete('cascade');
            
            // Receiving details
            $table->integer('quantity_received');
            $table->decimal('unit_cost', 12, 2);
            $table->decimal('total_cost', 12, 2);
            $table->string('batch_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->text('notes')->nullable();
            
            // Inventory tracking
            $table->integer('inventory_quantity_before')->default(0);
            $table->integer('inventory_quantity_after')->default(0);
            
            // Tenant and user tracking
            $table->foreignId('received_by')->constrained('users');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            // Indexes for better performance with custom names
            $table->index(['purchase_order_id', 'product_variant_id'], 'rpv_po_pv_index');
            $table->index(['batch_number', 'expiry_date'], 'rpv_batch_expiry_index');
            $table->index(['created_at'], 'rpv_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('received_product_variants');
    }
};