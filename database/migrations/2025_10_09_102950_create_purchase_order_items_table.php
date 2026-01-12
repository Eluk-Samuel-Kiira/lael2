<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_variant_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete(); 
            $table->string('product_name', 100);
            $table->string('sku', 50);
            $table->integer('quantity');
            $table->decimal('unit_cost', 12, 2);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total_cost', 12, 2);
            $table->enum('payment_status', ['pending', 'partial', 'paid', 'overdue'])->default('pending'); // Added
            $table->date('payment_date')->nullable(); // Added
            $table->integer('received_quantity')->default(0);
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('purchase_order_items');
    }
};