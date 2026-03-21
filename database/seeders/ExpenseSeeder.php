<?php
// database/seeders/ExpenseSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Expense;
use App\Models\Tenant;

class ExpenseSeeder extends Seeder
{
    public function run()
    {
        if (Expense::exists()) {
            $this->command->info('Expenses already exist. Skipping...');
            return;
        }
        
        $this->command->info('Creating expenses...');
        
        $tenants = Tenant::all();
        
        if ($tenants->isEmpty()) {
            $this->command->error('No tenants found.');
            return;
        }
        
        foreach ($tenants as $tenant) {
            // Create 15 random expenses per tenant using factory
            Expense::factory()
                ->count(15)
                ->create(['tenant_id' => $tenant->id]);
        }
        
        $this->command->info('✓ Expenses created successfully');
    }
}