<?php
// database/migrations/2024_01_01_000002_create_accounting_periods_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('accounting_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('period_name', 50);
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['open', 'closed', 'locked'])->default('open');
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->boolean('is_fiscal_year_end')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'start_date', 'end_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('accounting_periods');
    }
};