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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('customer_name')->nullable();
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();
            $table->foreignId('department_id')->constrained('departments')->restrictOnDelete();
            $table->string('order_number', 50);
            $table->enum('type', ['sale', 'return', 'quote', 'layby']);
            $table->enum('status', ['draft', 'confirmed', 'processing', 'completed', 'cancelled', 'refunded']);
            
            // 👇 Changed to BIGINT for storing in smallest currency unit
            $table->bigInteger('subtotal')->comment('Stored in smallest currency unit');
            $table->bigInteger('discount_total')->default(0)->comment('Stored in smallest currency unit');
            $table->bigInteger('tax_total')->default(0)->comment('Stored in smallest currency unit');
            $table->bigInteger('total')->comment('Stored in smallest currency unit');
            $table->bigInteger('paid_amount')->default(0)->comment('Stored in smallest currency unit');
            $table->bigInteger('balance_due')->default(0)->comment('Stored in smallest currency unit');
            
            $table->enum('source', ['pos', 'online', 'phone', 'mobile'])->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestampsTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};