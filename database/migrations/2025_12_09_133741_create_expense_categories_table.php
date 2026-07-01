<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_expense_categories_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('name', 100);
            $table->string('code', 20);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('gl_account_id')->nullable();
            $table->boolean('is_active')->default(true);
            
            // Add these missing columns:
            $table->boolean('requires_receipt')->default(true);
            $table->boolean('requires_approval')->default(false);
            $table->decimal('budget_monthly', 12, 2)->nullable();
            $table->decimal('budget_annual', 12, 2)->nullable();
            
            $table->timestamps();

            $table->foreign('gl_account_id')
                  ->references('id')
                  ->on('chart_of_accounts')
                  ->onDelete('set null');

            $table->unique(['tenant_id', 'name']);
            $table->index(['tenant_id', 'is_active']);
            $table->index('code');
        });
    }

    public function down()
    {
        Schema::dropIfExists('expense_categories');
    }
};