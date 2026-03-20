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
        Schema::create('taxes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            
            // 👇 This field stores:
            // - For percentage: decimal rate (e.g., 18.0000 for 18%)
            // - For fixed: integer in smallest currency unit
            $table->decimal('rate', 12, 4);
            
            $table->enum('type', ['percentage', 'fixed'])->default('percentage');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_withholding_tax')->default(false)->comment('If true, tax is deducted from supplier payment; if false, tax is added to supplier payment');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taxes');
    }
};