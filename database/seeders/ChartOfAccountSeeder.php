<?php
// database/seeders/ChartOfAccountSeeder.php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChartOfAccountSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            // Check if tenant already has accounts
            if (ChartOfAccount::where('tenant_id', $tenant->id)->exists()) {
                // $this->command->info("Tenant {$tenant->id} already has accounts. Skipping...");
                continue;
            }

            // $this->command->info("Creating accounts for Tenant ID: {$tenant->id}");

            // 1. Create System Accounts (ONLY these)
            $systemAccounts = [
                // Asset Accounts
                ['account_code' => '1010', 'account_name' => 'Cash on Hand', 'account_type' => 'asset_current', 'normal_balance' => 'D', 'is_system_account' => true],
                ['account_code' => '1020', 'account_name' => 'Bank Account', 'account_type' => 'asset_current', 'normal_balance' => 'D', 'is_system_account' => true],
                ['account_code' => '1030', 'account_name' => 'Accounts Receivable', 'account_type' => 'asset_current', 'normal_balance' => 'D', 'is_system_account' => true],
                ['account_code' => '1210', 'account_name' => 'Inventory', 'account_type' => 'asset_current', 'normal_balance' => 'D', 'is_system_account' => true],
                
                // Liability Accounts
                ['account_code' => '2010', 'account_name' => 'Accounts Payable', 'account_type' => 'liability_current', 'normal_balance' => 'C', 'is_system_account' => true],
                ['account_code' => '2020', 'account_name' => 'Sales Tax Payable', 'account_type' => 'liability_current', 'normal_balance' => 'C', 'is_system_account' => true],
                
                // Equity Accounts
                ['account_code' => '3010', 'account_name' => 'Owner\'s Capital', 'account_type' => 'equity', 'normal_balance' => 'C', 'is_system_account' => true],
                ['account_code' => '3020', 'account_name' => 'Retained Earnings', 'account_type' => 'equity_retained_earnings', 'normal_balance' => 'C', 'is_system_account' => true],
                
                // Revenue Accounts
                ['account_code' => '4010', 'account_name' => 'Sales Revenue', 'account_type' => 'revenue', 'normal_balance' => 'C', 'is_system_account' => true],
                ['account_code' => '4020', 'account_name' => 'Service Revenue', 'account_type' => 'revenue', 'normal_balance' => 'C', 'is_system_account' => true],
                
                // Expense Accounts
                ['account_code' => '5010', 'account_name' => 'Cost of Goods Sold', 'account_type' => 'expense_cost_of_goods', 'normal_balance' => 'D', 'is_system_account' => true],
                ['account_code' => '5020', 'account_name' => 'Rent Expense', 'account_type' => 'expense', 'normal_balance' => 'D', 'is_system_account' => true],
                ['account_code' => '5030', 'account_name' => 'Utilities Expense', 'account_type' => 'expense', 'normal_balance' => 'D', 'is_system_account' => true],
                ['account_code' => '5040', 'account_name' => 'Salaries Expense', 'account_type' => 'expense', 'normal_balance' => 'D', 'is_system_account' => true],
            ];

            // Insert system accounts safely
            foreach ($systemAccounts as $account) {
                try {
                    ChartOfAccount::firstOrCreate(
                        [
                            'tenant_id' => $tenant->id,
                            'account_code' => $account['account_code']
                        ],
                        array_merge($account, ['tenant_id' => $tenant->id])
                    );
                } catch (\Exception $e) {
                    $this->command->error("Error creating account {$account['account_code']} for tenant {$tenant->id}: " . $e->getMessage());
                }
            }

            // 2. Create ONLY 5 additional accounts (not 20)
            $this->createAdditionalAccounts($tenant->id, 5);
        }
    }

    private function createAdditionalAccounts($tenantId, $count = 5): void
    {
        $accountTypes = [
            ['type' => 'asset_current', 'balance' => 'D'],
            ['type' => 'liability_current', 'balance' => 'C'],
            ['type' => 'revenue', 'balance' => 'C'],
            ['type' => 'expense', 'balance' => 'D'],
            ['type' => 'equity', 'balance' => 'C'],
        ];

        $accountNames = [
            'asset_current' => ['Petty Cash', 'Office Equipment', 'Vehicles', 'Buildings', 'Land'],
            'liability_current' => ['Loans Payable', 'Credit Card Payable', 'Accrued Expenses', 'Customer Deposits', 'Taxes Payable'],
            'revenue' => ['Interest Income', 'Commission Revenue', 'Rental Income', 'Consulting Fees', 'Product Sales'],
            'expense' => ['Advertising Expense', 'Office Supplies', 'Travel Expense', 'Insurance Expense', 'Repairs Expense'],
            'equity' => ['Common Stock', 'Preferred Stock', 'Additional Paid-in Capital', 'Treasury Stock', 'Dividends Paid'],
        ];

        // Find the next available account code starting from 6000
        $lastCode = ChartOfAccount::where('tenant_id', $tenantId)
            ->where('account_code', '>=', '6000')
            ->orderBy('account_code', 'desc')
            ->first();

        $startCode = $lastCode ? ((int) $lastCode->account_code + 1) : 6000;

        for ($i = 0; $i < $count; $i++) {
            $typeIndex = $i % count($accountTypes);
            $type = $accountTypes[$typeIndex]['type'];
            $balance = $accountTypes[$typeIndex]['balance'];
            
            $nameIndex = $i % count($accountNames[$type]);
            $name = $accountNames[$type][$nameIndex];
            
            $code = $startCode + $i;

            try {
                ChartOfAccount::create([
                    'tenant_id' => $tenantId,
                    'account_code' => (string) $code,
                    'account_name' => $name,
                    'account_type' => $type,
                    'normal_balance' => $balance,
                    'is_active' => true,
                    'is_system_account' => false,
                    'description' => "Additional account for " . str_replace('_', ' ', $type),
                ]);
            } catch (\Exception $e) {
                $this->command->error("Error creating additional account for tenant {$tenantId}: " . $e->getMessage());
                // Try with a different code
                $code = $code + 100;
                ChartOfAccount::create([
                    'tenant_id' => $tenantId,
                    'account_code' => (string) $code,
                    'account_name' => $name,
                    'account_type' => $type,
                    'normal_balance' => $balance,
                    'is_active' => true,
                    'is_system_account' => false,
                    'description' => "Additional account for " . str_replace('_', ' ', $type),
                ]);
            }
        }
    }
}