<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_payments', function (Blueprint $table) {
            $table->id();
            
            // Relationships
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('order_payment_id')->nullable()->constrained('order_payments')->nullOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            $table->foreignId('processed_by')->constrained('users')->cascadeOnDelete();
            
            // Payment Details
            $table->string('transaction_id', 100)->nullable();
            $table->string('reference_number')->nullable();
            $table->bigInteger('amount')->comment('Stored in smallest currency unit');
            $table->string('currency_code', 3)->default('UGX');
            
            // Payment Method Details
            $table->string('payment_method_name')->nullable();
            $table->string('card_last_four', 4)->nullable();
            $table->string('card_brand', 20)->nullable();
            $table->string('payment_gateway')->nullable();
            
            // Status
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded', 'pending_verification'])->default('pending');
            $table->enum('type', ['full', 'partial', 'deposit'])->default('full');
            
            // Dates
            $table->timestampTz('payment_date')->useCurrent();
            $table->timestampTz('verified_at')->nullable();
            $table->timestampTz('refunded_at')->nullable();
            
            // Notes
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->json('gateway_response')->nullable();
            
            $table->timestampsTz();
            $table->softDeletesTz();
            
            // Indexes
            $table->index(['invoice_id']);
            $table->index(['transaction_id']);
            $table->index(['status']);
            $table->index(['payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_payments');
    }
};