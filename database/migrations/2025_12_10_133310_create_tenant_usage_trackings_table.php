<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_tenant_usage_tracking_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tenant_usage_tracking', function (Blueprint $table) {
            $table->id('usage_id');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->date('tracking_date');
            
            // Current counts (as of tracking date)
            $table->unsignedInteger('current_shops')->default(0);
            $table->unsignedInteger('current_locations')->default(0);
            $table->unsignedInteger('current_departments')->default(0);
            $table->unsignedInteger('current_users')->default(0);
            $table->unsignedInteger('current_products')->default(0);
            $table->unsignedInteger('current_customers')->default(0);
            $table->unsignedInteger('current_employees')->default(0);
            $table->unsignedInteger('current_api_keys')->default(0);
            $table->unsignedInteger('current_webhooks')->default(0);
            $table->unsignedInteger('current_integrations')->default(0);
            
            // Monthly cumulative counts
            $table->unsignedInteger('monthly_sales_count')->default(0);
            $table->unsignedInteger('monthly_api_calls')->default(0);
            $table->decimal('monthly_storage_mb', 12, 2)->default(0);
            
            // Performance metrics
            $table->decimal('average_response_time_ms', 8, 2)->nullable();
            $table->decimal('error_rate_percent', 5, 2)->nullable();
            $table->unsignedInteger('active_sessions')->default(0);
            $table->unsignedInteger('concurrent_users')->default(0);
            
            // Billing related
            $table->decimal('estimated_cost', 12, 2)->nullable();
            $table->decimal('resource_utilization_percent', 5, 2)->nullable();
            $table->boolean('exceeds_plan_limits')->default(false);
            
            $table->timestamps();
            
            // Unique constraint: One tracking record per tenant per day
            $table->unique(['tenant_id', 'tracking_date'], 'tenant_usage_tracking_unique');
            
            // Indexes for common queries
            $table->index(['tracking_date', 'tenant_id']);
            $table->index(['exceeds_plan_limits', 'tracking_date']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('tenant_usage_tracking');
    }
};