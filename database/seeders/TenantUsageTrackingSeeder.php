<?php
// database/seeders/TenantUsageTrackingSeeder.php

namespace Database\Seeders;

use App\Models\TenantUsageTracking;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TenantUsageTrackingSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('Creating tenant usage tracking records...');

        // Clear existing data if you want fresh seeding
        // TenantUsageTracking::truncate();
        
        // Or check if any data exists and ask for confirmation
        if (TenantUsageTracking::count() > 0) {
            if (!$this->command->confirm('Tenant usage tracking records already exist. Continue and update existing records?', false)) {
                $this->command->info('Seeding cancelled.');
                return;
            }
        }

        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->command->warn('No tenants found. Creating a demo tenant first...');
            $tenant = \App\Models\Tenant::factory()->create([
                'name' => 'Demo Tenant',
            ]);
            $tenants = collect([$tenant]);
        }

        foreach ($tenants as $tenant) {
            $this->command->info("Creating usage history for tenant: {$tenant->name}");
            
            // Create historical data for the past 30 days
            $this->createHistoricalData($tenant);
            
            // Create today's record (or update if exists)
            $this->createOrUpdateTodayRecord($tenant);
        }

        $this->command->info('Tenant usage tracking records created successfully!');
    }

    private function createHistoricalData($tenant)
    {
        // Generate data for the past 30 days (excluding today)
        $daysToGenerate = 30;
        
        for ($i = 1; $i <= $daysToGenerate; $i++) {
            $date = Carbon::today()->subDays($i);
            
            // Skip if record already exists for this date
            if (TenantUsageTracking::where('tenant_id', $tenant->id)
                ->whereDate('tracking_date', $date)
                ->exists()) {
                continue;
            }
            
            // Create usage record for this historical date
            TenantUsageTracking::factory()
                ->forTenant($tenant->id)
                ->forDate($date)
                ->create();
        }
        
        $this->command->info("  Created historical data for the past {$daysToGenerate} days.");
    }

    private function createOrUpdateTodayRecord($tenant)
    {
        // Check if today's record already exists
        $existingRecord = TenantUsageTracking::where('tenant_id', $tenant->id)
            ->whereDate('tracking_date', Carbon::today())
            ->first();

        if ($existingRecord) {
            // Update existing record with fresh data
            $existingRecord->update([
                'current_shops' => rand(1, $tenant->shops_count ?? 10),
                'current_locations' => rand(1, $tenant->locations_count ?? 20),
                'current_users' => rand(1, $tenant->users_count ?? 50),
                'current_products' => rand(100, $tenant->products_count ?? 5000),
                'current_customers' => rand(50, $tenant->customers_count ?? 2000),
                'monthly_sales_count' => rand(100, 10000),
                'monthly_api_calls' => rand(1000, 100000),
                'monthly_storage_mb' => rand(100, 5000) / 100, // Random MB with 2 decimal places
                'updated_at' => now(),
            ]);
            
            $this->command->info("  Updated today's usage record.");
        } else {
            // Create new record for today
            TenantUsageTracking::factory()
                ->forTenant($tenant->id)
                ->forToday()
                ->create();
            
            $this->command->info("  Created today's usage record.");
        }
    }

    private function createRandomRecentData($tenant)
    {
        // Create 5-10 random recent records
        $count = rand(5, 10);
        
        for ($i = 0; $i < $count; $i++) {
            $date = Carbon::today()->subDays(rand(1, 7));
            
            // Use updateOrCreate to avoid duplicates
            TenantUsageTracking::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'tracking_date' => $date,
                ],
                TenantUsageTracking::factory()->raw([
                    'tenant_id' => $tenant->id,
                    'tracking_date' => $date,
                ])
            );
        }
    }
}