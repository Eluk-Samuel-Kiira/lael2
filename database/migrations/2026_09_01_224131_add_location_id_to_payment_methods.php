<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            // Check if table exists
            if (!Schema::hasTable('payment_methods')) {
                echo "ℹ️ payment_methods table doesn't exist. Skipping migration.\n";
                return;
            }

            // Check if column already exists
            if (!Schema::hasColumn('payment_methods', 'location_id')) {
                Schema::table('payment_methods', function (Blueprint $table) {
                    $table->json('location_id')->nullable()->after('last_transaction_type')
                        ->comment('Array of location IDs where this payment method is available');
                    
                    $table->index('location_id');
                });
                echo "✅ Added location_id column to payment_methods table\n";
            } else {
                echo "ℹ️ location_id column already exists in payment_methods table\n";
            }
        } catch (\Exception $e) {
            echo "❌ Error adding location_id: " . $e->getMessage() . "\n";
            Log::error("Migration error (add_location_id): " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            // Check if table exists
            if (!Schema::hasTable('payment_methods')) {
                echo "ℹ️ payment_methods table doesn't exist. Skipping rollback.\n";
                return;
            }

            // Check if column exists before dropping
            if (Schema::hasColumn('payment_methods', 'location_id')) {
                Schema::table('payment_methods', function (Blueprint $table) {
                    // Check if index exists before dropping
                    try {
                        $table->dropIndex(['location_id']);
                    } catch (\Exception $e) {
                        // Index might not exist, continue
                        echo "ℹ️ location_id index doesn't exist or already dropped\n";
                    }
                    
                    $table->dropColumn('location_id');
                });
                echo "✅ Dropped location_id column from payment_methods table\n";
            } else {
                echo "ℹ️ location_id column doesn't exist in payment_methods table\n";
            }
        } catch (\Exception $e) {
            echo "❌ Error dropping location_id: " . $e->getMessage() . "\n";
            Log::error("Migration rollback error (add_location_id): " . $e->getMessage());
            throw $e;
        }
    }
};