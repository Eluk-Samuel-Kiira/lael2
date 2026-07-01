<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GeneralLedgerSeeder extends Seeder
{
    public function run(): void
    {
        // Skip if general ledger already has data
        if (DB::table('general_ledger')->exists()) {
            $this->command->info('General ledger already has data. Skipping seeder...');
            return;
        }
        
        $this->command->info('Creating simple general ledger entries...');
        
        // Get a few journal entry lines (limit to 20 for speed)
        $journalLines = DB::table('journal_entry_lines as jel')
            ->join('journal_entries as je', 'jel.journal_id', '=', 'je.id')
            ->select(
                'jel.id as journal_line_id',
                'je.tenant_id',
                'je.entry_date',
                'je.period_id',
                'jel.account_id',
                'jel.debit_amount',
                'jel.credit_amount',
                'jel.description',
                'je.id as journal_entry_id'
            )
            ->limit(20) // ONLY 20 entries for speed!
            ->orderBy('jel.id')
            ->get();
            
        if ($journalLines->isEmpty()) {
            $this->command->warn('No journal entry lines found. Please run JournalEntryLineSeeder first.');
            return;
        }
        
        $this->command->info("Found {$journalLines->count()} journal lines to process");
        
        $entries = [];
        $now = Carbon::now();
        
        // Simple running balance - just increment
        $runningBalance = 0;
        
        foreach ($journalLines as $index => $line) {
            $runningBalance += $line->debit_amount - $line->credit_amount;
            
            $entries[] = [
                'tenant_id' => $line->tenant_id,
                'journal_line_id' => $line->journal_line_id,
                'account_id' => $line->account_id,
                'entry_date' => $line->entry_date,
                'period_id' => $line->period_id,
                'debit_amount' => $line->debit_amount,
                'credit_amount' => $line->credit_amount,
                'running_balance' => $runningBalance,
                'description' => $line->description,
                'source_module' => 'journal',
                'source_id' => $line->journal_entry_id,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            
            // Show progress
            if (($index + 1) % 5 === 0) {
                $this->command->info("Processed " . ($index + 1) . " lines...");
            }
        }
        
        $this->command->info("Inserting " . count($entries) . " general ledger entries...");
        
        try {
            DB::table('general_ledger')->insert($entries);
            $this->command->info("✓ Successfully inserted " . count($entries) . " general ledger entries!");
        } catch (\Exception $e) {
            $this->command->error("Error: " . $e->getMessage());
            
            // Try one by one
            $success = 0;
            foreach ($entries as $entry) {
                try {
                    DB::table('general_ledger')->insert($entry);
                    $success++;
                } catch (\Exception $e2) {
                    $this->command->warn("Failed: Journal Line ID {$entry['journal_line_id']}");
                }
            }
            $this->command->info("Individual insert: {$success} success");
        }
        
        $this->command->info('General ledger seeding completed!');
    }
}