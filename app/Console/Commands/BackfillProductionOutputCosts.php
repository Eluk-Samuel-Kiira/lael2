<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\ProductionOrderOutput;

class BackfillProductionOutputCosts extends Command
{
    protected $signature = 'production:backfill-output-costs
                            {--dry-run : Preview changes without writing}
                            {--tenant= : Restrict to a single tenant_id}';

    protected $description = 'Snapshot production_cost, selling_price and total_cost onto every production_order_outputs row.';

    public function handle(): int
    {
        $dryRun   = (bool) $this->option('dry-run');
        $tenantId = $this->option('tenant');

        $query = ProductionOrderOutput::query()
            ->with('productVariant') // eager load to avoid N+1
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->orderBy('id');

        $total     = (clone $query)->count();
        $updated   = 0;
        $skipped   = 0;
        $missing   = 0;

        $this->info("Processing {$total} output row(s)" . ($dryRun ? ' [DRY RUN]' : ''));

        $query->chunkById(200, function ($rows) use (&$updated, &$skipped, &$missing, $dryRun) {
            foreach ($rows as $output) {
                /** @var ProductionOrderOutput $output */
                $variant = $output->productVariant;

                if (!$variant) {
                    $missing++;
                    $this->warn("  ⚠ Output #{$output->id} — variant #{$output->product_variant_id} not found, skipping.");
                    continue;
                }

                $qty            = (float) $output->actual_quantity;
                $perUnitCost    = (float) ($variant->grand_total_cost_price ?? 0);
                $sellingPrice   = (float) ($variant->selling_price ?? 0);
                $totalCost      = round($qty * $perUnitCost, 2);

                // Skip no-op rows to keep the run idempotent & fast
                $sameCost    = abs((float)$output->production_cost - $perUnitCost) < 0.001;
                $sameSell    = abs((float)$output->selling_price   - $sellingPrice) < 0.001;
                $sameTotal   = abs((float)$output->total_cost      - $totalCost) < 0.001;

                if ($qty <= 0 && $perUnitCost <= 0) {
                    $skipped++;
                    continue;
                }
                if ($sameCost && $sameSell && $sameTotal) {
                    $skipped++;
                    continue;
                }

                if ($dryRun) {
                    $this->line(sprintf(
                        '  [dry] #%d  qty=%s  cost=%s→%s  sell=%s→%s  total=%s→%s',
                        $output->id, $qty,
                        $output->production_cost, $perUnitCost,
                        $output->selling_price,   $sellingPrice,
                        $output->total_cost,      $totalCost
                    ));
                    $updated++;
                    continue;
                }

                // Use DB update directly so accessors/mutators still run, but bypass events
                $output->production_cost = $perUnitCost;
                $output->selling_price   = $sellingPrice;
                $output->total_cost      = $totalCost;
                $output->saveQuietly();

                $updated++;

                if ($updated % 500 === 0) {
                    $this->info("  … {$updated} rows updated");
                }
            }
        });

        $this->newLine();
        $this->table(['Metric', 'Count'], [
            ['Rows scanned',   $total],
            ['Rows updated',   $updated],
            ['Rows skipped',   $skipped],
            ['Variant missing', $missing],
        ]);

        return self::SUCCESS;
    }
}