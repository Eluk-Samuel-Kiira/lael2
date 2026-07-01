<?php
// database/migrations/2024_01_01_000005_create_general_ledger_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('general_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('journal_line_id')->constrained('journal_entry_lines')->restrictOnDelete();
            $table->foreignId('account_id')->constrained('chart_of_accounts')->restrictOnDelete();
            $table->date('entry_date');
            $table->foreignId('period_id')->constrained('accounting_periods')->restrictOnDelete();
            
            // 👇 Changed to BIGINT for storing in smallest currency unit
            $table->bigInteger('debit_amount')->default(0)->comment('Stored in smallest currency unit');
            $table->bigInteger('credit_amount')->default(0)->comment('Stored in smallest currency unit');
            $table->bigInteger('running_balance')->comment('Stored in smallest currency unit');
            
            $table->text('description')->nullable();
            $table->string('source_module')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'account_id', 'entry_date']);
            $table->index(['tenant_id', 'period_id']);
            $table->index(['source_module', 'source_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('general_ledger');
    }
};