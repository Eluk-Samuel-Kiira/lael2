<?php
// database/seeders/BillingPlanSeeder.php

namespace Database\Seeders;

use App\Models\BillingPlan;
use Illuminate\Database\Seeder;

class BillingPlanSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('Seeding billing plans...');

        // Clear existing plans (optional)
        // BillingPlan::query()->delete();

        $plans = [
            [
                'method' => 'free',
                'code' => 'free',
                'name' => 'Free Trial',
            ],
            [
                'method' => 'starter',
                'code' => 'starter',
                'name' => 'Starter Plan',
            ],
            [
                'method' => 'business',
                'code' => 'business',
                'name' => 'Business Plan',
            ],
            [
                'method' => 'enterprise',
                'code' => 'enterprise',
                'name' => 'Enterprise Plan',
            ],
            [
                'method' => 'lifetime',
                'code' => 'onetime_lifetime',
                'name' => 'Lifetime License',
            ],
        ];

        foreach ($plans as $plan) {
            $this->createOrUpdatePlan($plan);
        }

        $this->command->info('Billing plans seeded successfully!');
    }

    private function createOrUpdatePlan(array $planData)
    {
        $existingPlan = BillingPlan::where('plan_code', $planData['code'])->first();

        if ($existingPlan) {
            $this->command->info("Plan '{$planData['code']}' already exists. Updating...");
            
            // Update existing plan with factory data
            $factoryMethod = $planData['method'];
            $factoryData = BillingPlan::factory()->{$factoryMethod}()->make()->toArray();
            
            $existingPlan->update($factoryData);
            
            $this->command->info("Updated plan: {$planData['name']}");
            return;
        }

        $factoryMethod = $planData['method'];
        
        BillingPlan::factory()->{$factoryMethod}()->create([
            'plan_code' => $planData['code'],
            'plan_name' => $planData['name'],
        ]);

        $this->command->info("Created plan: {$planData['name']}");
    }
}