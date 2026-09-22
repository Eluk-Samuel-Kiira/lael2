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
        Schema::create('document_sequences', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            // e.g. 'invoice', 'order', 'purchase_order', 'production_order'
            $table->string('document_type', 32);

            // One counter per year so numbers reset on Jan 1 if desired.
            $table->unsignedSmallInteger('year');

            // Last used sequence number. Starts at 0; the generator increments.
            $table->unsignedBigInteger('last_number')->default(0);

            $table->timestampsTz();

            // Exactly one counter per tenant + document type + year.
            // This is the key the atomic INSERT ... ON DUPLICATE KEY UPDATE relies on.
            $table->unique(
                ['tenant_id', 'document_type', 'year'],
                'uniq_seq_tenant_type_year'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_sequences');
    }
};