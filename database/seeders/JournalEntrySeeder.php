<?php
// database/seeders/JournalEntrySeeder.php

namespace Database\Seeders;

use App\Models\JournalEntry;
use App\Models\Tenant;
use App\Models\AccountingPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class JournalEntrySeeder extends Seeder
{
    public function run(): void
    {
        // Skip entirely if ANY journal entries exist (optional - remove if you want to run seeder multiple times)
        if (JournalEntry::exists()) {
            $this->command->info('Journal entries already exist. Skipping seeder...');
            return;
        }
        
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            // Get periods for this tenant
            $periods = AccountingPeriod::where('tenant_id', $tenant->id)->get();
            
            if ($periods->isEmpty()) {
                $this->command->info("No accounting periods found for tenant {$tenant->id}. Skipping...");
                continue;
            }
            
            $this->command->info("Creating journal entries for tenant {$tenant->id}...");
            
            $createdCount = 0;
            $errorCount = 0;
            
            // Create sample journal entries
            for ($i = 0; $i < 10; $i++) {
                try {
                    // Start transaction for each entry to handle duplicates
                    DB::beginTransaction();
                    
                    // Create journal entry using factory
                    $journal = JournalEntry::factory()->make([
                        'tenant_id' => $tenant->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    
                    // Check for duplicate based on your unique constraints
                    // Adjust the duplicate check based on your business logic
                    $duplicateCheck = JournalEntry::where('tenant_id', $journal->tenant_id)
                        ->where('entry_number', $journal->entry_number)
                        ->exists();
                    
                    if ($duplicateCheck) {
                        $errorCount++;
                        $this->command->warn("Duplicate entry found for tenant {$tenant->id}, entry #{$journal->entry_number}. Skipping...");
                        DB::rollBack();
                        continue;
                    }
                    
                    // Save the journal entry
                    $journal->save();
                    
                    // Assign a random period that includes the entry date
                    $matchingPeriods = $periods->filter(function ($period) use ($journal) {
                        return $journal->entry_date >= $period->start_date && 
                               $journal->entry_date <= $period->end_date;
                    });
                    
                    if ($matchingPeriods->isNotEmpty()) {
                        $journal->period_id = $matchingPeriods->random()->id;
                    } else {
                        // Find closest period
                        $closestPeriod = $periods->sortBy(function ($period) use ($journal) {
                            return abs(strtotime($period->start_date) - strtotime($journal->entry_date));
                        })->first();
                        
                        $journal->period_id = $closestPeriod->id;
                    }
                    
                    // Update with period_id
                    $journal->save();
                    
                    DB::commit();
                    $createdCount++;
                    
                } catch (QueryException $e) {
                    DB::rollBack();
                    $errorCount++;
                    
                    // Check if it's a duplicate entry error (MySQL error code 1062)
                    if ($e->getCode() == 23000 || str_contains($e->getMessage(), '1062 Duplicate entry')) {
                        $this->command->warn("Duplicate entry error for tenant {$tenant->id}: " . $e->getMessage());
                    } else {
                        $this->command->error("Database error for tenant {$tenant->id}: " . $e->getMessage());
                    }
                    
                } catch (\Exception $e) {
                    DB::rollBack();
                    $errorCount++;
                    $this->command->error("General error for tenant {$tenant->id}: " . $e->getMessage());
                }
            }
            
            $this->command->info("Tenant {$tenant->id}: Created {$createdCount} entries, {$errorCount} errors/skipped.");
        }
    }
}