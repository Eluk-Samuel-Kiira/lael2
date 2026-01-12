<?php
// database/seeders/BillingPlanSeeder.php

namespace Database\Seeders;

use App\Models\BillingPlan;
use Illuminate\Database\Seeder;

class BillingPlanSeeder extends Seeder
{
    /**
     * Default plans that should always exist
     */
    private $defaultPlans = [
        [
            'method' => 'free',
            'code' => 'free',
            'name' => 'Free Plan',
        ],
        [
            'method' => 'starter',
            'code' => 'starter',
            'name' => 'Starter Plan',
        ],
        [
            'method' => 'professional',
            'code' => 'professional',
            'name' => 'Professional Plan',
        ],
        [
            'method' => 'enterprise',
            'code' => 'enterprise',
            'name' => 'Enterprise Plan',
        ],
        [
            'method' => 'onetime',
            'code' => 'onetime_lifetime',
            'name' => 'Lifetime License',
        ],
    ];

    public function run()
    {
        $this->command->info('Seeding billing plans...');

        // Clear existing plans (optional - uncomment if you want fresh data)
        // BillingPlan::query()->delete();

        // Create default plans
        foreach ($this->defaultPlans as $plan) {
            $this->createOrUpdatePlan($plan);
        }

        // Create some random additional plans
        $this->createRandomPlans();

        $this->command->info('Billing plans seeded successfully!');
    }

    private function createOrUpdatePlan(array $planData)
    {
        $existingPlan = BillingPlan::where('plan_code', $planData['code'])->first();

        if ($existingPlan) {
            $this->command->info("Plan '{$planData['code']}' already exists. Skipping...");
            return;
        }

        $factoryMethod = $planData['method'];
        
        BillingPlan::factory()->{$factoryMethod}()->create([
            'plan_code' => $planData['code'],
            'plan_name' => $planData['name'],
        ]);

        $this->command->info("Created plan: {$planData['name']}");
    }

    private function createRandomPlans()
    {
        // Create 3-5 random additional plans
        $count = rand(3, 5);
        
        BillingPlan::factory()
            ->count($count)
            ->active()
            ->create();
        
        $this->command->info("Created {$count} random additional plans.");
        
        // Create a few inactive plans
        BillingPlan::factory()
            ->count(2)
            ->create(['is_active' => false]);
            
        $this->command->info("Created 2 inactive plans.");
    }
}