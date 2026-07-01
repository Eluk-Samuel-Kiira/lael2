<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('purchase_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('received_by')->nullable()->constrained('users');
            $table->timestamp('received_at')->useCurrent();
            $table->text('notes')->nullable();

            // Add to purchase_receipts migration
            $table->bigInteger('subtotal')->nullable()->comment('Stored in smallest currency unit');
            $table->bigInteger('tax_total')->nullable()->comment('Stored in smallest currency unit');
            $table->bigInteger('total')->nullable()->comment('Stored in smallest currency unit');
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('purchase_receipts');
    }
};