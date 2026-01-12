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

                // Create employee from user data
                Employee::create([
                    'user_id' => $user->id,
                    'tenant_id' => $user->tenant_id ?? 1, // Use default if null
                    'department_id' => $user->department_id ?? 1, // Use default if null
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'phone' => $user->telephone_number,
                    'job_title' => $user->job_title,
                    'hire_date' => $user->created_at ?? now()->subDays(rand(30, 365)),
                    'salary' => $this->generateSalary($user->job_title),
                    'salary_type' => $this->faker->randomElement(['hourly', 'weekly', 'monthly', 'annual']),
                    'is_active' => $user->status === 'active',
                ]);

                $employeeCount++;
                
            } catch (\Exception $e) {
                $this->command->error("Error creating employee for user {$user->id}: " . $e->getMessage());
            }
        }

        $this->command->info("Successfully created {$employeeCount} employees from {$users->count()} users.");
    }

    /**
     * Generate salary based on job title
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