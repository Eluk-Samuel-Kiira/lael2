<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();

            // ── Ownership ──────────────────────────────────────────────────
            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();

            // ── Identity ───────────────────────────────────────────────────
            $table->string('name', 150);
            $table->string('trading_name', 150)->nullable()->comment('DBA / trading as');
            $table->enum('supplier_type', [
                'individual',
                'company',
                'government',
                'ngo',
                'foreign',
            ])->default('company');

            $table->boolean('is_active')->default(true);
            $table->string('supplier_code', 50)->nullable()->comment('Internal reference code');

            // ── Contact ────────────────────────────────────────────────────
            $table->string('contact_person', 100)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('phone_secondary', 50)->nullable();
            $table->string('website', 255)->nullable();

            // ── Address ────────────────────────────────────────────────────
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->char('country_code', 2)->default('UG');

            // ── Tax & Compliance ───────────────────────────────────────────
            $table->string('tax_number', 50)
                ->nullable()
                ->comment('TIN — Uganda Revenue Authority taxpayer ID');

            $table->boolean('is_vat_registered')->default(false)
                ->comment('Does supplier charge VAT on their invoices?');

            $table->string('vat_number', 50)->nullable()
                ->comment('VAT registration number if different from TIN');

            $table->boolean('withholding_tax_applicable')->default(true)
                ->comment('Should WHT be deducted when paying this supplier?');

            $table->decimal('withholding_tax_rate', 5, 2)->default(6.00)
                ->comment('Default WHT rate %. URA standard is 6%');

            $table->string('withholding_tax_exemption_ref', 100)->nullable()
                ->comment('URA exemption certificate reference if WHT exempt');

            $table->date('withholding_tax_exemption_expiry')->nullable()
                ->comment('Expiry date of WHT exemption certificate');

            // ── Banking ────────────────────────────────────────────────────
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_branch', 100)->nullable();
            $table->string('bank_account_name', 150)->nullable();
            $table->string('bank_account_number', 50)->nullable();
            $table->string('bank_swift_code', 20)->nullable();
            $table->string('mobile_money_number', 50)->nullable();
            $table->string('mobile_money_provider', 50)->nullable()
                ->comment('e.g. MTN, Airtel');

            // ── Payment Terms ──────────────────────────────────────────────
            $table->unsignedSmallInteger('payment_terms_days')->default(30)
                ->comment('Net payment days');

            $table->enum('payment_terms_type', [
                'net',        // pay in full by due date
                'cod',        // cash on delivery
                'prepaid',    // pay before delivery
                'installment',
            ])->default('net');

            $table->enum('preferred_payment_method', [
                'bank_transfer',
                'mobile_money',
                'cash',
                'cheque',
                'other',
            ])->nullable();

            $table->bigInteger('credit_limit')->default(0)
                ->comment('Max outstanding balance allowed, smallest currency unit');

            // ── Classification ─────────────────────────────────────────────
            $table->string('category', 100)->nullable()
                ->comment('e.g. Raw Materials, Services, IT');

            $table->enum('risk_level', ['low', 'medium', 'high'])->default('low');

            $table->string('currency_code', 3)->default('UGX')
                ->comment('Supplier invoices in this currency');

            // ── Notes & Meta ───────────────────────────────────────────────
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            $table->timestampsTz();
            $table->softDeletes();

            // ── Indexes ────────────────────────────────────────────────────
            $table->unique(['tenant_id', 'name'],         'idx_suppliers_tenant_name');
            $table->unique(['tenant_id', 'supplier_code'],'idx_suppliers_tenant_code');
            $table->index(['tenant_id', 'is_active'],     'idx_suppliers_tenant_active');
            $table->index(['tenant_id', 'supplier_type'], 'idx_suppliers_tenant_type');
            $table->index(['tax_number'],                 'idx_suppliers_tin');
            $table->index(['withholding_tax_applicable'], 'idx_suppliers_wht');
            $table->index(['tenant_id', 'currency_code'], 'idx_suppliers_currency');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};