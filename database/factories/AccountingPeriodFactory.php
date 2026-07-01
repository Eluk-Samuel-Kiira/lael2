<?php
// database/factories/AccountingPeriodFactory.php

namespace Database\Factories;

use App\Models\AccountingPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountingPeriodFactory extends Factory
{
    protected $model = AccountingPeriod::class;

    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-1 year', 'now');
        $endDate = (clone $startDate)->modify('+1 month -1 day');

        return [
            'period_name' => $startDate->format('F Y'),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $this->faker->randomElement(['open', 'closed', 'locked']),
            'is_fiscal_year_end' => false,
        ];
    }

    public function open(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'open',
        ]);
    }

    public function closed(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'closed',
            'closed_at' => $this->faker->dateTimeThisYear(),
        ]);
    }

    public function locked(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'locked',
        ]);
    }

    public function fiscalYearEnd(): self
    {
        return $this->state(fn (array $attributes) => [
            'period_name' => 'Year End ' . date('Y'),
            'start_date' => date('Y-01-01'),
            'end_date' => date('Y-12-31'),
            'is_fiscal_year_end' => true,
        ]);
    }
}