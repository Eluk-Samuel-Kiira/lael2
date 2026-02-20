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
        Schema::create('payment_transaction_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('transaction_ref')->unique();
            
            // Payment Method Reference
            $table->foreignId('payment_method_id')->constrained('payment_methods')->cascadeOnDelete();
            
            // Transaction Details
            $table->enum('transaction_type', [
                'DEPOSIT', 'WITHDRAWAL', 'TRANSFER_IN', 'TRANSFER_OUT',
                'FEE', 'REFUND', 'ADJUSTMENT', 'RECONCILIATION'
            ])->index('idx_payment_logs_type');
            
            $table->enum('transaction_category', [
                'EXPENSE', 'PURCHASE_ORDER', 'PAYMENT', 'ORDER',
                'SALARY', 'INVOICE', 'REFUND', 'FEE', 'ADJUSTMENT', 'ALLOWANCE', 'BONUS',
                'OVERTIME', 'ADVANCE', 'OTHER'
            ])->index('idx_payment_logs_category');
            
            $table->string('reference_table')->nullable()->index('idx_payment_logs_ref_table');
            $table->unsignedBigInteger('reference_id')->nullable()->index('idx_payment_logs_ref_id');
            
            // Amount Information - 👇 Changed to BIGINT for storing in smallest currency unit
            $table->bigInteger('amount')->comment('Stored in smallest currency unit');
            $table->bigInteger('transaction_fee')->default(0)->comment('Stored in smallest currency unit');
            $table->bigInteger('net_amount')->comment('Stored in smallest currency unit');
            $table->bigInteger('balance_before')->comment('Stored in smallest currency unit');
            $table->bigInteger('balance_after')->comment('Stored in smallest currency unit');
            
            // Currency Information
            $table->foreignId('currency_id')->constrained('currencies');
            $table->decimal('exchange_rate', 12, 6)->default(1); // Stays as decimal for precision
            
            // Status & Timing
            $table->enum('status', [
                'PENDING', 'COMPLETED', 'FAILED', 'CANCELLED', 'REVERSED'
            ])->default('COMPLETED')->index('idx_payment_logs_status');
            
            $table->timestamp('transaction_date')->useCurrent();
            $table->timestamp('effective_date')->useCurrent();
            $table->timestamp('settlement_date')->nullable();
            
            // Description & Metadata
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->string('notes')->nullable();
            
            // References
            $table->string('external_reference')->nullable()->index('idx_payment_logs_ext_ref');
            $table->string('bank_reference')->nullable();
            $table->string('receipt_number')->nullable()->unique('idx_payment_logs_receipt');
            
            // User & Tenant Context
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            
            // Related Party
            $table->foreignId('counterparty_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            $table->string('counterparty_name')->nullable();
            $table->string('counterparty_account')->nullable();
            
            // Timestamps & Soft Deletes
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for better performance with custom names
            $table->index(
                ['payment_method_id', 'transaction_date'],
                'idx_payment_logs_method_date'
            );
            
            $table->index(
                ['tenant_id', 'transaction_date'],
                'idx_payment_logs_tenant_date'
            );
            
            $table->index(
                ['tenant_id', 'status', 'transaction_date'],
                'idx_payment_logs_tenant_status_date'
            );
            
            $table->index(
                ['reference_table', 'reference_id'],
                'idx_payment_logs_ref_composite'
            );
            
            $table->index(
                ['transaction_type', 'transaction_category'],
                'idx_payment_logs_type_category'
            );
            
            $table->index(
                ['user_id', 'transaction_date'],
                'idx_payment_logs_user_date'
            );
            
            $table->index(
                ['external_reference', 'tenant_id'],
                'idx_payment_logs_ext_ref_tenant'
            );
            
            $table->index(
                ['payment_method_id', 'status'],
                'idx_payment_logs_method_status'
            );
            
            $table->index(
                ['transaction_date', 'status'],
                'idx_payment_logs_date_status'
            );
            
            $table->index(
                ['created_at', 'tenant_id'],
                'idx_payment_logs_created_tenant'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transaction_logs');
    }
};