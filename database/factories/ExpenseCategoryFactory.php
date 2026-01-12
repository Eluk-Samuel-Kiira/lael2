<?php
// database/factories/ExpenseCategoryFactory.php

namespace Database\Factories;

use App\Models\ExpenseCategory;
use App\Models\Tenant;
use App\Models\ChartOfAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseCategoryFactory extends Factory
{
    protected $model = ExpenseCategory::class;

    public function definition()
    {
        $tenant = Tenant::inRandomOrder()->first();
        
        return [
            'tenant_id' => $tenant->id,
            'name' => $this->faker->unique()->words(2, true),
            'code' => strtoupper($this->faker->unique()->bothify('EXP-??-##')),
            'description' => $this->faker->sentence(),
            'gl_account_id' => ChartOfAccount::where('tenant_id', $tenant->id)
                ->where('account_type', 'like', 'expense%')
                ->inRandomOrder()
                ->first()
                ?->id,
            'requires_receipt' => $this->faker->boolean(70),
            'requires_approval' => $this->faker->boolean(30),
            'budget_monthly' => $this->faker->optional()->randomFloat(2, 100, 10000),
            'budget_annual' => $this->faker->optional()->randomFloat(2, 1000, 100000),
            'is_active' => true,
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

    public function withAccount($accountId)
    {
        return $this->state(function (array $attributes) use ($accountId) {
            return [
                'gl_account_id' => $accountId,
            ];
        });
    }

    // Add these missing methods:
    public function active()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => true,
            ];
        });
    }

    public function inactive()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => false,
            ];
        });
    }

    public function requiresReceipt()
    {
        return $this->state(function (array $attributes) {
            return [
                'requires_receipt' => true,
            ];
        });
    }

    public function noReceiptRequired()
    {
        return $this->state(function (array $attributes) {
            return [
                'requires_receipt' => false,
            ];
        });
    }

    public function requiresApproval()
    {
        return $this->state(function (array $attributes) {
            return [
                'requires_approval' => true,
            ];
        });
    }

    public function autoApproved()
    {
        return $this->state(function (array $attributes) {
            return [
                'requires_approval' => false,
            ];
        });
    }

    public function withBudget($monthly = null, $annual = null)
    {
        return $this->state(function (array $attributes) use ($monthly, $annual) {
            return [
                'budget_monthly' => $monthly ?? $this->faker->randomFloat(2, 100, 10000),
                'budget_annual' => $annual ?? $this->faker->randomFloat(2, 1000, 100000),
            ];
        });
    }

    public function withoutBudget()
    {
        return $this->state(function (array $attributes) {
            return [
                'budget_monthly' => null,
                'budget_annual' => null,
            ];
        });
    }
}