<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CustomerGroup;

class CustomerGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // One default group
        CustomerGroup::factory()->create([
            'name' => 'Default',
            'discount_percentage' => 0,
            'is_default' => true,
        ]);

        // Other groups
        CustomerGroup::factory()->count(5)->create();
    }
}
