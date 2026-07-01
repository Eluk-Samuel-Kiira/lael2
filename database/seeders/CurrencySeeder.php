<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Currency;
use App\Models\Tenant;
use App\Models\User;
use Faker\Factory as Faker;

class CurrencySeeder extends Seeder
{
    private $faker;
    
    public function __construct()
    {
        $this->faker = Faker::create();
    }

    public function run(): void
    {
        // Get all tenants
        $tenants = Tenant::all();
        
        if ($tenants->isEmpty()) {
            $tenants = Tenant::factory()->count(3)->create();
        }
        
        $adminUser = User::where('role_id', 1)->where('status', 'active')->first();
        
        if (!$adminUser) {
            $adminUser = User::factory()->create([
                'role_id' => 1, 
                'status' => 'active',
                'email' => 'admin@example.com'
            ]);
        }

        // Create global USD currency (ID: 1) if it doesn't exist
        Currency::firstOrCreate(
            ['code' => 'USD', 'tenant_id' => null],
            [
                'name' => 'US Dollar (Global)',
                'symbol' => '$',
                'symbol_position' => 'before',
                'decimal_places' => 2,
                'exchange_rate' => 1.00000000,
                'created_by' => $adminUser->id,
                'is_active' => true,
                'is_base_currency' => true,
            ]
        );

        foreach ($tenants as $tenant) {
            // Check if tenant already has a base currency
            $existingBase = Currency::where('tenant_id', $tenant->id)
                ->where('is_base_currency', true)
                ->first();
            
            // Create base currency (USD) for each tenant if not exists
            if (!$existingBase) {
                Currency::factory()
                    ->baseCurrency()
                    ->withCode('USD')
                    ->forTenant($tenant->id)
                    ->create([
                        'created_by' => $adminUser->id,
                        'name' => 'US Dollar',
                        'symbol' => '$',
                    ]);
            }

            // Create additional currencies for each tenant
            $currencies = ['EUR', 'GBP', 'JPY', 'UGX', 'CAD'];
            
            foreach ($currencies as $code) {
                // Check if currency already exists for this tenant
                $exists = Currency::where('tenant_id', $tenant->id)
                    ->where('code', $code)
                    ->exists();
                
                if (!$exists) {
                    Currency::factory()
                        ->withCode($code)
                        ->forTenant($tenant->id)
                        ->create([
                            'created_by' => $adminUser->id,
                            'exchange_rate' => $this->getExchangeRateForCurrency($code),
                            'is_active' => $this->faker->boolean(80),
                        ]);
                }
            }

            // Add some random currencies (but avoid duplicates)
            $existingCodes = Currency::where('tenant_id', $tenant->id)
                ->pluck('code')
                ->toArray();
                
            $randomCurrencies = array_diff(['AUD', 'CHF', 'CNY', 'INR', 'MXN', 'BRL'], $existingCodes);
            $randomCount = min(3, count($randomCurrencies));
            
            if ($randomCount > 0) {
                $selectedRandoms = array_slice($randomCurrencies, 0, $randomCount);
                
                foreach ($selectedRandoms as $code) {
                    Currency::factory()
                        ->withCode($code)
                        ->forTenant($tenant->id)
                        ->create([
                            'created_by' => $adminUser->id,
                            'exchange_rate' => $this->getExchangeRateForCurrency($code),
                            'is_active' => $this->faker->boolean(70),
                        ]);
                }
            }
        }
    }

    private function getExchangeRateForCurrency(string $code): float
    {
        $rates = [
            'USD' => 1.00000000,
            'EUR' => 0.92000000,
            'GBP' => 0.79000000,
            'JPY' => 150.50000000,
            'UGX' => 3700.00000000,
            'CAD' => 1.35000000,
            'AUD' => 1.52000000,
            'CHF' => 0.88000000,
            'CNY' => 7.19000000,
            'INR' => 83.00000000,
            'MXN' => 17.50000000,
            'BRL' => 5.20000000,
        ];

        return $rates[$code] ?? $this->faker->randomFloat(8, 0.5, 1000);
    }
}