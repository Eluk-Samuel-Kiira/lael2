<?php
// database/migrations/2024_01_01_000006_create_account_balances_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('account_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('account_id')->constrained('chart_of_accounts')->cascadeOnDelete();
            $table->foreignId('period_id')->constrained('accounting_periods')->cascadeOnDelete();
            $table->date('balance_date');
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('debit_total', 15, 2)->default(0);
            $table->decimal('credit_total', 15, 2)->default(0);
            $table->decimal('closing_balance', 15, 2)->default(0);
            $table->timestamps();

            // 👇 Custom shorter names for constraints
            $table->unique(
                ['tenant_id', 'account_id', 'period_id', 'balance_date'],
                'acc_bal_unique'
            );
            
            $table->index(['tenant_id', 'balance_date'], 'acc_bal_tenant_date');
        });
    }

    public function down()
    {
        Schema::dropIfExists('account_balances');
    }
};