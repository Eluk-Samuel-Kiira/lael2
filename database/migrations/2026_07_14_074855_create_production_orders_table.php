<?php
// database/migrations/2026_07_14_000001_create_production_orders_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('location_id')->constrained()->onDelete('restrict');
            $table->foreignId('recipe_id')->nullable()->constrained()->onDelete('set null');
            
            // ✅ This IS the batch number
            $table->string('production_number', 50)->unique();
            
            // Status
            $table->enum('status', [
                'draft',
                'planned',
                'approved',
                'in_progress',
                'quality_check',
                'completed',
                'cancelled'
            ])->default('draft');
            
            // Scheduling
            $table->timestamp('scheduled_date')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            // Totals
            $table->decimal('total_input_quantity', 12, 4)->default(0);
            $table->decimal('total_output_quantity', 12, 4)->default(0);
            $table->bigInteger('estimated_cost')->default(0);
            $table->bigInteger('actual_cost')->default(0);
            
            // User tracking
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->foreignId('started_by')->nullable()->constrained('users');
            $table->foreignId('completed_by')->nullable()->constrained('users');
            $table->foreignId('cancelled_by')->nullable()->constrained('users');
            
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            
            $table->text('notes')->nullable();
            $table->text('quality_notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('production_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_orders');
    }
};