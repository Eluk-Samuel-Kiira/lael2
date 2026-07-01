<?php
// database/factories/ExpenseFactory.php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\Tenant;
use App\Models\ExpenseCategory;
use App\Models\User;
use App\Models\Employee;
use App\Models\PaymentMethod;
use App\Models\{ Department, Supplier };
use App\Models\Location;
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
        
        // Get department if exists (nullable)
        $department = Department::where('tenant_id', $tenantId)
            ->inRandomOrder()
            ->first();
        
        // Get location if exists (nullable)
        $location = Location::where('tenant_id', $tenantId)
            ->inRandomOrder()
            ->first();
        
        // Get existing users
        $users = User::where('tenant_id', $tenantId)->get();
        
        if ($users->isEmpty()) {
            throw new \Exception("No users found for tenant {$tenantId}. Please create users first.");
        }
        
        // Get existing employee
        $employee = Employee::where('tenant_id', $tenantId)->inRandomOrder()->first();
        
        if (!$employee) {
            throw new \Exception("No employees found for tenant {$tenantId}. Please create employees first.");
        }
        
        // Get existing payment method
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
        
        // Get supplier if exists
        $supplier = Supplier::where('tenant_id', $tenantId)
            ->inRandomOrder()
            ->first();
        
        // Calculate amounts
        $grossAmount = $this->faker->randomFloat(2, 10, 1000);  // Original expense amount
        $additiveTax = 0;
        $withholdingTax = 0;
        $taxBreakdown = [];
        
        // Randomly decide if this expense has taxes
        $hasVat = $this->faker->boolean(60);
        $hasWht = $this->faker->boolean(40);
        
        if ($hasVat) {
            $vatRate = 18;
            $vatAmount = $grossAmount * ($vatRate / 100);
            $additiveTax += $vatAmount;
            $taxBreakdown[] = [
                'tax_id' => 1,
                'tax_name' => 'VAT',
                'tax_code' => 'VAT18',
                'rate' => $vatRate,
                'type' => 'percentage',
                'amount' => $vatAmount,
                'is_withholding_tax' => false,
            ];
        }
        
        if ($hasWht) {
            $whtRate = 6;
            $whtAmount = $grossAmount * ($whtRate / 100);
            $withholdingTax += $whtAmount;
            $taxBreakdown[] = [
                'tax_id' => 2,
                'tax_name' => 'Withholding Tax',
                'tax_code' => 'WHT6',
                'rate' => $whtRate,
                'type' => 'percentage',
                'amount' => $whtAmount,
                'is_withholding_tax' => true,
            ];
        }
        
        $totalTax = $additiveTax + $withholdingTax;
        $netAmount = $grossAmount + $additiveTax - $withholdingTax;  // What supplier actually gets
        $totalAmount = $grossAmount + $additiveTax;  // Gross + additive tax (for reference)
        
        return [
            'tenant_id' => $tenantId,
            'expense_number' => 'EXP-' . date('Y') . '-' . $this->faker->unique()->numerify('#####'),
            'date' => $date,
            'description' => $this->faker->sentence(6),
            'category_id' => $category->id,
            'supplier_id' => $supplier ? $supplier->id : null,
            'vendor_name' => $supplier ? $supplier->name : $this->faker->company(),
            'gross_amount' => $grossAmount,
            'tax_amount' => $totalTax,
            'net_amount' => $netAmount,
            'total_amount' => $totalAmount,
            'tax_breakdown' => json_encode($taxBreakdown),
            'payment_method_id' => $paymentMethod->id,
            'payment_status' => $this->faker->randomElement(['pending', 'paid', 'reimbursed']),
            'paid_date' => $this->faker->optional(0.7)->dateTimeBetween($date, 'now'),
            'is_recurring' => $this->faker->boolean(20),
            'recurring_frequency' => $this->faker->optional()->randomElement(['weekly', 'monthly', 'quarterly', 'annually']),
            'next_recurring_date' => $this->faker->optional()->dateTimeBetween('now', '+1 year'),
            'receipt_url' => $this->faker->optional(0.6)->url(),
            'approved_by' => $approvedBy,
            'approved_at' => $this->faker->optional(0.8)->dateTimeBetween($date, 'now'),
            'employee_id' => $employee->id,
            'department_id' => $department ? $department->id : null,
            'location_id' => $location ? $location->id : null,
            'created_by' => $createdBy,
            'created_at' => $date,
            'updated_at' => $date,
        ];
    }

    // ... keep all your existing state methods ...
    
    public function forSupplier($supplierId)
    {
        return $this->state(function (array $attributes) use ($supplierId) {
            $supplier = Supplier::find($supplierId);
            return [
                'supplier_id' => $supplierId,
                'vendor_name' => $supplier ? $supplier->name : $attributes['vendor_name'],
            ];
        });
    }
    
    public function withVat($vatRate = 18)
    {
        return $this->state(function (array $attributes) use ($vatRate) {
            $grossAmount = $attributes['gross_amount'] ?? $this->faker->randomFloat(2, 100, 1000);
            $vatAmount = $grossAmount * ($vatRate / 100);
            $additiveTax = $vatAmount;
            
            // Get existing withholding tax from breakdown
            $existingBreakdown = isset($attributes['tax_breakdown']) ? json_decode($attributes['tax_breakdown'], true) : [];
            $withholdingTax = 0;
            
            foreach ($existingBreakdown as $tax) {
                if ($tax['is_withholding_tax'] ?? false) {
                    $withholdingTax += $tax['amount'];
                }
            }
            
            $totalTax = $additiveTax + $withholdingTax;
            $netAmount = $grossAmount + $additiveTax - $withholdingTax;
            $totalAmount = $grossAmount + $additiveTax;
            
            // Add VAT to breakdown
            $existingBreakdown[] = [
                'tax_id' => 1,
                'tax_name' => 'VAT',
                'tax_code' => 'VAT' . $vatRate,
                'rate' => $vatRate,
                'type' => 'percentage',
                'amount' => $vatAmount,
                'is_withholding_tax' => false,
            ];
            
            return [
                'gross_amount' => $grossAmount,
                'tax_amount' => $totalTax,
                'net_amount' => $netAmount,
                'total_amount' => $totalAmount,
                'tax_breakdown' => json_encode($existingBreakdown),
            ];
        });
    }
    
    public function withWht($whtRate = 6)
    {
        return $this->state(function (array $attributes) use ($whtRate) {
            $grossAmount = $attributes['gross_amount'] ?? $this->faker->randomFloat(2, 100, 1000);
            $whtAmount = $grossAmount * ($whtRate / 100);
            $withholdingTax = $whtAmount;
            
            // Get existing additive tax from breakdown
            $existingBreakdown = isset($attributes['tax_breakdown']) ? json_decode($attributes['tax_breakdown'], true) : [];
            $additiveTax = 0;
            
            foreach ($existingBreakdown as $tax) {
                if (!($tax['is_withholding_tax'] ?? false)) {
                    $additiveTax += $tax['amount'];
                }
            }
            
            $totalTax = $additiveTax + $withholdingTax;
            $netAmount = $grossAmount + $additiveTax - $withholdingTax;
            $totalAmount = $grossAmount + $additiveTax;
            
            // Add WHT to breakdown
            $existingBreakdown[] = [
                'tax_id' => 2,
                'tax_name' => 'Withholding Tax',
                'tax_code' => 'WHT' . $whtRate,
                'rate' => $whtRate,
                'type' => 'percentage',
                'amount' => $whtAmount,
                'is_withholding_tax' => true,
            ];
            
            return [
                'gross_amount' => $grossAmount,
                'tax_amount' => $totalTax,
                'net_amount' => $netAmount,
                'total_amount' => $totalAmount,
                'tax_breakdown' => json_encode($existingBreakdown),
            ];
        });
    }
}