<?php

namespace Database\Factories;

use App\Models\OrderTax;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class OrderTaxFactory extends Factory
{
    protected $model = OrderTax::class;

    public function definition()
    {
        $tenant = Tenant::inRandomOrder()->first() ?? Tenant::factory()->create();
        $order = Order::inRandomOrder()->first() ?? Order::factory()->create(['tenant_id' => $tenant->id]);
        $user = User::inRandomOrder()->first() ?? User::factory()->create(['tenant_id' => $tenant->id]);
        
        $createdAt = $this->faker->dateTimeBetween('-1 year', 'now');
        $updatedAt = $this->faker->dateTimeBetween($createdAt, 'now');
        
        $taxRate = $this->faker->randomElement([0.0, 5.0, 7.5, 10.0, 12.5, 15.0, 18.0, 20.0, 25.0]);
        $orderAmount = $order->total ?? $this->faker->numberBetween(10000, 1000000);
        $taxAmount = ($orderAmount * $taxRate) / 100;
        
        $status = $this->faker->randomElement(['pending', 'remitted', 'overdue', 'cancelled']);
        $isRemitted = $status === 'remitted';
        
        $paymentDate = $this->faker->dateTimeBetween('-6 months', 'now');
        $dueDate = Carbon::parse($paymentDate)->addMonth()->startOfMonth()->addDays(14); // 15th of next month

        // Create metadata array
        $metadata = [
            'source' => 'factory',
            'calculation_method' => $this->faker->randomElement(['automatic', 'manual']),
            'applied_at' => $paymentDate->format('Y-m-d H:i:s'),
        ];

        return [
            // Core
            'order_id' => $order->id,
            'tenant_id' => $tenant->id,
            'created_by' => $user->id,

            // Tax details
            'tax_name' => $this->faker->randomElement([
                'VAT', 'PAYE', 'WHT', 'Income Tax', 'Corporate Tax',
                'Sales Tax', 'Service Tax', 'Excise Duty', 'Import Duty',
                'Capital Gains Tax', 'Property Tax', 'Stamp Duty'
            ]),
            'tax_rate' => $taxRate,
            'tax_amount' => (int) round($taxAmount * 100), // Convert to smallest currency unit
            'is_compound' => $this->faker->boolean(20),

            // Remittance status
            'status' => $status,
            'due_date' => $dueDate->format('Y-m-d'),

            // Period
            'tax_year' => (int) $paymentDate->format('Y'),
            'tax_month' => (int) $paymentDate->format('m'),
            'tax_quarter' => (int) ceil($paymentDate->format('m') / 3),

            // Remittance tracking (only if remitted)
            'remitted_at' => $isRemitted ? $paymentDate->format('Y-m-d H:i:s') : null,
            'remittance_reference' => $isRemitted ? $this->faker->bothify('TAX-####-????-####') : null,
            'remittance_transaction_ref' => $isRemitted ? $this->faker->uuid() : null,
            'remittance_payment_method_id' => $isRemitted ? PaymentMethod::inRandomOrder()->first()?->id : null,
            'remitted_by' => $isRemitted ? User::inRandomOrder()->first()?->id : null,

            // Extra
            'notes' => $this->faker->optional(0.3)->sentence(),
            'metadata' => $this->faker->optional(0.2)->passthrough($metadata), // Use passthrough instead of jsonEncode

            'created_at' => $createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $updatedAt->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Pending tax state
     */
    public function pending()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
                'remitted_at' => null,
                'remittance_reference' => null,
                'remittance_transaction_ref' => null,
                'remittance_payment_method_id' => null,
                'remitted_by' => null,
            ];
        });
    }

    /**
     * Remitted tax state
     */
    public function remitted()
    {
        $remittedAt = $this->faker->dateTimeBetween('-3 months', 'now');
        
        return $this->state(function (array $attributes) use ($remittedAt) {
            return [
                'status' => 'remitted',
                'remitted_at' => $remittedAt->format('Y-m-d H:i:s'),
                'remittance_reference' => $this->faker->bothify('TAX-####-????-####'),
                'remittance_transaction_ref' => $this->faker->uuid(),
                'remittance_payment_method_id' => PaymentMethod::inRandomOrder()->first()?->id,
                'remitted_by' => User::inRandomOrder()->first()?->id,
            ];
        });
    }

    /**
     * Overdue tax state
     */
    public function overdue()
    {
        $dueDate = Carbon::now()->subDays($this->faker->numberBetween(1, 30));
        
        return $this->state(function (array $attributes) use ($dueDate) {
            return [
                'status' => 'overdue',
                'due_date' => $dueDate->format('Y-m-d'),
                'remitted_at' => null,
                'remittance_reference' => null,
                'remittance_transaction_ref' => null,
            ];
        });
    }

    /**
     * Cancelled tax state
     */
    public function cancelled()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'cancelled',
                'remitted_at' => null,
                'remittance_reference' => null,
                'remittance_transaction_ref' => null,
            ];
        });
    }

    /**
     * VAT tax
     */
    public function vat()
    {
        $vatRate = $this->faker->randomElement([5.0, 7.5, 10.0, 12.5, 15.0, 18.0, 20.0]);
        
        return $this->state(function (array $attributes) use ($vatRate) {
            return [
                'tax_name' => 'VAT',
                'tax_rate' => $vatRate,
                'is_compound' => false,
            ];
        });
    }

    /**
     * Withholding tax
     */
    public function withholdingTax()
    {
        $whtRate = $this->faker->randomElement([3.0, 4.0, 5.0, 6.0, 7.5, 10.0, 15.0]);
        
        return $this->state(function (array $attributes) use ($whtRate) {
            return [
                'tax_name' => 'WHT',
                'tax_rate' => $whtRate,
                'is_compound' => false,
            ];
        });
    }

    /**
     * Compound tax (tax on tax)
     */
    public function compound()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_compound' => true,
                'notes' => 'Compound tax - applied on amount including other taxes',
            ];
        });
    }

    /**
     * For specific order
     */
    public function forOrder($orderId)
    {
        return $this->state(function (array $attributes) use ($orderId) {
            $order = Order::find($orderId);
            
            return [
                'order_id' => $orderId,
                'tenant_id' => $order?->tenant_id ?? $attributes['tenant_id'],
            ];
        });
    }

    /**
     * For specific period (year/month)
     */
    public function forPeriod(int $year, int $month)
    {
        $quarter = (int) ceil($month / 3);
        
        return $this->state(function (array $attributes) use ($year, $month, $quarter) {
            return [
                'tax_year' => $year,
                'tax_month' => $month,
                'tax_quarter' => $quarter,
            ];
        });
    }

    /**
     * Due this month
     */
    public function dueThisMonth()
    {
        $now = Carbon::now();
        $dueDate = $now->copy()->addMonth()->startOfMonth()->addDays(14);
        
        return $this->state(function (array $attributes) use ($now, $dueDate) {
            return [
                'tax_year' => (int) $now->format('Y'),
                'tax_month' => (int) $now->format('m'),
                'tax_quarter' => (int) ceil($now->format('m') / 3),
                'due_date' => $dueDate->format('Y-m-d'),
                'status' => 'pending',
            ];
        });
    }

    /**
     * With specific tax rate
     */
    public function withRate(float $rate)
    {
        return $this->state(function (array $attributes) use ($rate) {
            return [
                'tax_rate' => $rate,
            ];
        });
    }

    /**
     * With specific amount
     */
    public function withAmount(int $amount)
    {
        return $this->state(function (array $attributes) use ($amount) {
            return [
                'tax_amount' => $amount,
            ];
        });
    }

    /**
     * With remittance reference
     */
    public function withRemittanceReference(string $reference)
    {
        return $this->state(function (array $attributes) use ($reference) {
            return [
                'remittance_reference' => $reference,
                'status' => 'remitted',
                'remitted_at' => now()->format('Y-m-d H:i:s'),
            ];
        });
    }

    /**
     * Soft deleted tax
     */
    public function trashed()
    {
        return $this->state(function (array $attributes) {
            return [];
        })->afterCreating(function ($orderTax) {
            $orderTax->delete();
        });
    }
}