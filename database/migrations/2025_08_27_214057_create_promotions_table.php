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
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->enum('discount_type', ['percentage', 'fixed_amount', 'buy_x_get_y']);
            
            // 👇 Changed to BIGINT - stores percentage * 100 OR fixed amount in smallest unit
            $table->bigInteger('discount_value')->comment('If percentage: 15.00% = 1500, if fixed: stored in smallest currency unit');
            
            $table->timestampTz('start_date');
            $table->timestampTz('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('max_uses')->nullable();
            $table->integer('max_uses_per_customer')->nullable();
            
            // 👇 Changed to BIGINT for storing in smallest currency unit
            $table->bigInteger('min_order_amount')->nullable()->comment('Stored in smallest currency unit');
            
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestampsTz();

            $table->unique(['tenant_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};