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
        Schema::create('change_log', function (Blueprint $table) {
            $table->id();
            $table->string('table_name', 100);
            $table->unsignedBigInteger('row_id');
            $table->enum('operation', ['INSERT', 'UPDATE', 'DELETE']);
            $table->json('payload');
            $table->json('old_payload')->nullable();
            $table->unsignedBigInteger('tenant_id');          // CRITICAL — isolates tenants
            $table->unsignedBigInteger('location_id')->nullable(); // which shop branch
            $table->timestamp('logged_at')->useCurrent();
            $table->timestamp('synced_at')->nullable();
            $table->string('sync_error', 500)->nullable();
            $table->unsignedTinyInteger('retry_count')->default(0);

            $table->index(['tenant_id', 'synced_at']);        // query pattern: unsynced per tenant
            $table->index(['tenant_id', 'logged_at']);
        });

        Schema::create('sync_status', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->enum('status', ['online', 'offline', 'syncing', 'error'])->default('offline');
            $table->timestamp('last_synced_at')->nullable();
            $table->unsignedInteger('pending_count')->default(0);
            $table->unsignedInteger('last_pushed')->default(0);  // rows pushed last run
            $table->string('last_error', 500)->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'location_id']);
        });

        Schema::create('sync_processed', function (Blueprint $table) {
            $table->id();
            $table->string('sync_key', 64)->unique(); // sha256 hash
            $table->unsignedBigInteger('tenant_id');
            $table->timestamp('processed_at');
            $table->index(['tenant_id', 'processed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_processed');
        Schema::dropIfExists('change_log');
        Schema::dropIfExists('sync_status');
    }
};