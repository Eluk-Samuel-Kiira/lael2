<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_employees_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('department_id')->nullable()->constrained()->onDelete('set null');
            
            // Personal Information
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('residence')->nullable();
            
            // Employment Details
            $table->date('hire_date');
            $table->date('termination_date')->nullable();
            $table->string('job_title')->nullable();
            $table->enum('employee_type', ['permanent', 'contract', 'casual', 'temporary', 'intern', 'probation'])->default('permanent');
            $table->boolean('is_active')->default(true);
            
            // Salary Information
            $table->bigInteger('salary')->nullable()->comment('Salary in smallest currency unit (e.g., cents)');
            $table->enum('salary_type', ['hourly', 'weekly', 'monthly', 'quarterly', 'annual'])->nullable();
            $table->boolean('is_salary_recurring')->default(false);
            $table->integer('recurring_day')->nullable()->comment('Day of month for salary payment');
            
            // Tax & Social Security
            $table->string('nssf_number')->nullable()->comment('Social Security Fund Number');
            $table->string('tin_number')->nullable()->comment('Tax Identification Number');
            
            // Bank Details
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_branch')->nullable();
            
            // Identification
            $table->enum('id_type', ['national_id', 'passport', 'drivers_license', 'voters_card', 'other'])->nullable();
            $table->string('id_number')->nullable();
            $table->string('qualification')->nullable()->comment('Highest qualification');
            
            // Next of Kin
            $table->string('next_of_kin_name')->nullable();
            $table->string('next_of_kin_contact')->nullable();
            $table->string('next_of_kin_relationship')->nullable();
            
            // Documents (JSON field to store multiple document paths)
            $table->json('documents')->nullable()->comment('CV, contracts, IDs, etc.');
            
            // Additional Info
            $table->text('notes')->nullable();
            
            $table->timestamps();

            $table->unique(['tenant_id', 'email']);
            $table->unique(['tenant_id', 'nssf_number'])->nullable();
            $table->unique(['tenant_id', 'tin_number'])->nullable();
            $table->index('employee_type');
            $table->index('is_active');
        });
    }

    public function down()
    {
        Schema::dropIfExists('employees');
    }
};