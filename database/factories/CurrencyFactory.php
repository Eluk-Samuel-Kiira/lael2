<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Tenant;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Currency>
 */
class CurrencyFactory extends Factory
{
    protected $model = \App\Models\Currency::class;
    
    public function definition(): array
    {
        $currencies = [
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'symbol_position' => 'before', 'decimal_places' => 2],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'symbol_position' => 'before', 'decimal_places' => 2],
            ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£', 'symbol_position' => 'before', 'decimal_places' => 2],
            ['code' => 'JPY', 'name' => 'Japanese Yen', 'symbol' => '¥', 'symbol_position' => 'before', 'decimal_places' => 0],
            ['code' => 'UGX', 'name' => 'Ugandan Shilling', 'symbol' => 'USh', 'symbol_position' => 'after', 'decimal_places' => 0],
            ['code' => 'CAD', 'name' => 'Canadian Dollar', 'symbol' => 'C$', 'symbol_position' => 'before', 'decimal_places' => 2],
        ];
        
        $currency = $this->faker->randomElement($currencies);
        
        $tenant = Tenant::inRandomOrder()->first();
        if (!$tenant) {
            $tenant = Tenant::factory()->create();
        }
        
        return [
            'tenant_id' => $tenant->id,
            'code' => $currency['code'],
            'name' => $currency['name'],
            'symbol' => $currency['symbol'],
            'symbol_position' => $currency['symbol_position'],
            'decimal_places' => $currency['decimal_places'],
            'exchange_rate' => $currency['code'] === 'USD' ? 1.00000000 : $this->faker->randomFloat(8, 0.1, 1000),
            'created_by' => User::where('role_id', 1)->inRandomOrder()->first()->id ?? 1,
            'is_active' => $this->faker->boolean(80),
            'is_base_currency' => false,
        ];
    }

    public function baseCurrency()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_base_currency' => true,
                'exchange_rate' => 1.00000000,
                'is_active' => true,
            ];
        });
    }

    public function withCode(string $code)
    {
        $currencies = [
            'USD' => ['name' => 'US Dollar', 'symbol' => '$', 'symbol_position' => 'before', 'decimal_places' => 2],
            'EUR' => ['name' => 'Euro', 'symbol' => '€', 'symbol_position' => 'before', 'decimal_places' => 2],
            'GBP' => ['name' => 'British Pound', 'symbol' => '£', 'symbol_position' => 'before', 'decimal_places' => 2],
            'JPY' => ['name' => 'Japanese Yen', 'symbol' => '¥', 'symbol_position' => 'before', 'decimal_places' => 0],
            'UGX' => ['name' => 'Ugandan Shilling', 'symbol' => 'USh', 'symbol_position' => 'after', 'decimal_places' => 0],
            'CAD' => ['name' => 'Canadian Dollar', 'symbol' => 'C$', 'symbol_position' => 'before', 'decimal_places' => 2],
        ];

        $currency = $currencies[$code] ?? ['name' => $code, 'symbol' => $code, 'symbol_position' => 'before', 'decimal_places' => 2];

        return $this->state(function (array $attributes) use ($code, $currency) {
            return [
                'code' => $code,
                'name' => $currency['name'],
                'symbol' => $currency['symbol'],
                'symbol_position' => $currency['symbol_position'],
                'decimal_places' => $currency['decimal_places'],
            ];
        });
    }

    public function forTenant($tenantId)
    {
        return $this->state(function (array $attributes) use ($tenantId) {
            return [
                'tenant_id' => $tenantId,
            ];
        });
    }
}