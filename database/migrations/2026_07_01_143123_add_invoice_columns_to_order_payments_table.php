<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * order_payments already exists and handles POS payments fine. We're
     * extending it — not duplicating it — so a payment against an invoice
     * shows up in the exact same table your financial reports already read.
     */
    public function up(): void
    {
        Schema::table('order_payments', function (Blueprint $table) {
            $table->foreignId('invoice_id')
                ->nullable()
                ->after('order_id')
                ->constrained('invoices')
                ->nullOnDelete();

            // How this payment row came to exist.
            $table->enum('recorded_via', ['pos', 'manual', 'webhook'])
                ->default('pos')
                ->after('status');

            // Set when recorded_via = webhook, points back to the raw event.
            $table->foreignId('webhook_id')
                ->nullable()
                ->constrained('invoice_payment_webhooks')
                ->nullOnDelete();

            // Set when recorded_via = manual — who confirmed "yes, this was paid".
            $table->foreignId('confirmed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->index(['invoice_id', 'status'], 'idx_order_payments_invoice_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_payments', function (Blueprint $table) {
            $table->dropIndex('idx_order_payments_invoice_status');
            $table->dropConstrainedForeignId('confirmed_by');
            $table->dropConstrainedForeignId('webhook_id');
            $table->dropColumn('recorded_via');
            $table->dropConstrainedForeignId('invoice_id');
        });
    }
};