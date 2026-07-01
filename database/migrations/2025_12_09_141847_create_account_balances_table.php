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
            
            // 👇 Changed to BIGINT for storing in smallest unit
            $table->bigInteger('opening_balance')->default(0)->comment('Stored in smallest currency unit');
            $table->bigInteger('debit_total')->default(0)->comment('Stored in smallest currency unit');
            $table->bigInteger('credit_total')->default(0)->comment('Stored in smallest currency unit');
            
            // 👇 If you want to store closing_balance (or compute it on the fly)
            $table->bigInteger('closing_balance')->default(0)->comment('Stored in smallest currency unit')->nullable();
            
            $table->timestamps();

            // Custom shorter names for constraints
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