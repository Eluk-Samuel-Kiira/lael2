<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Currency;
use App\Models\User;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 1 base currency
        Currency::factory()->create([
            'code' => 'USD',
            'name' => 'US Dollars',
            'symbol' => '$',
            'exchange_rate' => 1,
            'isBaseCurrency' => 1,
            'isActive' => 1,
            'created_by' => User::where('role_id', 1)->where('status', 'active')->get()->random()->id,
        ]);
        Currency::factory()->count(4)->create();
    }
}
