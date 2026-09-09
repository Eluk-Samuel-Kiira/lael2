<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Models\TenantSetting;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\TenantBillingReminderMail;
use App\Mail\TenantBillingExpiredMail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckTenantBilling extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:check {--force : Force run even if already run today}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check tenant billing cycles and update statuses';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking tenant billing cycles...');
        $startTime = microtime(true);
        $processed = 0;
        $expired = 0;
        $reminded = 0;

        try {
            // Get all tenants with billing settings
            $tenants = Tenant::with(['settings' => function($query) {
                $query->where('category', 'billing');
            }])->get();

            foreach ($tenants as $tenant) {
                $processed++;
                $this->info("Processing tenant: {$tenant->name} (ID: {$tenant->id})");

                // Get billing settings
                $billingSettings = $tenant->settings->keyBy('setting_key');
                
                // Skip if no billing settings
                if ($billingSettings->isEmpty()) {
                    $this->warn("No billing settings found for tenant: {$tenant->name}");
                    continue;
                }

                // Check if lifetime license
                $isLifetime = isset($billingSettings['is_lifetime']) && (bool)$billingSettings['is_lifetime']->setting_value;
                
                if ($isLifetime) {
                    $this->info("Tenant {$tenant->name} has lifetime license - skipping");
                    continue;
                }

                // Get subscription status
                $statusSetting = $billingSettings['subscription_status'] ?? null;
                $currentStatus = $statusSetting ? $statusSetting->setting_value : 'inactive';

                // Skip if already inactive/expired/suspended
                if (in_array($currentStatus, ['inactive', 'expired', 'suspended'])) {
                    $this->info("Tenant {$tenant->name} is already {$currentStatus} - skipping");
                    continue;
                }

                // Get next billing date
                $nextBillingDateSetting = $billingSettings['next_billing_date'] ?? null;
                $nextBillingDate = $nextBillingDateSetting && $nextBillingDateSetting->setting_value 
                    ? Carbon::parse($nextBillingDateSetting->setting_value) 
                    : null;

                // Get trial ends at
                $trialEndsAtSetting = $billingSettings['trial_ends_at'] ?? null;
                $trialEndsAt = $trialEndsAtSetting && $trialEndsAtSetting->setting_value 
                    ? Carbon::parse($trialEndsAtSetting->setting_value) 
                    : null;

                // Get billing cycle days
                $cycleDaysSetting = $billingSettings['billing_cycle_days'] ?? null;
                $cycleDays = $cycleDaysSetting ? (int)$cycleDaysSetting->setting_value : 30;

                $now = Carbon::now();
                $shouldExpire = false;
                $expireReason = '';

                // Check if trial has expired
                if ($trialEndsAt && $now->greaterThan($trialEndsAt)) {
                    // Check if we have a next billing date (meaning trial ended and subscription started)
                    if (!$nextBillingDate) {
                        $shouldExpire = true;
                        $expireReason = 'Trial period ended and no subscription started';
                    }
                }

                // Check if next billing date is passed
                if ($nextBillingDate && $now->greaterThan($nextBillingDate)) {
                    // Check if grace period has passed (7 days)
                    $gracePeriodEnd = $nextBillingDate->copy()->addDays(7);
                    if ($now->greaterThan($gracePeriodEnd)) {
                        $shouldExpire = true;
                        $expireReason = 'Billing cycle ended and grace period expired';
                    } else {
                        // Within grace period - send reminder
                        $this->sendExpiryReminder($tenant, $nextBillingDate);
                        $reminded++;
                    }
                }

                // Check if we need to remind about upcoming expiry (7 days before)
                if ($nextBillingDate && !$shouldExpire) {
                    $daysUntilExpiry = $now->diffInDays($nextBillingDate);
                    if ($daysUntilExpiry <= 7 && $daysUntilExpiry >= 0) {
                        $this->sendExpiryReminder($tenant, $nextBillingDate);
                        $reminded++;
                    }
                }

                // If should expire, handle the expiration
                if ($shouldExpire) {
                    $this->expireTenant($tenant, $expireReason);
                    $expired++;
                }

                // Update next billing date if cycle is completed
                if ($nextBillingDate && $now->greaterThan($nextBillingDate) && !$shouldExpire) {
                    // Calculate next billing date
                    $newBillingDate = $nextBillingDate->copy()->addDays($cycleDays);
                    
                    TenantSetting::updateOrCreate(
                        [
                            'tenant_id' => $tenant->id,
                            'setting_key' => 'next_billing_date',
                        ],
                        [
                            'setting_value' => $newBillingDate->toDateTimeString(),
                            'data_type' => 'datetime',
                            'category' => 'billing',
                        ]
                    );
                    
                    $this->info("Updated next billing date for {$tenant->name} to {$newBillingDate->toDateString()}");
                }
            }

            $duration = round(microtime(true) - $startTime, 2);
            $this->info("Billing check completed in {$duration} seconds");
            $this->info("Processed: {$processed}, Expired: {$expired}, Reminders sent: {$reminded}");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Error checking billing: ' . $e->getMessage());
            Log::error('Billing check failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Expire a tenant
     */
    private function expireTenant(Tenant $tenant, string $reason)
    {
        try {
            DB::beginTransaction();

            // Update tenant status
            $tenant->status = 'inactive';
            $tenant->save();

            // Update subscription status
            TenantSetting::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'setting_key' => 'subscription_status',
                ],
                [
                    'setting_value' => 'expired',
                    'data_type' => 'string',
                    'category' => 'billing',
                ]
            );

            DB::commit();

            // Send expiration email to admin users
            $this->sendExpirationEmail($tenant, $reason);

            Log::info("Tenant {$tenant->name} expired", [
                'tenant_id' => $tenant->id,
                'reason' => $reason
            ]);

            $this->info("Tenant {$tenant->name} has been expired. Reason: {$reason}");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to expire tenant {$tenant->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send expiry reminder email
     */
    private function sendExpiryReminder(Tenant $tenant, Carbon $expiryDate)
    {
        try {
            $adminUsers = User::role('admin')
                ->where('tenant_id', $tenant->id)
                ->get();

            if ($adminUsers->isEmpty()) {
                $this->warn("No admin users found for tenant {$tenant->name}");
                return;
            }

            $daysUntilExpiry = Carbon::now()->diffInDays($expiryDate);
            
            foreach ($adminUsers as $admin) {
                Mail::to($admin->email)->send(new TenantBillingReminderMail(
                    $tenant,
                    $admin,
                    $expiryDate,
                    $daysUntilExpiry
                ));
                $this->info("Reminder sent to {$admin->email} for tenant {$tenant->name}");
            }

        } catch (\Exception $e) {
            Log::error("Failed to send expiry reminder for tenant {$tenant->id}: " . $e->getMessage());
        }
    }

    /**
     * Send expiration email
     */
    private function sendExpirationEmail(Tenant $tenant, string $reason)
    {
        try {
            $adminUsers = User::role('admin')
                ->where('tenant_id', $tenant->id)
                ->get();

            if ($adminUsers->isEmpty()) {
                $this->warn("No admin users found for tenant {$tenant->name}");
                return;
            }

            foreach ($adminUsers as $admin) {
                Mail::to($admin->email)->send(new TenantBillingExpiredMail(
                    $tenant,
                    $admin,
                    $reason
                ));
                $this->info("Expiration email sent to {$admin->email} for tenant {$tenant->name}");
            }

        } catch (\Exception $e) {
            Log::error("Failed to send expiration email for tenant {$tenant->id}: " . $e->getMessage());
        }
    }
}