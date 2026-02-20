<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('supplier_id')->constrained()->onDelete('restrict');
            $table->foreignId('location_id')->constrained()->onDelete('restrict');
            $table->string('po_number', 50);
            $table->enum('status', ['draft', 'sent', 'pending_approval', 'approved', 'partially_received', 'received', 'cancelled']);
            $table->date('expected_delivery_date')->nullable();
            
            // 👇 Changed to BIGINT for storing in smallest currency unit
            $table->bigInteger('subtotal')->comment('Stored in smallest currency unit');
            $table->bigInteger('tax_total')->default(0)->comment('Stored in smallest currency unit');
            $table->bigInteger('total')->comment('Stored in smallest currency unit');
            
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            
            // Status tracking fields
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('sent_by')->nullable()->constrained('users');
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users');
            $table->timestamp('received_at')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users');
            
            $table->timestamps();

            $table->unique(['tenant_id', 'po_number']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('purchase_orders');
    }
};