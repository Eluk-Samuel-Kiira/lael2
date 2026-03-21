<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_taxes', function (Blueprint $table) {
            $table->id();

            // ── Core ───────────────────────────────────────────────────────
            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();

            // ── Tax details ────────────────────────────────────────────────
            $table->string('tax_name', 100);
            $table->decimal('tax_rate', 5, 2)->comment('Percentage, not a currency amount');
            $table->bigInteger('tax_amount')->comment('Stored in smallest currency unit');
            $table->boolean('is_compound')->default(false);

            // ── Remittance status ──────────────────────────────────────────
            $table->enum('status', ['pending', 'remitted', 'overdue', 'cancelled'])
                ->default('pending');

            $table->date('due_date')->nullable()->comment('URA deadline — 15th of following month');

            // ── Period ─────────────────────────────────────────────────────
            $table->unsignedSmallInteger('tax_year')->nullable();
            $table->unsignedTinyInteger('tax_month')->nullable()->comment('1–12');
            $table->unsignedTinyInteger('tax_quarter')->nullable()->comment('1–4');

            // ── Remittance tracking ────────────────────────────────────────
            $table->timestamp('remitted_at')->nullable();
            $table->string('remittance_reference')->nullable();
            $table->string('remittance_transaction_ref')->nullable();

            $table->foreignId('remittance_payment_method_id')
                ->nullable()
                ->constrained('payment_methods')
                ->nullOnDelete();

            $table->foreignId('remitted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // ── Extra ──────────────────────────────────────────────────────
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            $table->timestampsTz();
            $table->softDeletes();

            // ── Indexes ────────────────────────────────────────────────────
            $table->index(['tenant_id', 'status'],                'idx_order_taxes_tenant_status');
            $table->index(['tenant_id', 'tax_year', 'tax_month'], 'idx_order_taxes_period');
            $table->index(['due_date', 'status'],                 'idx_order_taxes_due_status');
            $table->index(['order_id', 'tax_name'],               'idx_order_taxes_order_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_taxes');
    }
};