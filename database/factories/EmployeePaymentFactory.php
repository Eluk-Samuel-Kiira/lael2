<?php
// database/factories/EmployeePaymentFactory.php

namespace Database\Factories;

use App\Models\EmployeePayment;
use App\Models\Employee;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeePaymentFactory extends Factory
{
    protected $model = EmployeePayment::class;

    public function definition(): array
    {
        $paymentDate = $this->faker->dateTimeBetween('-1 year', 'now');
        
        // Get payment types and make sure we handle case variations
        $paymentTypes = EmployeePayment::getPaymentTypes();
        $paymentType = $this->faker->randomElement($paymentTypes);
        
        // Get employee first to determine tenant_id
        $employee = Employee::inRandomOrder()->first() ?? Employee::factory()->create();
        $tenantId = $employee->tenant_id;
        
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
            'description' => $this->generateDescription($paymentType),
            'amount' => $this->faker->randomFloat(2, 100, 5000),
            'payment_method_id' => $paymentMethod->id,
            'reference_number' => $this->faker->optional(0.7)->bothify('REF#####'),
            'status' => $this->faker->randomElement(EmployeePayment::getStatuses()),
            'hours_worked' => $this->faker->optional(0.6)->randomFloat(2, 10, 80),
            'hourly_rate' => $this->faker->optional(0.6)->randomFloat(2, 15, 50),
            'notes' => $this->faker->optional(0.3)->sentence(),
        ];
    }

    private function generateDescription(string $paymentType): string
    {
        // Convert to lowercase for consistent matching
        $type = strtolower($paymentType);
        
        return match($type) {
            'salary' => 'Monthly salary payment',
            'allowance' => $this->faker->randomElement(['Transport allowance', 'Housing allowance', 'Meal allowance']),
            'bonus' => $this->faker->randomElement(['Performance bonus', 'Year-end bonus', 'Project completion bonus']),
            'overtime' => 'Overtime payment',
            'advance' => 'Salary advance',
            'other' => $this->faker->sentence(4),
            default => $this->faker->sentence(4), // Fallback for any unexpected types
        };
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed', // Use string instead of constant
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending', // Use string instead of constant
        ]);
    }
}