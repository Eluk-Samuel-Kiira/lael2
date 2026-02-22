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
        Schema::create('tenants', function (Blueprint $table) {
            $table->id(); // SERIAL PRIMARY KEY
            $table->string('name', 100);
            $table->string('subdomain', 225)->unique(); // e.g. 'acme.pointofsale.com'
            $table->enum('status', ['active', 'suspended', 'trial'])->default('trial');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
