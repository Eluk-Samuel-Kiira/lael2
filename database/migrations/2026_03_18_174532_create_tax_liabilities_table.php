<?php
// database/migrations/[timestamp]_create_tax_liabilities_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tax_liabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('employee_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('employee_payment_id')->constrained('employee_payments')->onDelete('cascade');
            $table->foreignId('tax_id')->nullable()->constrained()->onDelete('set null');
            
            // Tax Details
            $table->bigInteger('amount')->comment('Stored in smallest currency unit');
            $table->string('tax_name');
            $table->decimal('rate', 10, 2);
            $table->string('tax_code')->nullable();
            $table->enum('tax_type', ['percentage', 'fixed'])->default('percentage');
            
            // Liability Status
            $table->enum('status', [
                'pending',      // Withheld but not yet remitted
                'remitted',     // Paid to tax authority
                'overdue',      // Past due date
                'cancelled'     // Voided/adjusted
            ])->default('pending');
            
            // Due Dates
            $table->date('due_date')->nullable();
            $table->date('remitted_at')->nullable();
            $table->string('remittance_reference')->nullable();
            
            // Tax Period
            $table->integer('tax_year');
            $table->integer('tax_month')->nullable();
            $table->integer('tax_quarter')->nullable();
            
            // Payment Details (when remitted)
            $table->foreignId('remitted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('remittance_transaction_ref')->nullable();
            $table->foreignId('remittance_payment_method_id')->nullable()->constrained('payment_methods')->onDelete('set null');
            
            // Audit Trail
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'due_date']);
            $table->index(['tenant_id', 'tax_year', 'tax_month']);
            $table->index(['employee_payment_id']);
            $table->index(['status', 'due_date']); // For overdue queries
        });
    }

    public function down()
    {
        Schema::dropIfExists('tax_liabilities');
    }
};