<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('serial_numbers', function (Blueprint $table) {
            $table->id();
            
            // Product reference
            $table->foreignId('variant_id')->constrained('product_variants')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            
            // Serial number
            $table->string('serial_number', 100)->unique()->index();
            
            // Status
            $table->enum('status', [
                'available', 'sold', 'reserved', 'returned', 'lost', 'damaged'
            ])->default('available')->index();
            
            // Sales tracking
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->timestamp('sold_at')->nullable();
            $table->foreignId('sold_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Location tracking
            $table->foreignId('location_id')->nullable()->constrained('locations')->onDelete('set null');
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('set null');
            
            // Purchase tracking (where it came from)
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->onDelete('set null');
            $table->foreignId('purchase_receipt_id')->nullable()->constrained('purchase_receipts')->onDelete('set null');
            $table->foreignId('batch_id')->nullable()->constrained('purchase_receipt_items')->onDelete('set null');
            
            // Expiry
            $table->date('expiry_date')->nullable()->index();
            
            // Notes
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Composite indexes for common queries
            $table->index(['variant_id', 'status']);
            $table->index(['tenant_id', 'status']);
            $table->index(['location_id', 'department_id']);
            $table->index(['variant_id', 'location_id', 'department_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('serial_numbers');
    }
};