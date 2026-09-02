<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ReportNotificationService;
use App\Models\Tenant;
use App\Models\Location;
use Illuminate\Support\Facades\Log;

class SendMonthlyReportCommand extends Command
{
    protected $signature = 'report:send:monthly 
                            {--tenant= : Send report for specific tenant ID} 
                            {--location= : Send report for specific location ID} 
                            {--channels=email,whatsapp : Channels to send (email,whatsapp)}';

    protected $description = 'Send monthly summary report to admin users at 8pm EAT';

    protected $reportService;

    public function __construct(ReportNotificationService $reportService)
    {
        parent::__construct();
        $this->reportService = $reportService;
    }

    public function handle()
    {
        $this->info('📊 Starting Monthly Report Generation...');
        $this->info('Time: ' . now()->setTimezone('Africa/Nairobi')->format('Y-m-d H:i:s') . ' EAT');

        $tenantId = $this->option('tenant');
        $locationId = $this->option('location');
        $channels = explode(',', $this->option('channels'));

        // Get tenants to process
        $tenants = $tenantId 
            ? Tenant::where('id', $tenantId)->get()
            : Tenant::all();

        $totalSent = 0;
        $errors = [];

        foreach ($tenants as $tenant) {
            $this->info("Processing Tenant: {$tenant->name} (ID: {$tenant->id})");

            try {
                if ($locationId) {
                    $results = $this->reportService->sendReport('monthly', $tenant->id, $locationId, $channels);
                    $totalSent += count($results);
                    $this->info("  ✅ Sent to " . count($results) . " admins for location ID: {$locationId}");
                } else {
                    $locations = Location::where('tenant_id', $tenant->id)->get();
                    
                    if ($locations->isEmpty()) {
                        $results = $this->reportService->sendReport('monthly', $tenant->id, null, $channels);
                        $totalSent += count($results);
                        $this->info("  ✅ Sent tenant-wide monthly report to " . count($results) . " admins");
                    } else {
                        foreach ($locations as $location) {
                            $results = $this->reportService->sendReport('monthly', $tenant->id, $location->id, $channels);
                            $totalSent += count($results);
                            $this->info("  ✅ Sent monthly report for location: {$location->name} to " . count($results) . " admins");
                        }
                    }
                }
            } catch (\Exception $e) {
                $errors[] = "Tenant {$tenant->id}: " . $e->getMessage();
                $this->error("  ❌ Error: " . $e->getMessage());
                Log::error("Monthly report failed for tenant {$tenant->id}: " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info("✅ Monthly Report Generation Complete!");
        $this->info("📨 Total reports sent: {$totalSent}");

        if (!empty($errors)) {
            $this->warn("⚠️ Errors encountered:");
            foreach ($errors as $error) {
                $this->warn("  - {$error}");
            }
        }

        return 0;
    }
}