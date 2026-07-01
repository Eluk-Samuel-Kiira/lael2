<?php
// Migration for currencies table
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10); // e.g. USD, EUR, UGX
            $table->string('name')->nullable();
            $table->string('symbol')->nullable();
            $table->string('symbol_position')->default('before'); // before/after
            $table->integer('decimal_places')->default(2); // For currencies like IQD that use 3 decimals
            $table->decimal('exchange_rate', 20, 8)->default(1.00000000); // Higher precision
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_active')->default(1);
            $table->boolean('is_base_currency')->default(0);
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->timestamps();
            
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};