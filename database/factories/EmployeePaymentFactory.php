<?php
// database/factories/EmployeePaymentFactory.php

namespace Database\Factories;

use App\Models\EmployeePayment;
use App\Models\Employee;
use App\Models\PaymentMethod;
use App\Models\Tax;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeePaymentFactory extends Factory
{
    protected $model = EmployeePayment::class;

    /**
     * Tax rates by code for calculation
     */
    protected array $taxRates = [
        'PAYE' => 0.15,           // 15% Pay As You Earn
        'NSSF_EMPLOYEE' => 0.05,  // 5% NSSF employee contribution
        'LST' => 0.01,            // 1% Local Service Tax
    ];

    public function definition(): array
    {
        $paymentDate = $this->faker->dateTimeBetween('-1 year', 'now');
        
        // Get payment types
        $paymentTypes = ['salary', 'allowance', 'bonus', 'overtime', 'advance', 'other'];
        $paymentType = $this->faker->randomElement($paymentTypes);
        
        // Get employee first to determine tenant_id
        $employee = Employee::inRandomOrder()->first() ?? Employee::factory()->create();
        $tenantId = $employee->tenant_id;
        
        // Generate base amount based on payment type
        $amount = $this->generateAmountByType($paymentType);
        $grossAmount = $amount;
        
        // Calculate taxes for taxable payment types
        $taxableTypes = ['salary', 'bonus'];
        $totalTaxAmount = 0;
        $appliedTaxes = null;
        
        if (in_array($paymentType, $taxableTypes)) {
            $taxResult = $this->calculateTaxes($amount, $tenantId);
            $totalTaxAmount = $taxResult['total'];
            $appliedTaxes = json_encode($taxResult['breakdown']);
        }
        
        $netAmount = $grossAmount - $totalTaxAmount;
        
        // Get a random payment method for the tenant
        $paymentMethod = PaymentMethod::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->inRandomOrder()
            ->first();
        
        // If no payment methods exist for this tenant, create one
        if (!$paymentMethod) {
            $paymentMethod = PaymentMethod::factory()->create([
                'tenant_id' => $tenantId,
                'created_by' => $employee->id,
            ]);
        }
        
        return [
            'employee_id' => $employee->id,
            'tenant_id' => $tenantId,
            'payment_date' => $paymentDate,
            'payment_type' => $paymentType,
            'description' => $this->generateDescription($paymentType, $paymentDate),
            'amount' => $amount,
            'gross_amount' => $grossAmount,
            'net_amount' => $netAmount,
            'total_tax_amount' => $totalTaxAmount,
            'applied_taxes' => $appliedTaxes,
            'is_tax_computed' => in_array($paymentType, $taxableTypes),
            'payment_method_id' => $paymentMethod->id,
            'reference_number' => $this->faker->optional(0.7)->bothify('PAY-####-????'),
            'status' => $this->faker->randomElement(['pending', 'completed', 'failed', 'cancelled']),
            'pay_period_start' => $this->getPayPeriodStart($paymentDate),
            'pay_period_end' => $this->getPayPeriodEnd($paymentDate),
            'hours_worked' => $paymentType === 'overtime' ? $this->faker->randomFloat(2, 2, 40) : null,
            'hourly_rate' => $paymentType === 'overtime' ? $this->faker->randomFloat(2, 10, 30) : null,
            'breakdown' => $this->generateBreakdown($paymentType),
            'notes' => $this->faker->optional(0.3)->sentence(),
        ];
    }

    /**
     * Calculate taxes based on amount
     */
    private function calculateTaxes(float $amount, int $tenantId): array
    {
        $breakdown = [];
        $totalTax = 0;

        // Try to get taxes from database first
        $taxes = Tax::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();

        if ($taxes->isNotEmpty()) {
            foreach ($taxes as $tax) {
                // FIX: Get raw rate value using accessor we created
                $rate = $tax->raw_rate; // Using the raw_rate accessor we added
                
                // Alternative if raw_rate accessor doesn't exist
                // $rate = $tax->getRawOriginal('rate'); 
                
                if ($tax->type === 'percentage') {
                    $taxAmount = $amount * ($rate / 100);
                } else {
                    // Fixed amount - need to convert from base currency back to display?
                    // For factory, we'll use the raw value
                    $taxAmount = $rate;
                }

                // Apply NSSF maximum cap if applicable
                if ($tax->code === 'NSSF_EMPLOYEE' && $taxAmount > 50000) {
                    $taxAmount = 50000;
                }

                $breakdown[] = [
                    'tax_id' => $tax->id,
                    'tax_code' => $tax->code,
                    'tax_name' => $tax->name,
                    'rate' => $rate,
                    'amount' => round($taxAmount, 2),
                    'type' => $tax->type,
                ];

                $totalTax += $taxAmount;
            }
        } else {
            // Fallback to default tax calculations
            foreach ($this->taxRates as $taxCode => $rate) {
                $taxAmount = $amount * $rate;

                // Apply NSSF maximum cap
                if ($taxCode === 'NSSF_EMPLOYEE' && $taxAmount > 50000) {
                    $taxAmount = 50000;
                }

                $breakdown[] = [
                    'tax_code' => $taxCode,
                    'tax_name' => $this->getTaxName($taxCode),
                    'rate' => $rate,
                    'amount' => round($taxAmount, 2),
                    'type' => 'percentage',
                ];

                $totalTax += $taxAmount;
            }
        }

        return [
            'total' => round($totalTax, 2),
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Generate amount based on payment type
     */
    private function generateAmountByType(string $paymentType): float
    {
        return match($paymentType) {
            'salary' => $this->faker->randomFloat(2, 500000, 3000000),    // 500k - 3M UGX
            'allowance' => $this->faker->randomFloat(2, 50000, 500000),   // 50k - 500k UGX
            'bonus' => $this->faker->randomFloat(2, 100000, 1000000),     // 100k - 1M UGX
            'overtime' => $this->faker->randomFloat(2, 50000, 300000),    // 50k - 300k UGX
            'advance' => $this->faker->randomFloat(2, 100000, 500000),    // 100k - 500k UGX
            default => $this->faker->randomFloat(2, 50000, 200000),       // 50k - 200k UGX
        };
    }

    /**
     * Generate description based on payment type and date
     */
    private function generateDescription(string $paymentType, $paymentDate): string
    {
        $month = $paymentDate->format('F Y');
        
        return match($paymentType) {
            'salary' => "Salary payment for {$month}",
            'allowance' => $this->faker->randomElement([
                "Transport allowance for {$month}",
                "Housing allowance for {$month}",
                "Meal allowance for {$month}",
                "Communication allowance for {$month}"
            ]),
            'bonus' => $this->faker->randomElement([
                "Performance bonus - Q" . ceil($paymentDate->format('n') / 3) . " " . $paymentDate->format('Y'),
                "Year-end bonus " . $paymentDate->format('Y'),
                "Project completion bonus"
            ]),
            'overtime' => "Overtime payment for {$month}",
            'advance' => "Salary advance - " . $paymentDate->format('d/m/Y'),
            'other' => $this->faker->sentence(4),
            default => $this->faker->sentence(4),
        };
    }

    /**
     * Generate payment breakdown JSON
     */
    private function generateBreakdown(string $paymentType): ?string
    {
        if ($paymentType !== 'salary') {
            return null;
        }

        $breakdown = [
            'basic_salary' => $this->faker->randomFloat(2, 400000, 2000000),
            'housing_allowance' => $this->faker->randomFloat(2, 0, 300000),
            'transport_allowance' => $this->faker->randomFloat(2, 0, 100000),
            'meal_allowance' => $this->faker->randomFloat(2, 0, 50000),
        ];

        return json_encode($breakdown);
    }

    /**
     * Get pay period start date (month start)
     */
    private function getPayPeriodStart($paymentDate): string
    {
        return $paymentDate->format('Y-m-01');
    }

    /**
     * Get pay period end date (month end)
     */
    private function getPayPeriodEnd($paymentDate): string
    {
        return $paymentDate->format('Y-m-t');
    }

    /**
     * Get tax name from code
     */
    private function getTaxName(string $taxCode): string
    {
        return match($taxCode) {
            'PAYE' => 'Pay As You Earn',
            'NSSF_EMPLOYEE' => 'NSSF Employee Contribution',
            'LST' => 'Local Service Tax',
            default => $taxCode,
        };
    }

    /**
     * State for completed payments
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }

    /**
     * State for pending payments
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * State for failed payments
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
        ]);
    }

    /**
     * State for salary payments only
     */
    public function salary(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_type' => 'salary',
        ]);
    }

    /**
     * State for bonus payments only
     */
    public function bonus(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_type' => 'bonus',
        ]);
    }
}