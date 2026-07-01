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
        Schema::create('tenant_settings', function (Blueprint $table) {
            $table->id('setting_id');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('setting_key', 100);
            $table->text('setting_value')->nullable();
            $table->string('data_type', 20)->default('string'); // string, integer, boolean, json
            $table->string('category', 50)->default('general'); // limits, features, billing, etc.
            
            // Audit
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            
            // Composite unique key
            $table->unique(['tenant_id', 'setting_key']);
            
            // Indexes for performance
            $table->index(['tenant_id', 'category']);
            $table->index('setting_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_settings');
    }
};