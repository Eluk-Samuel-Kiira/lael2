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
        Schema::create('tenant_configurations', function (Blueprint $table) {
            $table->id('config_id'); // SERIAL PRIMARY KEY
            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->onDelete('cascade');
            
            $table->char('currency_code', 3)->default('USD');
            $table->string('timezone', 50)->default('UTC');
            $table->string('locale', 10)->default('en-US');
            $table->date('fiscal_year_start')->default('2023-01-01');
            
            // ✅ Native ENUM support for MySQL
            $table->enum('tax_calculation_method', ['inclusive', 'exclusive'])->default('inclusive');

            $table->unique('tenant_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_configurations');
    }
};
