<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Every webhook hit lands here first, verbatim, before any business logic
     * runs. This gives you replay-ability and an audit trail independent of
     * whether the matching/processing logic succeeded.
     */
    public function up(): void
    {
        Schema::create('invoice_payment_webhooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();

            // Nullable because the event is matched to an invoice AFTER it
            // arrives (usually via a reference/metadata field in the payload).
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();

            $table->string('provider', 50)->comment('e.g. stripe, flutterwave, pesapal, mtn_momo, airtel_money');
            $table->string('provider_event_id')->comment('Idempotency key from the provider');
            $table->string('event_type', 100)->nullable();

            $table->bigInteger('amount')->nullable()->comment('Stored in smallest currency unit');
            $table->string('currency', 3)->nullable();

            $table->json('payload')->comment('Raw webhook body, for replay/debugging');

            $table->enum('status', ['received', 'processed', 'failed', 'ignored'])->default('received');
            $table->text('processing_notes')->nullable();
            $table->timestampTz('processed_at')->nullable();

            $table->timestampsTz();

            // Prevents double-processing if the provider retries delivery.
            $table->unique(['provider', 'provider_event_id'], 'uniq_webhook_provider_event');
            $table->index(['invoice_id', 'status'], 'idx_webhooks_invoice_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_payment_webhooks');
    }
};