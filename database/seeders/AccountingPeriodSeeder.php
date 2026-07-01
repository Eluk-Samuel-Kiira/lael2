<?php
// database/seeders/AccountingPeriodSeeder.php

namespace Database\Seeders;

use App\Models\AccountingPeriod;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AccountingPeriodSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            // $this->command->info("Processing accounting periods for Tenant ID: {$tenant->id}");
            
            // Get existing periods for this tenant
            $existingPeriods = AccountingPeriod::where('tenant_id', $tenant->id)
                ->get()
                ->keyBy(function ($period) {
                    return $period->start_date->format('Y-m') . '_' . $period->end_date->format('Y-m');
                });
            
            $startDate = Carbon::now()->subMonths(11)->startOfMonth();
            
            for ($i = 0; $i < 12; $i++) {
                $periodStart = $startDate->copy()->addMonths($i);
                $periodEnd = $periodStart->copy()->endOfMonth();
                
                $key = $periodStart->format('Y-m') . '_' . $periodEnd->format('Y-m');
                
                // Check if period already exists
                if (isset($existingPeriods[$key])) {
                    // Period exists, update status if needed
                    $period = $existingPeriods[$key];
                    $newStatus = $periodEnd->isPast() ? 'closed' : 'open';
                    
                    if ($period->status !== $newStatus) {
                        $period->update(['status' => $newStatus]);
                        // $this->command->info("Updated period {$period->period_name} status to {$newStatus}");
                    }
                } else {
                    // Create new period
                    $status = $periodEnd->isPast() ? 'closed' : 'open';
                    
                    if ($i === 11) {
                        $status = 'open'; // Current month
                    }
                    
                    AccountingPeriod::create([
                        'tenant_id' => $tenant->id,
                        'period_name' => $periodStart->format('F Y'),
                        'start_date' => $periodStart,
                        'end_date' => $periodEnd,
                        'status' => $status,
                        'is_fiscal_year_end' => $periodStart->month === 12,
                    ]);
                    
                    // $this->command->info("Created period: {$periodStart->format('F Y')}");
                }
            }
        }
    }
}