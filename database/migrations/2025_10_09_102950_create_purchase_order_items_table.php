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
            
            // 👇 Changed to BIGINT for storing in smallest currency unit
            $table->bigInteger('unit_cost')->comment('Stored in smallest currency unit');
            $table->bigInteger('tax_amount')->default(0)->comment('Stored in smallest currency unit');
            $table->bigInteger('total_cost')->comment('Stored in smallest currency unit');
            
            $table->enum('payment_status', ['pending', 'partial', 'paid', 'overdue'])->default('pending');
            $table->date('payment_date')->nullable();
            $table->integer('received_quantity')->default(0);
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('purchase_order_items');
    }
};