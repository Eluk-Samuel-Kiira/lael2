<?php
// database/seeders/EmployeeSeeder.php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Faker\Factory as FakerFactory;

class EmployeeSeeder extends Seeder
{
    protected $faker;

    public function __construct()
    {
        $this->faker = FakerFactory::create();
    }

    public function run(): void
    {
        // Get all users
        $users = User::all();

        if ($users->isEmpty()) {
            $this->command->error('No users found. Please run UserSeeder first.');
            return;
        }

        $employeeCount = 0;

        foreach ($users as $user) {
            try {
                // Check if employee already exists for this user
                if (Employee::where('user_id', $user->id)->exists()) {
                    continue;
                }

                $employeeType = $this->faker->randomElement(['permanent', 'contract', 'casual', 'temporary', 'intern', 'probation']);
                $isSalaryRecurring = $this->faker->boolean(80); // 80% chance of recurring salary
                
                // Generate documents array
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
                if ($this->faker->boolean(50)) {
                    $documents[] = [
                        'type' => 'id',
                        'path' => 'documents/id_' . $user->id . '.jpg',
                        'uploaded_at' => now()->toDateTimeString()
                    ];
                }

                Employee::create([
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
                    'hire_date' => $user->created_at ?? now()->subDays(rand(30, 365)),
                    'job_title' => $user->job_title ?? $this->faker->jobTitle(),
                    'employee_type' => $employeeType,
                    'is_active' => $user->status === 'active',
                    
                    // Salary Information
                    'salary' => $this->generateSalary($user->job_title),
                    'salary_type' => $this->faker->randomElement(['hourly', 'weekly', 'monthly', 'annual']),
                    'is_salary_recurring' => $isSalaryRecurring,
                    'recurring_day' => $isSalaryRecurring ? $this->faker->numberBetween(1, 28) : null,
                    
                    // Tax & Social Security
                    'nssf_number' => $this->faker->boolean(70) ? $this->faker->numerify('NSSF-####-####') : null,
                    'tin_number' => $this->faker->boolean(80) ? $this->faker->numerify('TIN-####-####-####') : null,
                    
                    // Bank Details
                    'bank_name' => $this->faker->boolean(85) ? $this->faker->randomElement(['Equity Bank', 'KCB', 'Cooperative Bank', 'Stanbic Bank', 'NCBA', 'Absa Bank']) : null,
                    'bank_account_number' => $this->faker->boolean(85) ? $this->faker->bankAccountNumber() : null,
                    'bank_branch' => $this->faker->boolean(70) ? $this->faker->city() . ' Branch' : null,
                    
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
                    'notes' => $this->faker->boolean(30) ? $this->faker->sentence() : null,
                ]);

                $employeeCount++;
                
            } catch (\Exception $e) {
                $this->command->error("Error creating employee for user {$user->id}: " . $e->getMessage());
            }
        }

        $this->command->info("Successfully created {$employeeCount} employees from {$users->count()} users.");
    }

    /**
     * Generate salary based on job title and employee type
     */
    private function generateSalary(?string $jobTitle): float
    {
        $baseSalaries = [
            'manager' => 60000,
            'director' => 80000,
            'supervisor' => 50000,
            'developer' => 70000,
            'designer' => 55000,
            'analyst' => 60000,
            'administrator' => 45000,
            'coordinator' => 48000,
            'specialist' => 52000,
            'assistant' => 35000,
            'clerk' => 30000,
            'officer' => 40000,
            'executive' => 55000,
            'consultant' => 65000,
            'engineer' => 70000,
        ];

        if ($jobTitle) {
            $jobTitleLower = strtolower($jobTitle);
            foreach ($baseSalaries as $key => $salary) {
                if (str_contains($jobTitleLower, $key)) {
                    return $salary + rand(-5000, 15000);
                }
            }
        }

        // Default salary if no match found
        return 40000 + rand(-10000, 20000);
    }
}