<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_employee_payments_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('employee_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->date('payment_date');
            $table->enum('payment_type', ['salary', 'allowance', 'bonus', 'overtime', 'advance', 'other']);
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();            
            $table->string('reference_number')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->date('pay_period_start')->nullable();
            $table->date('pay_period_end')->nullable();
            $table->decimal('hours_worked', 8, 2)->nullable();
            $table->decimal('hourly_rate', 8, 2)->nullable();
            $table->json('breakdown')->nullable(); // For detailed payment breakdown
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'payment_date']);
            $table->index(['tenant_id', 'payment_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('employee_payments');
    }
};