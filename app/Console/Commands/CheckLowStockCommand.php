<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\LowStockAlertService;
use Illuminate\Support\Facades\Log;

class CheckLowStockCommand extends Command
{
    protected $signature = 'stock:check-low 
                            {--tenant= : Check stock for specific tenant ID} 
                            {--dry-run : Run without sending actual alerts}';

    protected $description = 'Check low stock levels and send alerts to admins';

    protected $alertService;

    public function __construct(LowStockAlertService $alertService)
    {
        parent::__construct();
        $this->alertService = $alertService;
    }

    public function handle()
    {
        $this->info('📦 Checking Low Stock Levels...');
        $this->info('Time: ' . now()->setTimezone('Africa/Nairobi')->format('Y-m-d H:i:s') . ' EAT');
        $this->newLine();

        $tenantId = $this->option('tenant');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('⚠️ DRY RUN MODE - No alerts will be sent');
            $this->newLine();
        }

        try {
            // Get low stock items
            $tenants = $tenantId 
                ? \App\Models\Tenant::where('id', $tenantId)->get()
                : \App\Models\Tenant::all();

            foreach ($tenants as $tenant) {
                $this->info("Processing Tenant: {$tenant->name} (ID: {$tenant->id})");
                
                $isSingleShop = tenant_is_single_shop($tenant->id);
                $this->info("  Shop Type: " . ($isSingleShop ? 'Single Shop' : 'Multi-Shop'));
                
                // Get low stock items
                $lowStockItems = $this->alertService->getLowStockItems($tenant->id);
                
                if ($lowStockItems->isEmpty()) {
                    $this->info("  ✅ No low stock items found");
                    continue;
                }

                $this->info("  ⚠️ Found " . $lowStockItems->count() . " low stock items");
                
                // Show items
                foreach ($lowStockItems as $item) {
                    if ($isSingleShop) {
                        $this->line("    - {$item->name} (SKU: {$item->sku}): {$item->overal_quantity_at_hand} / {$item->low_stock_level}");
                    } else {
                        $variant = $item->variant;
                        $location = $item->itemLocation;
                        $this->line("    - " . ($variant->name ?? 'Unknown') . " (Location: " . ($location->name ?? 'Unassigned') . "): {$item->quantity_on_hand} / {$item->preferred_stock_level}");
                    }
                }

                if (!$dryRun) {
                    // Send alerts
                    $result = $this->alertService->checkAndAlert($tenant->id);
                    $this->info("  ✅ Alerts sent successfully");
                } else {
                    $this->warn("  ⏭️ Skipped sending alerts (dry-run mode)");
                }

                $this->newLine();
            }

            $this->info('✅ Low Stock Check Complete!');

        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            Log::error('Low stock check failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }

        return 0;
    }
}