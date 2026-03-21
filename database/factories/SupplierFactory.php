<?php
namespace Database\Factories;

use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition()
    {
        $tenant = Tenant::inRandomOrder()->first() ?? Tenant::factory()->create();
        $user = User::inRandomOrder()->first() ?? User::factory()->create(['tenant_id' => $tenant->id]);
        
        $createdAt = $this->faker->dateTimeBetween('-1 year', 'now');
        $updatedAt = $this->faker->dateTimeBetween($createdAt, 'now');
        
        $supplierType = $this->faker->randomElement(['individual', 'company', 'government', 'ngo', 'foreign']);
        $isVatRegistered = $this->faker->boolean(70);
        $withholdingTaxApplicable = $this->faker->boolean(80);
        $hasExemption = $this->faker->boolean(20);

        // Create metadata array
        $metadata = [
            'source' => 'factory',
            'generated_at' => now()->toDateTimeString(),
        ];

        return [
            // Ownership
            'tenant_id' => $tenant->id,
            'created_by' => $user->id,

            // Identity
            'name' => $this->faker->company(),
            'trading_name' => $this->faker->optional(0.7)->companySuffix(),
            'supplier_type' => $supplierType,
            'is_active' => $this->faker->boolean(90),
            'supplier_code' => $this->faker->optional(0.8)->numerify('SUP-####'),

            // Contact
            'contact_person' => $this->faker->optional(0.8)->name(),
            'email' => $this->faker->optional(0.8)->companyEmail(),
            'phone' => $this->faker->optional(0.9)->phoneNumber(),
            'phone_secondary' => $this->faker->optional(0.3)->phoneNumber(),
            'website' => $this->faker->optional(0.4)->domainName(),

            // Address
            'address' => $this->faker->optional(0.8)->streetAddress(),
            'city' => $this->faker->optional(0.8)->city(),
            'state' => $this->faker->optional(0.6)->state(),
            'postal_code' => $this->faker->optional(0.7)->postcode(),
            'country_code' => $this->faker->randomElement(['UG', 'KE', 'TZ', 'RW', 'SS']),

            // Tax & Compliance
            'tax_number' => $this->faker->optional(0.9)->numerify('TIN-########'),
            'is_vat_registered' => $isVatRegistered,
            'vat_number' => $isVatRegistered ? $this->faker->numerify('VAT-########') : null,
            'withholding_tax_applicable' => $withholdingTaxApplicable,
            'withholding_tax_rate' => $withholdingTaxApplicable ? $this->faker->randomElement([3.00, 4.00, 6.00, 15.00]) : 0,
            'withholding_tax_exemption_ref' => $hasExemption ? $this->faker->bothify('EXMP-####-????') : null,
            'withholding_tax_exemption_expiry' => $hasExemption ? $this->faker->dateTimeBetween('now', '+2 years')->format('Y-m-d') : null,

            // Banking
            'bank_name' => $this->faker->optional(0.7)->randomElement([
                'Stanbic Bank', 'Centenary Bank', 'DFCU Bank', 'Equity Bank', 
                'KCB Bank', 'Absa Bank', 'Bank of Baroda', 'PostBank'
            ]),
            'bank_branch' => $this->faker->optional(0.6)->city() . ' Branch',
            'bank_account_name' => $this->faker->optional(0.7)->name(),
            'bank_account_number' => $this->faker->optional(0.7)->numerify('##########'),
            'bank_swift_code' => $this->faker->optional(0.4)->swiftBicNumber(),
            'mobile_money_number' => $this->faker->optional(0.5)->numerify('25677#######'),
            'mobile_money_provider' => $this->faker->optional(0.5)->randomElement(['MTN', 'Airtel']),

            // Payment Terms - FIXED: credit_limit should never be null
            'payment_terms_days' => $this->faker->randomElement([0, 15, 30, 45, 60, 90]),
            'payment_terms_type' => $this->faker->randomElement(['net', 'cod', 'prepaid', 'installment']),
            'preferred_payment_method' => $this->faker->randomElement([
                'bank_transfer', 'mobile_money', 'cash', 'cheque', 'other'
            ]),
            // Ensure credit_limit is always set - use numberBetween with a default of 0
            'credit_limit' => $this->faker->numberBetween(0, 100000000), // Always returns a number, never null

            // Classification
            'category' => $this->faker->optional(0.7)->randomElement([
                'Raw Materials', 'Services', 'IT', 'Office Supplies', 
                'Construction', 'Transport', 'Consulting', 'Manufacturing'
            ]),
            'risk_level' => $this->faker->randomElement(['low', 'medium', 'high']),
            'currency_code' => $this->faker->randomElement(['UGX', 'USD', 'EUR', 'GBP', 'KES']),

            // Notes & Meta
            'notes' => $this->faker->optional(0.4)->paragraph(),
            'metadata' => $this->faker->optional(0.3)->passthrough($metadata),

            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ];
    }

    /**
     * Active supplier state
     */
    public function active()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => true,
            ];
        });
    }

    /**
     * Inactive supplier state
     */
    public function inactive()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => false,
            ];
        });
    }

    /**
     * VAT registered supplier
     */
    public function vatRegistered()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_vat_registered' => true,
                'vat_number' => $this->faker->numerify('VAT-########'),
            ];
        });
    }

    /**
     * WHT exempt supplier
     */
    public function withholdingTaxExempt()
    {
        return $this->state(function (array $attributes) {
            return [
                'withholding_tax_applicable' => false,
                'withholding_tax_exemption_ref' => $this->faker->bothify('EXMP-####-????'),
                'withholding_tax_exemption_expiry' => $this->faker->dateTimeBetween('+6 months', '+2 years')->format('Y-m-d'),
            ];
        });
    }

    /**
     * High risk supplier
     */
    public function highRisk()
    {
        return $this->state(function (array $attributes) {
            return [
                'risk_level' => 'high',
                'credit_limit' => 500000, // Low credit limit for high risk
            ];
        });
    }

    /**
     * Foreign supplier
     */
    public function foreign()
    {
        return $this->state(function (array $attributes) {
            return [
                'supplier_type' => 'foreign',
                'country_code' => $this->faker->randomElement(['CN', 'IN', 'US', 'UK', 'DE']),
                'currency_code' => $this->faker->randomElement(['USD', 'EUR', 'GBP', 'CNY']),
            ];
        });
    }

    /**
     * Company supplier
     */
    public function company()
    {
        return $this->state(function (array $attributes) {
            return [
                'supplier_type' => 'company',
                'name' => $this->faker->company(),
                'trading_name' => $this->faker->companySuffix(),
                'contact_person' => $this->faker->name(),
            ];
        });
    }

    /**
     * Individual supplier
     */
    public function individual()
    {
        return $this->state(function (array $attributes) {
            return [
                'supplier_type' => 'individual',
                'name' => $this->faker->name(),
                'trading_name' => null,
                'contact_person' => null,
            ];
        });
    }

    /**
     * Mobile money preferred
     */
    public function mobileMoneyPreferred()
    {
        return $this->state(function (array $attributes) {
            return [
                'preferred_payment_method' => 'mobile_money',
                'mobile_money_number' => $this->faker->numerify('25677#######'),
                'mobile_money_provider' => $this->faker->randomElement(['MTN', 'Airtel']),
                'bank_name' => null,
                'bank_account_number' => null,
            ];
        });
    }

    /**
     * Bank transfer preferred
     */
    public function bankTransferPreferred()
    {
        return $this->state(function (array $attributes) {
            return [
                'preferred_payment_method' => 'bank_transfer',
                'bank_name' => $this->faker->randomElement([
                    'Stanbic Bank', 'Centenary Bank', 'DFCU Bank', 'Equity Bank'
                ]),
                'bank_account_name' => $this->faker->name(),
                'bank_account_number' => $this->faker->numerify('##########'),
                'mobile_money_number' => null,
            ];
        });
    }

    /**
     * Cash on delivery
     */
    public function cashOnDelivery()
    {
        return $this->state(function (array $attributes) {
            return [
                'payment_terms_type' => 'cod',
                'payment_terms_days' => 0,
                'credit_limit' => 0,
            ];
        });
    }

    /**
     * With specific category
     */
    public function category(string $category)
    {
        return $this->state(function (array $attributes) use ($category) {
            return [
                'category' => $category,
            ];
        });
    }

    /**
     * Soft deleted supplier
     */
    public function trashed()
    {
        return $this->state(function (array $attributes) {
            return [];
        })->afterCreating(function ($supplier) {
            $supplier->delete();
        });
    }
}