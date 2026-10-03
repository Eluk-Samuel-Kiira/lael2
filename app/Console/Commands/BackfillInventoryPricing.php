<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

class BackfillInventoryPricing extends Command
{
    protected $signature   = 'inventory:backfill-pricing';
    protected $description = 'Seed pricing on every inventory strategy table from its parent product_variant, only where null or 0.';

    public function handle(): int
    {
        $toBase = fn ($value) => $value === null ? null : to_base_currency((float) $value);

        $variantCost = function (ProductVariant $v): float {
            $grand = (float) ($v->grand_total_cost_price ?? 0);
            if ($grand > 0) return $grand;

            return (float) ($v->supplier_cost_price ?? 0)
                 + (float) ($v->total_shipping_cost ?? 0)
                 + (float) ($v->ura_taxes_applied   ?? 0)
                 + (float) ($v->additional_expenses ?? 0);
        };

        $variantSelling = function (ProductVariant $v): float {
            return (float) ($v->discount_selling_price ?? $v->selling_price ?? 0);
        };

        // ─────────────────────────────────────────────────
        // 1. inventory_items
        // ─────────────────────────────────────────────────
        $count = 0;
        DB::table('inventory_items')
            ->select('id', 'variant_id',
                     'grand_total_cost_price', 'selling_price', 'discount_selling_price')
            ->orderBy('id')
            ->chunk(500, function ($rows) use (&$count, $variantCost, $variantSelling, $toBase) {
                $variants = ProductVariant::whereIn('id',
                    $rows->pluck('variant_id')->filter()->unique()
                )->get()->keyBy('id');

                foreach ($rows as $row) {
                    $v = $variants->get($row->variant_id);
                    if (! $v) continue;

                    $updates = [];
                    if (empty($row->grand_total_cost_price) || (float) $row->grand_total_cost_price === 0.0) {
                        $updates['grand_total_cost_price'] = $toBase($variantCost($v));
                    }
                    if (empty($row->selling_price) || (float) $row->selling_price === 0.0) {
                        $updates['selling_price'] = $toBase($v->selling_price);
                    }
                    if (empty($row->discount_selling_price) || (float) $row->discount_selling_price === 0.0) {
                        $updates['discount_selling_price'] = $toBase($variantSelling($v));
                    }
                    if ($updates) {
                        DB::table('inventory_items')->where('id', $row->id)->update($updates);
                        $count++;
                    }
                }
            });
        $this->info("inventory_items: {$count} rows updated");

        // ─────────────────────────────────────────────────
        // 2. purchase_receipt_items (batches)
        // ─────────────────────────────────────────────────
        $count = 0;
        DB::table('purchase_receipt_items')
            ->join('purchase_order_items',
                'purchase_receipt_items.purchase_order_item_id', '=', 'purchase_order_items.id')
            ->select(
                'purchase_receipt_items.id',
                'purchase_receipt_items.grand_total_cost_price',
                'purchase_receipt_items.unit_selling_price',
                'purchase_receipt_items.discount_selling_price',
                'purchase_receipt_items.pricing_source',
                'purchase_order_items.product_variant_id'
            )
            ->orderBy('purchase_receipt_items.id')
            ->chunk(500, function ($rows) use (&$count, $variantCost, $variantSelling, $toBase) {
                $variants = ProductVariant::whereIn('id',
                    $rows->pluck('product_variant_id')->filter()->unique()
                )->get()->keyBy('id');

                foreach ($rows as $row) {
                    $v = $variants->get($row->product_variant_id);
                    if (! $v) continue;

                    $updates = [];
                    if (empty($row->grand_total_cost_price) || (float) $row->grand_total_cost_price === 0.0) {
                        $updates['grand_total_cost_price'] = $toBase($variantCost($v));
                    }
                    if (empty($row->unit_selling_price) || (float) $row->unit_selling_price === 0.0) {
                        $updates['unit_selling_price'] = $toBase($v->selling_price);
                    }
                    if (empty($row->discount_selling_price) || (float) $row->discount_selling_price === 0.0) {
                        $updates['discount_selling_price'] = $toBase($variantSelling($v));
                    }
                    if (empty($row->pricing_source)) {
                        $updates['pricing_source'] = 'variant';
                    }
                    if ($updates) {
                        DB::table('purchase_receipt_items')->where('id', $row->id)->update($updates);
                        $count++;
                    }
                }
            });
        $this->info("purchase_receipt_items: {$count} rows updated");

        // ─────────────────────────────────────────────────
        // 3. serial_numbers
        // ─────────────────────────────────────────────────
        $count = 0;
        DB::table('serial_numbers')
            ->select('id', 'variant_id',
                     'grand_total_cost_price', 'selling_price', 'discount_selling_price', 'pricing_source')
            ->orderBy('id')
            ->chunk(500, function ($rows) use (&$count, $variantCost, $variantSelling, $toBase) {
                $variants = ProductVariant::whereIn('id',
                    $rows->pluck('variant_id')->filter()->unique()
                )->get()->keyBy('id');

                foreach ($rows as $row) {
                    $v = $variants->get($row->variant_id);
                    if (! $v) continue;

                    $updates = [];
                    if (empty($row->grand_total_cost_price) || (float) $row->grand_total_cost_price === 0.0) {
                        $updates['grand_total_cost_price'] = $toBase($variantCost($v));
                    }
                    if (empty($row->selling_price) || (float) $row->selling_price === 0.0) {
                        $updates['selling_price'] = $toBase($v->selling_price);
                    }
                    if (empty($row->discount_selling_price) || (float) $row->discount_selling_price === 0.0) {
                        $updates['discount_selling_price'] = $toBase($variantSelling($v));
                    }
                    if (empty($row->pricing_source)) {
                        $updates['pricing_source'] = 'variant';
                    }
                    if ($updates) {
                        DB::table('serial_numbers')->where('id', $row->id)->update($updates);
                        $count++;
                    }
                }
            });
        $this->info("serial_numbers: {$count} rows updated");

        // ─────────────────────────────────────────────────
        // 4. recipe_ingredients
        // ─────────────────────────────────────────────────
        $count = 0;
        DB::table('recipe_ingredients')
            ->select('id', 'ingredient_variant_id', 'quantity_required',
                     'unit_cost', 'total_cost', 'unit_selling_price', 'pricing_source')
            ->orderBy('id')
            ->chunk(500, function ($rows) use (&$count, $variantCost, $variantSelling, $toBase) {
                $variants = ProductVariant::whereIn('id',
                    $rows->pluck('ingredient_variant_id')->filter()->unique()
                )->get()->keyBy('id');

                foreach ($rows as $row) {
                    $v = $variants->get($row->ingredient_variant_id);
                    if (! $v) continue;

                    $updates = [];
                    if (empty($row->unit_cost) || (float) $row->unit_cost === 0.0) {
                        $unit = $variantCost($v);
                        $updates['unit_cost']  = $toBase($unit);
                        $updates['total_cost'] = $toBase($unit * (float) $row->quantity_required);
                    }
                    if (empty($row->unit_selling_price) || (float) $row->unit_selling_price === 0.0) {
                        $updates['unit_selling_price'] = $toBase($variantSelling($v));
                    }
                    if (empty($row->pricing_source)) {
                        $updates['pricing_source'] = 'variant';
                    }
                    if ($updates) {
                        DB::table('recipe_ingredients')->where('id', $row->id)->update($updates);
                        $count++;
                    }
                }
            });
        $this->info("recipe_ingredients: {$count} rows updated");

        // ─────────────────────────────────────────────────
        // 5. recipes (header) — recompute from ingredients
        // ─────────────────────────────────────────────────
        $count = 0;
        DB::table('recipes')
            ->select('id', 'product_id', 'unit_cost', 'unit_selling_price')
            ->orderBy('id')
            ->chunk(500, function ($rows) use (&$count, $toBase) {
                foreach ($rows as $row) {
                    if (! empty($row->unit_cost) && (float) $row->unit_cost !== 0.0) continue;

                    $sumRaw = DB::table('recipe_ingredients')
                        ->where('recipe_id', $row->id)
                        ->sum('total_cost');

                    if (! $sumRaw) continue;

                    $updates = [
                        'unit_cost'      => $sumRaw,
                        'last_costed_at' => now(),
                    ];

                    if (empty($row->unit_selling_price)) {
                        $variant = ProductVariant::where('product_id', $row->product_id)
                            ->where('is_active', 1)
                            ->orderByDesc('grand_total_cost_price')
                            ->first();

                        if ($variant) {
                            $updates['unit_selling_price'] = $toBase(
                                $variant->discount_selling_price ?? $variant->selling_price ?? 0
                            );
                        }
                    }

                    DB::table('recipes')->where('id', $row->id)->update($updates);
                    $count++;
                }
            });
        $this->info("recipes: {$count} rows updated");

        $this->newLine();
        $this->info('✅ Backfill complete.');
        return self::SUCCESS;
    }
}