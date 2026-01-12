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
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10); // e.g. USD, EUR
            $table->string('name')->nullable();     // e.g. US Dollar
            $table->string('symbol')->nullable();   // e.g. $
            $table->decimal('exchange_rate', 15, 6)->default(1.000000); // to base currency
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->boolean('isActive')->default(1);
            $table->boolean('isBaseCurrency')->default(0);
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
