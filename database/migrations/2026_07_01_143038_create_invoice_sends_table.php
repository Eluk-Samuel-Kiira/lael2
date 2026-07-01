<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * One row per delivery attempt. An invoice can be resent, resent on a
     * different channel, or auto-reminded — each attempt gets its own row
     * so you can show a full delivery/read history on the invoice.
     */
    public function up(): void
    {
        Schema::create('invoice_sends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();

            $table->enum('channel', ['email', 'sms', 'whatsapp', 'portal_link', 'print']);
            $table->string('recipient')->nullable()->comment('Email address or phone number used for this attempt');

            $table->enum('status', ['pending', 'sent', 'delivered', 'failed', 'bounced'])->default('pending');
            $table->string('provider', 50)->nullable()->comment('e.g. ses, twilio, whatsapp_business_api');
            $table->string('provider_message_id')->nullable()->comment('Used to match delivery webhooks back to this row');
            $table->text('error_message')->nullable();

            // Null when triggered automatically (e.g. a scheduled overdue reminder).
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestampTz('sent_at')->nullable();
            $table->timestampTz('delivered_at')->nullable();
            $table->timestampsTz();

            $table->index(['invoice_id', 'channel'], 'idx_invoice_sends_invoice_channel');
            $table->index('provider_message_id', 'idx_invoice_sends_provider_msg');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_sends');
    }
};