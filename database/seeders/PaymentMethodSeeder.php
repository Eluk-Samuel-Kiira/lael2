<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PaymentMethod;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Currency;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all tenants
        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->command->info('No tenants found. Skipping payment method seeding.');
            return;
        }

        // Get default currency (USD)
        $defaultCurrency = Currency::where('code', 'USD')->first();
        if (!$defaultCurrency) {
            $defaultCurrency = Currency::first();
        }
        
        if (!$defaultCurrency) {
            $this->command->error('No currencies found. Please run CurrencySeeder first.');
            return;
        }

        // Define payment methods template with ONLY EXISTING fields
        $paymentMethodTemplates = [
            // Bank Accounts
            [
                'name' => 'Primary Business Account',
                'type' => 'bank_account',
                'code' => 'BANK_PRIMARY_001',
                'description' => 'Main business checking account',
                'provider' => 'Chase Bank',
                'account_name' => 'Business Account',
                'account_number' => '1234567890',
                'routing_number' => '021000021',
                'is_active' => true,
                'is_default' => true,
                'is_online' => true,
                'is_verified' => true,
                'currency_id' => $defaultCurrency->id,
                'supported_currencies' => ['USD'],
            ],
            [
                'name' => 'Payroll Account',
                'type' => 'bank_account',
                'code' => 'BANK_PAYROLL_001',
                'description' => 'Account dedicated for payroll processing',
                'provider' => 'Bank of America',
                'account_name' => 'Payroll Account',
                'account_number' => '0987654321',
                'routing_number' => '026009593',
                'is_active' => true,
                'is_default' => false,
                'is_online' => true,
                'is_verified' => true,
                'currency_id' => $defaultCurrency->id,
                'supported_currencies' => ['USD'],
            ],

            // Digital Wallets
            [
                'name' => 'PayPal Business',
                'type' => 'digital_wallet',
                'code' => 'PAYPAL_BUSINESS',
                'description' => 'Business PayPal account for online payments',
                'provider' => 'PayPal',
                'wallet_email' => 'payments@company.com',
                'transaction_fee_percentage' => 2.9,
                'transaction_fee_fixed' => 0.30,
                'is_active' => true,
                'is_default' => false,
                'is_online' => true,
                'is_verified' => true,
                'currency_id' => $defaultCurrency->id,
                'supported_currencies' => ['USD', 'EUR', 'GBP'],
            ],
            [
                'name' => 'Stripe Account',
                'type' => 'digital_wallet',
                'code' => 'STRIPE_ACCOUNT',
                'description' => 'Payment processing via Stripe',
                'provider' => 'Stripe',
                'wallet_email' => 'stripe@company.com',
                'transaction_fee_percentage' => 2.9,
                'transaction_fee_fixed' => 0.30,
                'is_active' => true,
                'is_default' => false,
                'is_online' => true,
                'is_verified' => true,
                'currency_id' => $defaultCurrency->id,
                'supported_currencies' => ['USD', 'CAD', 'EUR', 'GBP', 'AUD'],
            ],

            // Cards
            [
                'name' => 'Corporate Credit Card',
                'type' => 'card',
                'code' => 'CORP_CARD_001',
                'description' => 'Company Visa credit card',
                'card_last_four' => '4321',
                'card_type' => 'visa',
                'card_expiry_date' => now()->addYears(3)->format('Y-m-d'),
                'is_active' => true,
                'is_default' => false,
                'is_online' => true,
                'is_verified' => true,
                'currency_id' => $defaultCurrency->id,
                'supported_currencies' => ['USD'],
            ],

            // Cash
            [
                'name' => 'Cash Register - Main',
                'type' => 'cash',
                'code' => 'CASH_REGISTER_MAIN',
                'description' => 'Main cash register at headquarters',
                'is_active' => true,
                'is_default' => false,
                'is_online' => false,
                'cash_location' => 'Headquarters - Reception',
                'currency_id' => $defaultCurrency->id,
                'supported_currencies' => ['USD'],
            ],

            // Check
            [
                'name' => 'Business Checks',
                'type' => 'check',
                'code' => 'BUSINESS_CHECKS',
                'description' => 'Manual check payments from primary account',
                'is_active' => true,
                'is_default' => false,
                'is_online' => false,
                'currency_id' => $defaultCurrency->id,
                'supported_currencies' => ['USD'],
            ],

            // Mobile Money
            [
                'name' => 'M-Pesa Business',
                'type' => 'mobile_money',
                'code' => 'MPESA_BUSINESS',
                'description' => 'M-Pesa for mobile payments (Kenya)',
                'provider' => 'M-Pesa',
                'account_name' => 'Mobile Account',
                'account_number' => '0712345678',
                'transaction_fee_percentage' => 1.5,
                'transaction_fee_fixed' => 0.00,
                'is_active' => true,
                'is_default' => false,
                'is_online' => true,
                'is_verified' => true,
                'currency_id' => Currency::where('code', 'KES')->first()?->id ?? $defaultCurrency->id,
                'supported_currencies' => ['KES'],
            ],
            [
                'name' => 'MTN MoMo',
                'type' => 'mobile_money',
                'code' => 'MTN_MOMO',
                'description' => 'MTN Mobile Money for payments (Ghana/Uganda)',
                'provider' => 'MTN',
                'account_name' => 'Mobile Account',
                'account_number' => '0776263482',
                'transaction_fee_percentage' => 1.5,
                'transaction_fee_fixed' => 0.00,
                'is_active' => true,
                'is_default' => false,
                'is_online' => true,
                'is_verified' => true,
                'currency_id' => Currency::whereIn('code', ['GHS', 'UGX'])->first()?->id ?? $defaultCurrency->id,
                'supported_currencies' => ['GHS', 'UGX', 'USD'],
            ],
        ];

        $totalPaymentMethodsCreated = 0;
        $tenantsProcessed = 0;

        // Get the super admin user (first user in the system) as fallback creator
        $fallbackUser = User::first();
        
        // If no users exist at all, we need to handle this differently
        if (!$fallbackUser) {
            $this->command->error('No users exist in the system. Cannot create payment methods.');
            return;
        }

        foreach ($tenants as $tenant) {
            // Get any user from this tenant (preferably admin)
            $user = User::where('tenant_id', $tenant->id)
                ->whereHas('roles', function($q) {
                    $q->where('name', 'admin');
                })
                ->first();

            if (!$user) {
                $user = User::where('tenant_id', $tenant->id)->first();
            }

            // If no user exists for this tenant, we still create payment methods using fallback user
            $creatorId = $user ? $user->id : $fallbackUser->id;
            
            foreach ($paymentMethodTemplates as $template) {
                // Create unique code for each tenant
                $uniqueCode = $template['code'] . '_T' . $tenant->id;

                // Determine if we should set this as default (first bank account)
                $isDefault = $template['code'] === 'BANK_PRIMARY_001' && $template['type'] === 'bank_account';

                $paymentMethodData = array_merge($template, [
                    'code' => $uniqueCode,
                    'tenant_id' => $tenant->id,
                    'created_by' => $creatorId,
                    'verified_at' => now(),
                    'is_default' => $isDefault,
                    'min_transaction_amount' => 1.00,
                    'max_transaction_amount' => 100000.00,
                    'daily_limit' => 50000.00,
                    'monthly_limit' => 1000000.00,
                ]);

                // Set cash handler for cash payment methods
                if ($template['type'] === 'cash' && isset($template['cash_location'])) {
                    $cashHandler = User::where('tenant_id', $tenant->id)
                        ->whereHas('roles', function($q) {
                            $q->whereIn('name', ['cashier', 'finance', 'manager']);
                        })
                        ->first();
                    
                    if ($cashHandler) {
                        $paymentMethodData['cash_handler_id'] = $cashHandler->id;
                    }
                }

                PaymentMethod::updateOrCreate(
                    [
                        'code' => $uniqueCode,
                        'tenant_id' => $tenant->id
                    ],
                    $paymentMethodData
                );

                $totalPaymentMethodsCreated++;
            }

            $tenantsProcessed++;
            $this->command->info("✓ Created " . count($paymentMethodTemplates) . " payment methods for tenant: {$tenant->name} (ID: {$tenant->id})");
        }

        $this->command->info("\n📊 SEEDER SUMMARY:");
        $this->command->info("✅ Total tenants processed: {$tenantsProcessed}");
        $this->command->info("✅ Total payment methods created: {$totalPaymentMethodsCreated}");
        $this->command->info("✅ Average per tenant: " . ($tenantsProcessed > 0 ? round($totalPaymentMethodsCreated / $tenantsProcessed) : 0));
    }
}