<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_expenses_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id(); // Laravel uses 'id' as primary key, not 'expense_id'
            $table->unsignedBigInteger('tenant_id');
            
            // Basic Info
            $table->string('expense_number', 50);
            $table->date('date');
            $table->string('description', 255);
            
            // Categorization
            $table->unsignedBigInteger('category_id');
            $table->string('vendor_name', 200)->nullable();
            
            // Amounts
            $table->decimal('amount', 12, 2);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->storedAs('amount + tax_amount');
            
            // Payment Info
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();  
            $table->enum('payment_status', ['pending', 'paid', 'reimbursed'])->default('pending');
            $table->date('paid_date')->nullable();
            
            // Recurring Support
            $table->boolean('is_recurring')->default(false);
            $table->enum('recurring_frequency', ['weekly', 'monthly', 'quarterly', 'annually'])->nullable();
            $table->date('next_recurring_date')->nullable();
            
            // Attachments & Tracking
            $table->string('receipt_url', 255)->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('employee_id')->nullable();
            
            // Audit
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps(); // Laravel manages created_at and updated_at
            
            // Foreign key constraints
            $table->foreign('tenant_id')
                  ->references('id')
                  ->on('tenants')
                  ->onDelete('cascade');
                  
            $table->foreign('category_id')
                  ->references('id')
                  ->on('expense_categories')
                  ->onDelete('restrict');
                  
            $table->foreign('approved_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
                  
            $table->foreign('employee_id')
                  ->references('id')
                  ->on('employees')
                  ->onDelete('set null');
                  
            $table->foreign('created_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
            
            // Unique constraint
            $table->unique(['tenant_id', 'expense_number']);
            
            // Indexes for performance
            $table->index(['tenant_id', 'date']);
            $table->index(['tenant_id', 'category_id']);
            $table->index(['tenant_id', 'payment_status']);
            $table->index(['tenant_id', 'is_recurring']);
            $table->index('expense_number');
            $table->index('date');
        });
    }

    public function down()
    {
        Schema::dropIfExists('expenses');
    }
};