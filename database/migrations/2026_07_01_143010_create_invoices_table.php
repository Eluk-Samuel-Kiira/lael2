<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();

            // Every invoice traces back to exactly one order (the order carries
            // the line items, discounts, tax, totals — invoices don't duplicate that).
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();

            // Invoice number is a separate sequence from order_number — this is
            // the legally/tax-visible identifier and must stay gapless per tenant.
            $table->string('invoice_number', 50);

            // Opaque token for a client-facing "view/pay this invoice" link that
            // doesn't require the client to have a login.
            $table->string('public_token', 64)->unique();

            // Billing details are snapshotted at issue time. If the customer
            // record changes later (address, name), historical invoices must
            // not silently change with it.
            $table->string('billing_name');
            $table->string('billing_email')->nullable();
            $table->string('billing_phone')->nullable();
            $table->text('billing_address')->nullable();
            $table->string('tax_id', 50)->nullable()->comment('Customer TIN, if applicable');

            $table->date('issue_date');
            $table->date('due_date')->nullable();

            $table->enum('status', [
                'draft',           // created, not yet sent
                'sent',            // delivered to client
                'viewed',          // client opened it (if tracked)
                'partially_paid',
                'paid',
                'overdue',         // past due_date, balance_due > 0
                'void',            // cancelled before payment, number retired
                'cancelled',
            ])->default('draft');

            $table->string('currency', 3)->default('UGX');

            // Snapshotted from the order at issue time, in smallest currency unit.
            $table->bigInteger('subtotal');
            $table->bigInteger('discount_total')->default(0);
            $table->bigInteger('tax_total')->default(0);
            $table->bigInteger('total');
            $table->bigInteger('amount_paid')->default(0);
            $table->bigInteger('balance_due');

            $table->string('pdf_path')->nullable()->comment('Storage path of the generated PDF');
            $table->text('terms')->nullable()->comment('Payment terms shown on the document');
            $table->text('notes')->nullable();

            $table->timestampTz('sent_at')->nullable();
            $table->timestampTz('viewed_at')->nullable();
            $table->timestampTz('paid_at')->nullable();
            $table->timestampTz('voided_at')->nullable();

            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestampsTz();
            $table->softDeletes();

            $table->unique(['tenant_id', 'invoice_number'], 'uniq_invoices_tenant_number');
            $table->index(['tenant_id', 'status'], 'idx_invoices_tenant_status');
            $table->index(['due_date', 'status'], 'idx_invoices_due_status');
            $table->index('order_id', 'idx_invoices_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};