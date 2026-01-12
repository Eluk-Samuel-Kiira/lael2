<?php
// database/migrations/2024_01_01_000001_create_chart_of_accounts_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('account_code', 20);
            $table->string('account_name', 100);
            $table->enum('account_type', [
                'asset_current', 'asset_fixed', 'asset_non_current',
                'liability_current', 'liability_long_term',
                'equity', 'equity_retained_earnings',
                'revenue', 'revenue_other',
                'expense', 'expense_cost_of_goods', 'expense_operating'
            ]);
            $table->char('normal_balance', 1)->default('D'); // D = Debit, C = Credit
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system_account')->default(false);
            $table->text('description')->nullable();
            $table->foreignId('parent_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'account_code']);
            $table->unique(['tenant_id', 'account_name']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('chart_of_accounts');
    }
};