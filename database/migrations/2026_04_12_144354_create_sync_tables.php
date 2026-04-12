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
        // Change log table - heartbeat of the sync system
        Schema::create('change_log', function (Blueprint $table) {
            $table->id();
            $table->string('table_name', 100);
            $table->unsignedBigInteger('row_id');
            $table->enum('operation', ['INSERT', 'UPDATE', 'DELETE']);
            $table->json('payload');           // full row snapshot
            $table->json('old_payload')->nullable(); // previous values for UPDATE
            $table->unsignedBigInteger('tenant_id');
            $table->timestamp('logged_at')->useCurrent();
            $table->timestamp('synced_at')->nullable(); // null = pending
            $table->string('sync_error')->nullable();
            $table->unsignedTinyInteger('retry_count')->default(0);
            
            // Indexes for performance
            $table->index(['synced_at', 'tenant_id']);
            $table->index(['tenant_id', 'table_name', 'synced_at']);
            $table->index(['logged_at']);
            $table->index(['operation']);
        });

        // Sync status table - one row per tenant for dashboard display
        Schema::create('sync_status', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->unique();
            $table->timestamp('last_synced_at')->nullable();
            $table->unsignedInteger('pending_count')->default(0);
            $table->enum('status', ['online', 'offline', 'syncing', 'error'])->default('offline');
            $table->string('last_error')->nullable();
            $table->timestamps();
            
            // Additional indexes
            $table->index(['status']);
            $table->index(['tenant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('change_log');
        Schema::dropIfExists('sync_status');
    }
};