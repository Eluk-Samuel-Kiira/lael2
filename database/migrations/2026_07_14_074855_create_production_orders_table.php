<?php
// database/migrations/2026_01_15_000001_create_production_orders_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('location_id')->nullable();
            
            $table->string('production_number', 50)->unique();
            $table->enum('status', [
                'draft', 
                'in_progress', 
                'completed', 
                'cancelled'
            ])->default('draft');
            
            $table->dateTime('scheduled_date')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            
            // ✅ Totals
            $table->decimal('total_input_quantity', 15, 4)->default(0);
            $table->decimal('total_output_quantity', 15, 4)->default(0);
            
            // ✅ Costs (stored as integers for base currency)
            $table->bigInteger('total_input_cost')->default(0);
            $table->bigInteger('total_output_cost')->default(0);
            $table->bigInteger('total_cost')->default(0);
            $table->bigInteger('estimated_cost')->default(0)->nullable();
            
            // ✅ Payment tracking
            $table->unsignedBigInteger('payment_method_id')->nullable();
            $table->string('payment_transaction_ref', 100)->nullable();
            
            // ✅ Foreign keys
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('started_by')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // ✅ Indexes
            $table->index('tenant_id');
            $table->index('status');
            $table->index('production_number');
            $table->index('scheduled_date');
            $table->index('payment_method_id');
            $table->index('payment_transaction_ref');
            
            // ✅ Foreign keys
            $table->foreign('tenant_id')->references('id')->on('tenants');
            $table->foreign('location_id')->references('id')->on('locations');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('started_by')->references('id')->on('users');
            $table->foreign('completed_by')->references('id')->on('users');
            $table->foreign('cancelled_by')->references('id')->on('users');
            $table->foreign('payment_method_id')->references('id')->on('payment_methods')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_orders');
    }
};