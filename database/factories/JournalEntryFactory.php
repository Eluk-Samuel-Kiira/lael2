<?php

namespace Database\Factories;

use App\Models\JournalEntry;
use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

class JournalEntryFactory extends Factory
{
    protected $model = JournalEntry::class;

    public function definition(): array
    {
        $entryDate = $this->faker->dateTimeBetween('-1 year', 'now');
        
        return [
            'entry_number' => $this->generateUniqueEntryNumber(),
            'entry_date' => $entryDate,
            'description' => $this->faker->sentence(),
            'reference_number' => $this->faker->optional()->numerify('INV-#####'),
            'source_module' => $this->faker->randomElement(['sales', 'purchases', 'expenses', 'manual', null]),
            'source_id' => $this->faker->optional()->randomNumber(),
            'total_debit' => 0,
            'total_credit' => 0,
            'status' => $this->faker->randomElement(['draft', 'posted']),
            'posted_at' => $this->faker->optional()->dateTimeThisYear(),
        ];
    }

    public function configure()
    {
        return $this->afterMaking(function (JournalEntry $journal) {
            // Check if entry_number is already set (it should be from definition)
            if (empty($journal->entry_number)) {
                // Regenerate if somehow missing
                $journal->entry_number = $this->generateUniqueEntryNumber();
            }
            
            // Find an accounting period for the entry date
            $period = AccountingPeriod::where('tenant_id', $journal->tenant_id)
                ->where('start_date', '<=', $journal->entry_date)
                ->where('end_date', '>=', $journal->entry_date)
                ->first();
            
            if (!$period) {
                // Create a period if none exists for this date
                $period = AccountingPeriod::factory()->create([
                    'tenant_id' => $journal->tenant_id,
                    'start_date' => $journal->entry_date->copy()->startOfMonth(),
                    'end_date' => $journal->entry_date->copy()->endOfMonth(),
                    'period_name' => $journal->entry_date->format('F Y'),
                    'status' => 'open',
                ]);
            }
            
            $journal->period_id = $period->id;
        })->afterCreating(function (JournalEntry $journal) {
            // Create 2-4 lines for each journal entry
            $lineCount = $this->faker->numberBetween(2, 4);
            $totalDebit = 0;
            $totalCredit = 0;

            // Get accounts for this tenant
            $accounts = ChartOfAccount::where('tenant_id', $journal->tenant_id)
                ->where('is_active', true)
                ->get();
            
            if ($accounts->isEmpty()) {
                // Create some accounts if none exist
                $accounts = ChartOfAccount::factory()
                    ->count(5)
                    ->create(['tenant_id' => $journal->tenant_id]);
            }

            for ($i = 1; $i <= $lineCount; $i++) {
                $isDebit = $i < $lineCount; // All but last line are debits
                $amount = $this->faker->randomFloat(2, 10, 1000);
                
                if ($isDebit) {
                    $totalDebit += $amount;
                } else {
                    // Last line makes it balanced
                    $amount = $totalDebit - $totalCredit;
                    $totalCredit += $amount;
                }

                $journal->lines()->create([
                    'account_id' => $accounts->random()->id,
                    'line_number' => $i,
                    'description' => $this->faker->sentence(),
                    'debit_amount' => $isDebit ? $amount : 0,
                    'credit_amount' => $isDebit ? 0 : $amount,
                ]);
            }

            $journal->update([
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
            ]);
        });
    }
    
    private function generateUniqueEntryNumber(): string
    {
        $year = date('Y');
        $prefix = "JE-{$year}-";
        
        // Generate a unique number using UUID or timestamp
        return $prefix . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}