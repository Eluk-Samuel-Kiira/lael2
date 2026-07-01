<?php
// database/seeders/ExpenseCategorySeeder.php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            // Skip if tenant already has expense categories
            if (ExpenseCategory::where('tenant_id', $tenant->id)->exists()) {
                $this->command->info("Tenant {$tenant->id} already has expense categories. Skipping...");
                continue;
            }

            $this->command->info("Creating expense categories for tenant {$tenant->id}...");

            // Create standard expense categories
            $standardCategories = [
                [
                    'name' => 'Office Supplies',
                    'code' => 'EXP-OFFICE',
                    'description' => 'Office stationery and supplies',
                    'requires_receipt' => true,
                    'requires_approval' => false,
                    'budget_monthly' => 500,
                ],
                [
                    'name' => 'Travel & Accommodation',
                    'code' => 'EXP-TRAVEL',
                    'description' => 'Business travel expenses',
                    'requires_receipt' => true,
                    'requires_approval' => true,
                    'budget_monthly' => 2000,
                ],
                [
                    'name' => 'Utilities',
                    'code' => 'EXP-UTIL',
                    'description' => 'Electricity, water, internet bills',
                    'requires_receipt' => true,
                    'requires_approval' => false,
                    'budget_monthly' => 1000,
                ],
                [
                    'name' => 'Marketing',
                    'code' => 'EXP-MKT',
                    'description' => 'Advertising and marketing expenses',
                    'requires_receipt' => true,
                    'requires_approval' => true,
                    'budget_monthly' => 1500,
                ],
                [
                    'name' => 'Professional Fees',
                    'code' => 'EXP-PROF',
                    'description' => 'Legal, accounting, consulting fees',
                    'requires_receipt' => true,
                    'requires_approval' => true,
                    'budget_monthly' => 3000,
                ],
            ];

            foreach ($standardCategories as $category) {
                ExpenseCategory::create(array_merge($category, [
                    'tenant_id' => $tenant->id,
                    'is_active' => true,
                ]));
            }

            // Create a few random categories using factory
            ExpenseCategory::factory()
                ->count(5)
                ->forTenant($tenant->id)
                ->create();
            
            // Create one inactive category
            ExpenseCategory::factory()
                ->forTenant($tenant->id)
                ->inactive() // This is the method that was missing
                ->create();
        }
    }
}