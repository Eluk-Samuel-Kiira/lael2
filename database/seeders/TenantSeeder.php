<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Tenant;
use App\Models\TenantConfiguration;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure "Stardena Shoppers" exists with ID 2
        if (!Tenant::where('id', 2)->exists()) {
            $stardenaShoppers = Tenant::create([
                'id' => 2,
                'name' => 'Stardena Shoppers',
                'subdomain' => 'stardenashoppers.pointofsale.com',
                'status' => 'active',
            ]);

            $stardenaShoppers->configuration()->create([
                'currency_code' => 'USD',
                'timezone' => 'America/New_York',
                'locale' => 'en',
                'fiscal_year_start' => now()->startOfYear()->format('Y-m-d'),
                'tax_calculation_method' => 'exclusive',
            ]);
        }

        // Ensure "Brenda Shoppers" exists with ID 2
        if (!Tenant::where('id', 1)->exists()) {
            $stardenaShoppers = Tenant::create([
                'id' => 1,
                'name' => 'Brenda Shoppers',
                'subdomain' => 'brenda.pointofsale.com',
                'status' => 'active',
            ]);

            $stardenaShoppers->configuration()->create([
                'currency_code' => 'USD',
                'timezone' => 'America/New_York',
                'locale' => 'en',
                'fiscal_year_start' => now()->startOfYear()->format('Y-m-d'),
                'tax_calculation_method' => 'exclusive',
            ]);
        }

        // Create other tenants using factory
        Tenant::factory()
            ->count(10)
            ->create()
            ->each(function ($tenant) {
                // Skip creating configuration if it's ID 2 (already created above)
                if ($tenant->id != 2 || $tenant->id != 1) {
                    $tenant->configuration()->create(
                        TenantConfiguration::factory()->make()->toArray()
                    );
                }
            });
    }
}