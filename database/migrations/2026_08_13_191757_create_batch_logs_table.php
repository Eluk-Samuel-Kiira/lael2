<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batch_logs', function (Blueprint $table) {
            $table->id();
            
            // Batch reference
            $table->foreignId('batch_id')->constrained('purchase_receipt_items')->onDelete('cascade');
            $table->string('batch_number', 100)->nullable()->index();
            
            // Product details
            $table->foreignId('variant_id')->constrained('product_variants')->onDelete('cascade');
            $table->string('variant_name')->nullable();
            $table->string('variant_sku', 100)->nullable()->index();
            
            // Event type: received, depleted, adjusted, transferred, expired
            $table->enum('type', ['received', 'depleted', 'adjusted', 'transferred', 'expired'])
                ->default('received')
                ->index();
            
            // Quantity tracking
            $table->integer('quantity_change')->default(0);
            $table->integer('quantity_before')->default(0);
            $table->integer('quantity_after')->default(0);
            
            // Cost tracking
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
            
            // Reference links
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->string('order_number', 50)->nullable()->index();
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->onDelete('set null');
            $table->string('purchase_order_number', 50)->nullable()->index();
            $table->foreignId('purchase_receipt_id')->nullable()->constrained('purchase_receipts')->onDelete('set null');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('set null');
            $table->string('supplier_name')->nullable();
            
            // Tenant and location
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('location_id')->nullable()->constrained('locations')->onDelete('set null');
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('set null');
            
            // Expiry and dates
            $table->date('expiry_date')->nullable()->index();
            $table->timestamp('event_date')->nullable();
            
            // User who performed the action
            $table->foreignId('performed_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Additional metadata
            $table->json('metadata')->nullable();
            
            // Timestamps
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['batch_id', 'type']);
            $table->index(['variant_id', 'tenant_id']);
            $table->index(['batch_number', 'tenant_id']);
            $table->index(['order_id', 'type']);
            $table->index(['purchase_order_id', 'tenant_id']);
            $table->index(['supplier_id', 'tenant_id']);
            $table->index(['expiry_date', 'tenant_id']);
            $table->index(['event_date']);
            $table->index(['tenant_id', 'event_date']);
            $table->index(['location_id', 'department_id']);
            $table->index(['type', 'event_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_logs');
    }
};