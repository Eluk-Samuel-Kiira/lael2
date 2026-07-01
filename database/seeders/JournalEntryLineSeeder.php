<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class JournalEntryLineSeeder extends Seeder
{
    public function run(): void
    {
        // Skip if journal entry lines already exist
        if (DB::table('journal_entry_lines')->exists()) {
            $this->command->info('Journal entry lines already exist. Skipping seeder...');
            return;
        }

        $this->command->info('=== DEBUG: Checking what account IDs actually exist ===');
        
        // First, let's see what account IDs we actually have
        $accounts = DB::table('chart_of_accounts')
            ->select('id', 'account_code', 'account_name', 'tenant_id')
            ->orderBy('tenant_id')
            ->orderBy('id')
            ->limit(20)
            ->get();
            
        $this->command->info("Found " . $accounts->count() . " accounts in chart_of_accounts:");
        foreach ($accounts as $account) {
            $this->command->info("  - ID: {$account->id}, Code: {$account->account_code}, Name: {$account->account_name}, Tenant: {$account->tenant_id}");
        }
        
        $journalEntries = DB::table('journal_entries')
            ->select('id', 'tenant_id')
            ->orderBy('id')
            ->get();

        $this->command->info("Found " . $journalEntries->count() . " journal entries");
        
        if ($journalEntries->isEmpty()) {
            $this->command->warn('No journal entries found.');
            return;
        }

        $entries = [];
        $now = Carbon::now();

        foreach ($journalEntries as $journal) {
            $tenantId = $journal->tenant_id;
            
            // Get accounts for this tenant
            $tenantAccounts = DB::table('chart_of_accounts')
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->orderBy('id')
                ->get(['id', 'account_type']);
            
            if ($tenantAccounts->count() < 2) {
                $this->command->warn("Tenant {$tenantId} only has {$tenantAccounts->count()} accounts. Need at least 2.");
                continue;
            }
            
            // Use the first 2 accounts for this tenant
            $account1 = $tenantAccounts[0];
            $account2 = $tenantAccounts[1];
            
            $this->command->info("Using accounts for tenant {$tenantId}: {$account1->id} and {$account2->id}");
            
            // Generate random amount
            $amount = rand(100, 10000) + (rand(0, 99) / 100);
            
            // Create debit/credit pair
            $entries[] = [
                'journal_id' => $journal->id,
                'account_id' => $account1->id,
                'line_number' => 1,
                'description' => 'Debit entry',
                'debit_amount' => $amount,
                'credit_amount' => 0,
                'reference_type' => 'journal',
                'reference_id' => $journal->id,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            
            $entries[] = [
                'journal_id' => $journal->id,
                'account_id' => $account2->id,
                'line_number' => 2,
                'description' => 'Credit entry',
                'debit_amount' => 0,
                'credit_amount' => $amount,
                'reference_type' => 'journal',
                'reference_id' => $journal->id,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $this->command->info("Ready to insert " . count($entries) . " journal entry lines...");

        // Insert in very small batches to debug
        $batchSize = 10;
        $totalBatches = ceil(count($entries) / $batchSize);
        
        for ($i = 0; $i < count($entries); $i += $batchSize) {
            $batch = array_slice($entries, $i, $batchSize);
            $batchNumber = ($i / $batchSize) + 1;
            
            $this->command->info("Inserting batch {$batchNumber}/{$totalBatches}...");
            
            try {
                DB::table('journal_entry_lines')->insert($batch);
                $this->command->info("✓ Batch {$batchNumber} inserted successfully");
            } catch (\Exception $e) {
                $this->command->error("✗ Error in batch {$batchNumber}: " . $e->getMessage());
                
                // Try one by one to see which specific entry fails
                $this->command->info("Trying entries one by one...");
                $successCount = 0;
                $errorCount = 0;
                
                foreach ($batch as $entry) {
                    try {
                        // Verify the account exists first
                        $accountExists = DB::table('chart_of_accounts')
                            ->where('id', $entry['account_id'])
                            ->exists();
                            
                        if (!$accountExists) {
                            $this->command->warn("  Account ID {$entry['account_id']} doesn't exist!");
                            $errorCount++;
                            continue;
                        }
                        
                        DB::table('journal_entry_lines')->insert($entry);
                        $successCount++;
                    } catch (\Exception $e2) {
                        $errorCount++;
                        $this->command->warn("  Failed entry - Journal: {$entry['journal_id']}, Account: {$entry['account_id']}, Error: " . $e2->getMessage());
                    }
                }
                $this->command->info("  Individual results: {$successCount} success, {$errorCount} errors");
            }
        }

        $this->command->info('Journal entry line seeding completed!');
        
        // Show what we created
        $createdCount = DB::table('journal_entry_lines')->count();
        $this->command->info("Total journal entry lines created: {$createdCount}");
    }
}