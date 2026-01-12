<?php
// database/factories/ExpenseFactory.php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\Tenant;
use App\Models\ExpenseCategory;
use App\Models\User;
use App\Models\Employee;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition()
    {
        // Get existing tenant
        $tenant = Tenant::inRandomOrder()->first();
        
        if (!$tenant) {
            throw new \Exception('No tenants found. Please create tenants first.');
        }
        
        $tenantId = $tenant->id;
        $date = $this->faker->dateTimeBetween('-1 year', 'now');
        
        // Get existing category (MUST exist)
        $category = ExpenseCategory::where('tenant_id', $tenantId)
            ->inRandomOrder()
            ->first();
        
        if (!$category) {
            throw new \Exception("No expense categories found for tenant {$tenantId}. Please create expense categories first.");
        }
        
        // Get existing users (MUST exist)
        $users = User::where('tenant_id', $tenantId)->get();
        
        if ($users->isEmpty()) {
            throw new \Exception("No users found for tenant {$tenantId}. Please create users first.");
        }
        
        // Get existing employee (MUST exist)
        $employee = Employee::where('tenant_id', $tenantId)->inRandomOrder()->first();
        
        if (!$employee) {
            throw new \Exception("No employees found for tenant {$tenantId}. Please create employees first.");
        }
        
        // Get existing payment method (MUST exist)
        $paymentMethod = PaymentMethod::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->inRandomOrder()
            ->first();
        
        if (!$paymentMethod) {
            // If no payment method exists, create one
            $paymentMethod = PaymentMethod::factory()->create([
                'tenant_id' => $tenantId,
                'created_by' => $users->random()->id,
                'is_active' => true,
            ]);
        }
        
        $createdBy = $users->random()->id;
        $approvedBy = $this->faker->optional(0.8)->passthrough($users->random()->id);
        
        return [
            'tenant_id' => $tenantId,
            'expense_number' => 'EXP-' . date('Y') . '-' . $this->faker->unique()->numerify('#####'),
            'date' => $date,
            'description' => $this->faker->sentence(6),
            'category_id' => $category->id,
            'vendor_name' => $this->faker->company(),
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'tax_amount' => $this->faker->randomFloat(2, 0, 200),
            'payment_method_id' => $paymentMethod->id, // Changed from payment_method
            'payment_status' => $this->faker->randomElement(['pending', 'paid', 'reimbursed']),
            'paid_date' => $this->faker->optional(0.7)->dateTimeBetween($date, 'now'),
            'is_recurring' => $this->faker->boolean(20),
            'recurring_frequency' => $this->faker->optional()->randomElement(['weekly', 'monthly', 'quarterly', 'annually']),
            'next_recurring_date' => $this->faker->optional()->dateTimeBetween('now', '+1 year'),
            'receipt_url' => $this->faker->optional(0.6)->url(),
            'approved_by' => $approvedBy,
            'approved_at' => $this->faker->optional(0.8)->dateTimeBetween($date, 'now'),
            'employee_id' => $employee->id,
            'created_by' => $createdBy,
            'created_at' => $date,
            'updated_at' => $date,
        ];
    }

    public function forTenant($tenantId)
    {
        return $this->state(function (array $attributes) use ($tenantId) {
            return [
                'tenant_id' => $tenantId,
            ];
        });
    }

    public function withCategory($categoryId)
    {
        return $this->state(function (array $attributes) use ($categoryId) {
            return [
                'category_id' => $categoryId,
            ];
        });
    }
    
    public function forEmployee($employeeId)
    {
        return $this->state(function (array $attributes) use ($employeeId) {
            return [
                'employee_id' => $employeeId,
            ];
        });
    }
    
    public function createdBy($userId)
    {
        return $this->state(function (array $attributes) use ($userId) {
            return [
                'created_by' => $userId,
            ];
        });
    }
    
    public function withPaymentMethod($paymentMethodId)
    {
        return $this->state(function (array $attributes) use ($paymentMethodId) {
            return [
                'payment_method_id' => $paymentMethodId,
            ];
        });
    }
}