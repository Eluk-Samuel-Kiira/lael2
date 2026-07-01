<?php
// database/seeders/EmployeePaymentSeeder.php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\EmployeePayment;
use Illuminate\Database\Seeder;

class EmployeePaymentSeeder extends Seeder
{
    public function run(): void
    {
        $employees = Employee::with('tenant')->get();

        if ($employees->isEmpty()) {
            $this->command->info('No employees found. Please run Employee seeder first.');
            return;
        }

        foreach ($employees as $employee) {
            // Create 3-8 payments per employee
            $paymentCount = rand(3, 8);
            
            EmployeePayment::factory()
                ->count($paymentCount)
                ->create([
                    'employee_id' => $employee->id,
                    'tenant_id' => $employee->tenant_id,
                ]);
        }

        $this->command->info("Created payments for {$employees->count()} employees.");
    }
}