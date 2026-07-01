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
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->integer('quantity')->default(0);
            $table->integer('reference_id')->default(0); //Could link to purchase_order_items, order_items, etc.
            $table->string('reference_type', 100)->nullable(); //'purchase_order', 'sales_order', etc. 
            $table->enum('type', ['purchase', 'sale', 'return', 'adjustment', 'transfer_in', 'transfer_out'])->default('sale');
            $table->text('notes')->nullable();


            $table->foreignId('inventory_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
