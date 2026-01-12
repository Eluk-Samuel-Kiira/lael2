<?php
// database/seeders/ExpenseSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ExpenseSeeder extends Seeder
{
    public function run()
    {
        // Check if expenses already exist
        if (DB::table('expenses')->exists()) {
            $this->command->info('Expenses already exist. Skipping seeder...');
            return;
        }
        
        $this->command->info('Creating expenses...');
        
        // Get all tenants
        $tenants = DB::table('tenants')->get();
        
        if ($tenants->isEmpty()) {
            $this->command->error('No tenants found. Please create tenants first.');
            return;
        }
        
        $totalExpenses = 0;
        
        foreach ($tenants as $tenant) {
            $expenseCount = $this->createExpensesForTenant($tenant->id);
            $totalExpenses += $expenseCount;
            $this->command->info("Created {$expenseCount} expenses for tenant {$tenant->id}");
        }
        
        $this->command->info("✓ Total expenses created: {$totalExpenses}");
    }
    
    private function createExpensesForTenant($tenantId)
    {
        // Get required data for this tenant
        $users = DB::table('users')
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->get();
            
        $employees = DB::table('employees')
            ->where('tenant_id', $tenantId)
            ->get();
            
        $categories = DB::table('expense_categories')
            ->where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->get();
        
        // Get payment methods for this tenant
        $paymentMethods = DB::table('payment_methods')
            ->where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->get();
        
        // If no payment methods exist, create some default ones
        if ($paymentMethods->isEmpty()) {
            $paymentMethods = $this->createDefaultPaymentMethods($tenantId, $users);
        }
        
        if ($users->isEmpty() || $employees->isEmpty() || $categories->isEmpty() || $paymentMethods->isEmpty()) {
            $this->command->warn("Missing data for tenant {$tenantId}. Skipping...");
            return 0;
        }
        
        $expenses = [];
        $now = Carbon::now();
        $expenseCounter = 1;
        
        foreach ($categories as $category) {
            // Create 2 expenses for each category
            for ($i = 1; $i <= 2; $i++) {
                $date = Carbon::now()->subDays(rand(1, 180));
                $user = $users->random();
                $employee = $employees->random();
                
                $amount = rand(5000, 50000) / 100; // $50.00 - $500.00
                $taxAmount = $amount * 0.10;
                
                // Get random payment method
                $paymentMethod = $paymentMethods->random();
                
                $paymentStatuses = ['pending', 'paid', 'reimbursed'];
                
                $expenseNumber = 'EXP-' . date('ym') . '-' . str_pad($expenseCounter, 5, '0', STR_PAD_LEFT);
                $expenseCounter++;
                
                $expenses[] = [
                    'tenant_id' => $tenantId,
                    'expense_number' => $expenseNumber,
                    'date' => $date->format('Y-m-d'),
                    'description' => $this->getRandomDescription($category->name),
                    'category_id' => $category->id,
                    'vendor_name' => $this->getRandomVendor(),
                    'amount' => $amount,
                    'tax_amount' => $taxAmount,
                    'payment_method_id' => $paymentMethod->id, // Changed from payment_method
                    'payment_status' => $paymentStatuses[array_rand($paymentStatuses)],
                    'paid_date' => rand(0, 1) ? $date->addDays(rand(1, 30))->format('Y-m-d') : null,
                    'is_recurring' => rand(0, 4) === 0, // 20% chance
                    'recurring_frequency' => rand(0, 4) === 0 ? ['weekly', 'monthly', 'quarterly', 'annually'][rand(0, 3)] : null,
                    'next_recurring_date' => rand(0, 4) === 0 ? $date->copy()->addMonths(rand(1, 12))->format('Y-m-d') : null,
                    'receipt_url' => rand(0, 1) ? 'https://example.com/receipts/receipt_' . rand(1000, 9999) . '.pdf' : null,
                    'approved_by' => rand(0, 1) ? $user->id : null,
                    'approved_at' => rand(0, 1) ? $date->format('Y-m-d H:i:s') : null,
                    'employee_id' => $employee->id,
                    'created_by' => $user->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }
        
        // Insert all expenses for this tenant
        DB::table('expenses')->insert($expenses);
        
        return count($expenses);
    }
    
    private function createDefaultPaymentMethods($tenantId, $users)
    {
        $paymentMethodData = [
            [
                'name' => 'Cash Payment',
                'type' => 'cash',
                'code' => 'CASH_' . $tenantId,
                'description' => 'Physical cash payments',
                'is_active' => true,
                'is_default' => true,
                'is_online' => false,
                'is_verified' => true,
                'verified_at' => Carbon::now(),
            ],
            [
                'name' => 'Bank Transfer',
                'type' => 'bank_account',
                'code' => 'BANK_TRANSFER_' . $tenantId,
                'description' => 'Direct bank transfer',
                'provider' => 'Bank',
                'account_name' => 'Business Account',
                'account_number' => '****1234',
                'is_active' => true,
                'is_default' => false,
                'is_online' => true,
                'is_verified' => true,
                'verified_at' => Carbon::now(),
            ],
            [
                'name' => 'Credit Card',
                'type' => 'card',
                'code' => 'CREDIT_CARD_' . $tenantId,
                'description' => 'Company credit card',
                'card_last_four' => '4321',
                'card_type' => 'visa',
                'is_active' => true,
                'is_default' => false,
                'is_online' => true,
                'is_verified' => true,
                'verified_at' => Carbon::now(),
            ],
        ];
        
        $createdMethods = collect();
        $now = Carbon::now();
        $userId = $users->first()->id ?? 1;
        
        foreach ($paymentMethodData as $method) {
            $id = DB::table('payment_methods')->insertGetId(array_merge($method, [
                'tenant_id' => $tenantId,
                'created_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
            
            $createdMethods->push((object) array_merge($method, [
                'id' => $id,
                'tenant_id' => $tenantId,
                'created_by' => $userId,
            ]));
        }
        
        return $createdMethods;
    }
    
    private function getRandomDescription($categoryName)
    {
        $descriptions = [
            'Office supplies purchase',
            'Monthly utility bill',
            'Client meeting expenses',
            'Business travel costs',
            'Equipment maintenance',
            'Software subscription renewal',
            'Marketing campaign expense',
            'Training and development',
            'Conference registration fee',
            'Team building activity',
        ];
        
        return $descriptions[array_rand($descriptions)] . ' - ' . $categoryName;
    }
    
    private function getRandomVendor()
    {
        $vendors = [
            'Amazon Business',
            'Office Depot',
            'Staples',
            'Walmart',
            'FedEx Office',
            'Verizon Wireless',
            'AT&T',
            'Comcast',
            'Dell Technologies',
            'HP Inc.',
            'Microsoft Store',
            'Adobe Systems',
            'Google Workspace',
            'Salesforce',
            'Zoom Video',
            'Uber for Business',
            'Delta Airlines',
            'Marriott Hotels',
            'Hilton Worldwide',
            'American Express',
        ];
        
        return $vendors[array_rand($vendors)];
    }
}