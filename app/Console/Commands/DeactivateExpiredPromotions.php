<?php
// app/Console/Commands/DeactivateExpiredPromotions.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\{ DB, Log };
use App\Models\Promotion;
use Carbon\Carbon;

class DeactivateExpiredPromotions extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'promotions:deactivate-expired 
                            {--tenant= : Specific tenant ID (optional)}
                            {--dry-run : Check without making changes}';

    /**
     * The console command description.
     */
    protected $description = 'Deactivate promotions that have passed their end date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🕒 Checking for expired promotions...');
        
        $startTime = microtime(true);
        $tenantId = $this->option('tenant');
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->warn('⚠️ DRY RUN MODE: No changes will be made');
        }
        
        // Build query for expired promotions
        $query = Promotion::where('is_active', true)
            ->whereNotNull('end_date')
            ->where('end_date', '<', Carbon::now());
        
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
            $this->info("📌 Filtering for tenant #{$tenantId}");
        }
        
        $expiredPromotions = $query->get();
        
        $count = $expiredPromotions->count();
        
        if ($count === 0) {
            $this->info('✅ No expired promotions found.');
            return Command::SUCCESS;
        }
        
        $this->info("📦 Found {$count} expired promotion(s) to deactivate.");
        
        // Display expired promotions
        $this->table(
            ['ID', 'Tenant ID', 'Name', 'End Date', 'Days Expired'],
            $expiredPromotions->map(function ($promo) {
                $daysExpired = Carbon::parse($promo->end_date)->diffInDays(Carbon::now(), false);
                return [
                    $promo->id,
                    $promo->tenant_id,
                    substr($promo->name, 0, 40),
                    $promo->end_date->format('Y-m-d H:i'),
                    abs($daysExpired) . ' days',
                ];
            })->toArray()
        );
        
        if (!$dryRun) {
            DB::beginTransaction();
            
            try {
                $deactivatedCount = Promotion::whereIn('id', $expiredPromotions->pluck('id'))
                    ->update([
                        'is_active' => false,
                        'updated_at' => now(),
                    ]);
                
                DB::commit();
                
                $duration = round((microtime(true) - $startTime) * 1000, 2);
                
                $this->newLine();
                $this->info("✅ Successfully deactivated {$deactivatedCount} expired promotions.");
                $this->line("⏱️  Time taken: {$duration} ms");
                
                // Log the action
                Log::info('Expired promotions deactivated', [
                    'count' => $deactivatedCount,
                    'execution_time_ms' => $duration,
                ]);
                
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("❌ Failed to deactivate promotions: " . $e->getMessage());
                Log::error('Failed to deactivate expired promotions', [
                    'error' => $e->getMessage(),
                ]);
                return Command::FAILURE;
            }
        } else {
            $this->newLine();
            $this->warn("📋 DRY RUN: Would deactivate {$count} promotion(s)");
        }
        
        return Command::SUCCESS;
    }
}