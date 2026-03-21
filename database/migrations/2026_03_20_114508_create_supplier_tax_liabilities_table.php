<?php
// database/migrations/[timestamp]_create_supplier_tax_liabilities_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('supplier_tax_liabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('supplier_id')->constrained()->onDelete('cascade');
            $table->foreignId('purchase_order_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('purchase_receipt_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('expense_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('tax_id')->nullable()->constrained()->onDelete('set null');
            
            // Tax Details
            $table->bigInteger('taxable_amount')->comment('Stored in smallest currency unit');
            $table->bigInteger('tax_amount')->comment('Stored in smallest currency unit');
            $table->decimal('tax_rate', 10, 2)->comment('Tax rate percentage');
            $table->string('tax_name', 100);
            $table->string('tax_code', 50)->nullable();
            $table->enum('tax_type', ['percentage', 'fixed'])->default('percentage');
            
            // Transaction Details
            $table->string('reference_number', 100)->nullable();
            $table->date('transaction_date');
            $table->date('due_date')->nullable()->comment('When tax is due to tax authority');
            
            // Liability Status
            $table->enum('status', [
                'pending',      // Tax withheld but not yet remitted
                'remitted',     // Paid to tax authority
                'overdue',      // Past due date
                'cancelled',    // Voided/adjusted
                'exempt'        // Tax exempt
            ])->default('pending');
            
            // Tax Period
            $table->integer('tax_year');
            $table->integer('tax_month')->nullable();
            $table->integer('tax_quarter')->nullable();
            
            // Remittance Details
            $table->date('remitted_at')->nullable();
            $table->string('remittance_reference', 100)->nullable();
            $table->string('remittance_transaction_ref', 100)->nullable();
            $table->foreignId('remitted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('remittance_payment_method_id')->nullable()->constrained('payment_methods')->onDelete('set null');
            
            // Withholding Tax Specific (WHT)
            $table->boolean('is_withholding_tax')->default(false);
            $table->string('wht_certificate_number', 100)->nullable();
            $table->date('wht_certificate_date')->nullable();
            
            // Notes & Metadata
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'due_date']);
            $table->index(['tenant_id', 'tax_year', 'tax_month']);
            $table->index(['supplier_id', 'status']);
            $table->index(['purchase_order_id']);
            $table->index(['expense_id']);
            $table->index(['reference_number']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('supplier_tax_liabilities');
    }
};