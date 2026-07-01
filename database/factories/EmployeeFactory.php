<?php
// database/factories/EmployeeFactory.php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        $user = User::inRandomOrder()->first() ?? User::factory()->create();
        $employeeType = $this->faker->randomElement(['permanent', 'contract', 'casual', 'temporary', 'intern', 'probation']);
        $isSalaryRecurring = $this->faker->boolean(80);
        
        // Generate sample documents
        $documents = [];
        if ($this->faker->boolean(70)) {
            $documents[] = [
                'type' => 'cv',
                'path' => 'documents/cv_' . $user->id . '.pdf',
                'uploaded_at' => now()->toDateTimeString()
            ];
        }
        if ($this->faker->boolean(60)) {
            $documents[] = [
                'type' => 'contract',
                'path' => 'documents/contract_' . $user->id . '.pdf',
                'uploaded_at' => now()->toDateTimeString()
            ];
        }
        
        return [
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id ?? 1,
            'department_id' => $user->department_id ?? rand(1, 5),
            
            // Personal Information
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->telephone_number ?? $this->faker->phoneNumber(),
            'gender' => $this->faker->randomElement(['male', 'female', 'other']),
            'date_of_birth' => $this->faker->dateTimeBetween('-60 years', '-18 years'),
            'residence' => $this->faker->address(),
            
            // Employment Details
            'hire_date' => $this->faker->dateTimeBetween('-5 years', 'now'),
            'termination_date' => $this->faker->optional(0.1)->dateTimeBetween('-1 year', 'now'),
            'job_title' => $user->job_title ?? $this->faker->jobTitle(),
            'employee_type' => $employeeType,
            'is_active' => $this->faker->boolean(90),
            
            // Salary Information
            'salary' => $this->faker->randomFloat(2, 30000, 150000),
            'salary_type' => $this->faker->randomElement(['hourly', 'weekly', 'monthly', 'annual']),
            'is_salary_recurring' => $isSalaryRecurring,
            'recurring_day' => $isSalaryRecurring ? $this->faker->numberBetween(1, 28) : null,
            
            // Tax & Social Security
            'nssf_number' => $this->faker->optional(0.7)->numerify('NSSF-####-####'),
            'tin_number' => $this->faker->optional(0.8)->numerify('TIN-####-####-####'),
            
            // Bank Details
            'bank_name' => $this->faker->optional(0.85)->randomElement(['Equity Bank', 'KCB', 'Cooperative Bank', 'Stanbic Bank', 'NCBA']),
            'bank_account_number' => $this->faker->optional(0.85)->bankAccountNumber(),
            'bank_branch' => $this->faker->optional(0.7)->city() . ' Branch',
            
            // Identification
            'id_type' => $this->faker->randomElement(['national_id', 'passport', 'drivers_license', 'voters_card', 'other']),
            'id_number' => $this->faker->numerify('##########'),
            'qualification' => $this->faker->randomElement(['High School', 'Diploma', 'Bachelor\'s Degree', 'Master\'s Degree', 'PhD', 'Certificate']),
            
            // Next of Kin
            'next_of_kin_name' => $this->faker->name(),
            'next_of_kin_contact' => $this->faker->phoneNumber(),
            'next_of_kin_relationship' => $this->faker->randomElement(['Spouse', 'Parent', 'Sibling', 'Child', 'Friend']),
            
            // Documents
            'documents' => !empty($documents) ? json_encode($documents) : null,
            
            // Notes
            'notes' => $this->faker->optional(0.3)->sentence(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'termination_date' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'termination_date' => now()->subDays(rand(1, 365)),
        ]);
    }

    public function permanent(): static
    {
        return $this->state(fn (array $attributes) => [
            'employee_type' => 'permanent',
        ]);
    }

    public function contract(): static
    {
        return $this->state(fn (array $attributes) => [
            'employee_type' => 'contract',
        ]);
    }

    public function recurringSalary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_salary_recurring' => true,
            'recurring_day' => $this->faker->numberBetween(1, 28),
        ]);
    }
}