<?php
// database/factories/ChartOfAccountFactory.php

namespace Database\Factories;

use App\Models\ChartOfAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChartOfAccountFactory extends Factory
{
    protected $model = ChartOfAccount::class;

    // System account codes to avoid
    private $systemCodes = ['1010', '1020', '1030', '1210', '2010', '2020', '3010', '3020', '4010', '4020', '5010', '5020', '5030', '5040'];

    public function definition(): array
    {
        $types = [
            'asset_current', 'asset_fixed', 'asset_non_current',
            'liability_current', 'liability_long_term',
            'equity', 'equity_retained_earnings',
            'revenue', 'revenue_other',
            'expense', 'expense_cost_of_goods', 'expense_operating'
        ];

        $type = $this->faker->randomElement($types);
        $normalBalance = in_array($type, ['asset_current', 'asset_fixed', 'asset_non_current', 'expense', 'expense_cost_of_goods', 'expense_operating']) 
            ? 'D' : 'C';

        return [
            'account_code' => $this->generateUniqueAccountCode(),
            'account_name' => $this->faker->words(3, true),
            'account_type' => $type,
            'normal_balance' => $normalBalance,
            'is_active' => $this->faker->boolean(90),
            'is_system_account' => false,
            'description' => $this->faker->sentence(),
        ];
    }

    /**
     * Generate a unique account code that doesn't conflict with system codes
     */
    private function generateUniqueAccountCode(): string
    {
        do {
            $code = (string) $this->faker->numberBetween(6000, 9999); // Use higher range
        } while (in_array($code, $this->systemCodes) || 
                 ChartOfAccount::where('account_code', $code)->exists()); // Check DB too
        
        return $code;
    }

    public function asset(): self
    {
        return $this->state(fn (array $attributes) => [
            'account_type' => 'asset_current',
            'normal_balance' => 'D',
        ]);
    }

    public function liability(): self
    {
        return $this->state(fn (array $attributes) => [
            'account_type' => 'liability_current',
            'normal_balance' => 'C',
        ]);
    }

    public function revenue(): self
    {
        return $this->state(fn (array $attributes) => [
            'account_type' => 'revenue',
            'normal_balance' => 'C',
        ]);
    }

    public function expense(): self
    {
        return $this->state(fn (array $attributes) => [
            'account_type' => 'expense',
            'normal_balance' => 'D',
        ]);
    }

    public function inactive(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function system(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_system_account' => true,
        ]);
    }
}