<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_employee_advances_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('employee_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_id')->nullable()->constrained('employee_payments')->onDelete('set null');
            
            // Advance Details - Using BIGINT for monetary values (stored in smallest currency unit)
            $table->bigInteger('advance_amount')->comment('Stored in smallest currency unit');
            $table->bigInteger('remaining_amount')->comment('Stored in smallest currency unit');
            $table->date('advance_date');
            $table->date('request_date');
            $table->date('approval_date')->nullable();
            
            // Deduction Configuration
            $table->enum('deduction_frequency', ['one_time', 'weekly', 'monthly', 'yearly'])->default('one_time');
            $table->integer('installments')->nullable()->comment('Number of installments to deduct');
            $table->integer('installments_paid')->default(0);
            $table->bigInteger('installment_amount')->nullable()->comment('Stored in smallest currency unit');
            $table->integer('deduction_day')->nullable()->comment('Day of month/week for deduction');
            
            // Deduction Period
            $table->date('deduction_start_date')->nullable();
            $table->date('deduction_end_date')->nullable();
            
            // Which salary types this advance applies to
            $table->json('applicable_salary_types')->nullable()->comment('JSON array of salary types this advance applies to');
            
            // Purpose & Reason
            $table->string('purpose')->nullable();
            $table->text('reason')->nullable();
            
            // Approval & Status
            $table->enum('status', ['pending', 'approved', 'partially_paid', 'fully_paid', 'rejected', 'cancelled'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            
            $table->json('deduction_schedule')->nullable(); // Store planned deductions
            $table->json('deduction_history')->nullable(); // Store actual deductions made
            $table->text('notes')->nullable();
            
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'status']);
            $table->index(['tenant_id', 'status']);
            $table->index('advance_date');
        });
    }

    public function down()
    {
        Schema::dropIfExists('employee_advances');
    }
};