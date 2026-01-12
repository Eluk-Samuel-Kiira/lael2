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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            
            // Basic information
            $table->string('name');
            $table->string('type')->index();
            $table->string('code')->unique();
            $table->text('description')->nullable();
            
            // Account/Provider details
            $table->string('provider')->nullable();
            $table->string('account_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('iban')->nullable();
            $table->string('swift_bic')->nullable();
            $table->string('routing_number')->nullable();
            
            // For cards
            $table->string('card_last_four', 4)->nullable();
            $table->string('card_type')->nullable();
            $table->date('card_expiry_date')->nullable();
            
            // For digital wallets
            $table->string('wallet_id')->nullable();
            $table->string('wallet_email')->nullable();
            
            // Fees & Limits
            $table->decimal('transaction_fee_percentage', 5, 2)->default(0);
            $table->decimal('transaction_fee_fixed', 10, 2)->default(0);
            $table->decimal('min_transaction_amount', 15, 2)->default(0);
            $table->decimal('max_transaction_amount', 15, 2)->nullable();
            $table->decimal('daily_limit', 15, 2)->nullable();
            $table->decimal('monthly_limit', 15, 2)->nullable();
            
            // Balance information - CORRECTED SECTION
            $table->decimal('current_balance', 20, 2)->default(0.00);
            $table->decimal('available_balance', 20, 2)->default(0.00);
            $table->decimal('pending_balance', 20, 2)->default(0);
            $table->decimal('min_balance_limit', 20, 2)->nullable();
            $table->decimal('max_balance_limit', 20, 2)->nullable();
            $table->boolean('allow_negative_balance')->default(true);
            $table->date('last_reconciled_at')->nullable();
            $table->timestamp('last_transaction_at')->nullable();
            $table->decimal('last_transaction_amount', 20, 2)->nullable();
            $table->string('last_transaction_type', 10)->nullable();
            
            // Status & Configuration
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_online')->default(true);
            $table->boolean('requires_verification')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->date('verified_at')->nullable();
            
            // Security
            $table->string('token')->nullable();
            $table->text('api_key')->nullable();
            $table->text('secret_key')->nullable();
            $table->text('webhook_url')->nullable();
            
            // Integration settings
            $table->json('settings')->nullable();
            
            // Currency support
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->json('supported_currencies')->nullable();
            
            // For cash handling
            $table->foreignId('cash_handler_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('cash_location')->nullable();
            
            // Audit & Ownership
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for better performance
            $table->index(['tenant_id', 'is_active']);
            $table->index(['tenant_id', 'type', 'is_active']);
            $table->index(['tenant_id', 'is_default']);
            $table->index(['tenant_id', 'current_balance'], 'idx_payment_methods_tenant_balance');
            $table->index(['type', 'current_balance'], 'idx_payment_methods_type_balance');
            $table->index(['current_balance'], 'idx_payment_methods_balance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};