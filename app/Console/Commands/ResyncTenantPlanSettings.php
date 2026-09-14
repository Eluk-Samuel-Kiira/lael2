<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\BillingPlan;
use App\Models\TenantSetting;
use Illuminate\Console\Command;

class ResyncTenantPlanSettings extends Command
{
    protected $signature = 'tenants:resync-plan {tenant_id? : Specific tenant ID, or omit for all tenants}';
    protected $description = 'Re-apply each tenant\'s current billing plan to backfill missing tenant_settings (e.g. reports/production/restaurant categories)';

    public function handle()
    {
        $tenants = $this->argument('tenant_id')
            ? Tenant::where('id', $this->argument('tenant_id'))->get()
            : Tenant::all();

        if ($tenants->isEmpty()) {
            $this->error('No matching tenant(s) found.');
            return 1;
        }

        foreach ($tenants as $tenant) {
            $planCode = TenantSetting::where('tenant_id', $tenant->id)
                ->where('setting_key', 'billing_plan')
                ->value('setting_value');

            if (!$planCode) {
                $this->warn("Tenant {$tenant->id} ({$tenant->name}): no billing_plan setting found, skipping.");
                continue;
            }

            $plan = BillingPlan::where('plan_code', $planCode)->first();

            if (!$plan) {
                $this->warn("Tenant {$tenant->id} ({$tenant->name}): plan '{$planCode}' not found, skipping.");
                continue;
            }

            $plan->applyToTenant($tenant->id);
            $this->info("Tenant {$tenant->id} ({$tenant->name}): re-applied plan '{$planCode}' — reports/production/restaurant settings backfilled.");
        }

        return 0;
    }
}