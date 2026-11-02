<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\InventoryItems;
use App\Models\InventoryTransactions;
use App\Models\InventoryAdjustments;
use App\Models\SingleShopInventoryLog;
use Carbon\Carbon;

class PricingResolverService
{
    /**
     * Resolve unit cost and selling price for an order line item.
     *
     * Cascade (multi-shop):
     *   1. Frozen cost on the inventory log (InventoryTransactions → InventoryAdjustments)
     *   2. InventoryItems override — ONLY when has_custom_pricing = 1
     *   3. ProductVariant overall price
     *   4. Recompute from variant components
     *
     * Cascade (single-shop):
     *   1. Frozen cost on SingleShopInventoryLog
     *   2. ProductVariant overall price
     *   3. Recompute from variant components
     *
     * @return array{unit_cost: float, unit_sell: float, source: string}
     */
    // app/Services/PricingResolverService.php

    public function resolveLinePricing(OrderItem $item): array
    {
        $order   = $item->order;
        $variant = $item->productVariant;

        if (! $variant) {
            return ['unit_cost' => 0.0, 'unit_sell' => 0.0, 'source' => 'unknown'];
        }

        // ── Re-fetch the full model if columns are missing ───────
        // If someone forgot to select the pricing columns on the
        // eager-load, reload the model so the accessors have real
        // values to work with. Cheap, one query, only when needed.
        if ($this->variantMissingPricingColumns($variant)) {
            $variant = ProductVariant::find($variant->id) ?? $variant;
        }

        $tenantId     = $order->tenant_id ?? $variant->tenant_id;
        $isSingleShop = tenant_is_single_shop($tenantId);

        // ── 1. Frozen cost from the log ─────────────────────────
        // Only accept the log when it carries a positive cost. A
        // log row with unit_cost_price = 0 is treated as "no log"
        // so the variant fallback can fire.
        $log = null;
        if (! $isSingleShop) {
            $log = $this->resolveMultiShopLog($order, $variant);
        } else {
            $log = SingleShopInventoryLog::where('variant_id', $variant->id)
                ->where('order_id', $order->id)
                ->latest('id')
                ->first();
        }

        if ($log && (float) $log->unit_cost_price > 0) {
            return [
                'unit_cost' => (float) $log->unit_cost_price,
                'unit_sell' => (float) ($log->unit_selling_price ?? 0),
                'source'    => 'log',
            ];
        }

        // ── 2. InventoryItems override (multi-shop, custom only) ─
        if (! $isSingleShop && $order->location_id && $order->department_id) {
            $inv = InventoryItems::where('tenant_id', $variant->tenant_id)
                ->where('variant_id', $variant->id)
                ->where('location_id', $order->location_id)
                ->where('department_id', $order->department_id)
                ->where('has_custom_pricing', 1)
                ->first();

            if ($inv && (float) ($inv->grand_total_cost_price ?? 0) > 0) {
                return [
                    'unit_cost' => (float) $inv->grand_total_cost_price,
                    'unit_sell' => (float) ($inv->discount_selling_price ?? $inv->selling_price ?? 0),
                    'source'    => 'item',
                ];
            }
        }

        // ── 3. Variant grand_total_cost_price ────────────────────
        $grand = (float) ($variant->grand_total_cost_price ?? 0);
        if ($grand > 0) {
            return [
                'unit_cost' => $grand,
                'unit_sell' => (float) ($variant->discount_selling_price ?? $variant->selling_price ?? 0),
                'source'    => 'variant',
            ];
        }

        // ── 4. Recompute from variant components ─────────────────
        $recomputed = (float) ($variant->supplier_cost_price ?? 0)
                    + (float) ($variant->total_shipping_cost ?? 0)
                    + (float) ($variant->ura_taxes_applied   ?? 0)
                    + (float) ($variant->additional_expenses ?? 0);

        return [
            'unit_cost' => $recomputed,
            'unit_sell' => (float) ($variant->discount_selling_price ?? $variant->selling_price ?? 0),
            'source'    => 'variant',
        ];
    }

    /**
     * Detect an eagerly-loaded variant that is missing the columns
     * the pricing resolver and accessors need.
     */
    protected function variantMissingPricingColumns(ProductVariant $variant): bool
    {
        $needed = [
            'grand_total_cost_price',
            'selling_price',
            'discount_selling_price',
            'supplier_cost_price',
            'total_shipping_cost',
            'ura_taxes_applied',
            'additional_expenses',
        ];

        foreach ($needed as $col) {
            if (! array_key_exists($col, $variant->getAttributes())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve the log row for a multi-shop order.
     */
    protected function resolveMultiShopLog(Order $order, ProductVariant $variant): ?object
    {
        // Try InventoryTransactions (created per sale)
        $txn = InventoryTransactions::where('reference_type', 'order')
            ->where('reference_id', $order->id)
            ->whereHas('InventoryItems', function ($q) use ($variant) {
                $q->where('variant_id', $variant->id);
            })
            ->latest('id')
            ->first();

        if ($txn && ((float) $txn->unit_cost_price > 0 || (float) $txn->total_cost_value !== 0)) {
            return $txn;
        }

        // Fallback: InventoryAdjustments
        $adj = InventoryAdjustments::where('inventory_id', function ($q) use ($order, $variant) {
                $q->select('id')
                  ->from('inventory_items')
                  ->where('variant_id', $variant->id)
                  ->where('location_id', $order->location_id)
                  ->where('department_id', $order->department_id)
                  ->limit(1);
            })
            ->where('reason', 'order_sale')
            ->where('notes', 'like', "%Order #{$order->order_number}%")
            ->latest('id')
            ->first();

        if ($adj) {
            return $adj;
        }

        return null;
    }

    /**
     * Bulk resolve pricing for a collection of OrderItems.
     *
     * @param  \Illuminate\Support\Collection<OrderItem>  $items
     * @return array<int, array{unit_cost: float, unit_sell: float, source: string}>
     */
    public function resolveBatchPricing($items): array
    {
        $results = [];

        $grouped = $items->groupBy(function ($item) {
            return $item->order->tenant_id ?? $item->productVariant->tenant_id;
        });

        foreach ($grouped as $tenantId => $tenantItems) {
            $isSingleShop = tenant_is_single_shop($tenantId);
            $variantIds   = $tenantItems->pluck('variant_id')->unique()->filter()->toArray();
            $orderIds     = $tenantItems->pluck('order_id')->unique()->filter()->toArray();

            if ($isSingleShop) {
                $logs = SingleShopInventoryLog::where('tenant_id', $tenantId)
                    ->whereIn('variant_id', $variantIds)
                    ->whereIn('order_id', $orderIds)
                    ->get()
                    ->keyBy(fn($l) => "{$l->variant_id}|{$l->order_id}");

                // Pre-load variant + inventory fallbacks once
                $variants   = ProductVariant::whereIn('id', $variantIds)->get()->keyBy('id');
                $inventory  = InventoryItems::where('tenant_id', $tenantId)
                    ->whereIn('variant_id', $variantIds)
                    ->where('has_custom_pricing', 1)
                    ->get()
                    ->keyBy(fn($i) => "{$i->variant_id}|{$i->location_id}|{$i->department_id}");

                foreach ($tenantItems as $item) {
                    $key = "{$item->variant_id}|{$item->order_id}";
                    $log = $logs->get($key);

                    if ($log && (float) $log->unit_cost_price > 0) {
                        $results[$item->id] = [
                            'unit_cost' => (float) $log->unit_cost_price,
                            'unit_sell' => (float) ($log->unit_selling_price ?? 0),
                            'source'    => 'log',
                        ];
                        continue;
                    }

                    $results[$item->id] = $this->fallbackPricingFromCache(
                        $item, $variants, $inventory, $isSingleShop
                    );
                }
            } else {
                // Pre-load multi-shop transactions keyed by order|variant
                $txns = InventoryTransactions::where('reference_type', 'order')
                    ->whereIn('reference_id', $orderIds)
                    ->with('InventoryItems')
                    ->get()
                    ->keyBy(fn($t) => "{$t->reference_id}|" . optional($t->InventoryItems)->variant_id);

                $variants   = ProductVariant::whereIn('id', $variantIds)->get()->keyBy('id');
                $inventory  = InventoryItems::where('tenant_id', $tenantId)
                    ->whereIn('variant_id', $variantIds)
                    ->where('has_custom_pricing', 1)   // ★ only custom-priced items
                    ->get()
                    ->keyBy(fn($i) => "{$i->variant_id}|{$i->location_id}|{$i->department_id}");

                foreach ($tenantItems as $item) {
                    $key = "{$item->order_id}|{$item->variant_id}";
                    $txn = $txns->get($key);

                    if ($txn && (float) $txn->unit_cost_price > 0) {
                        $results[$item->id] = [
                            'unit_cost' => (float) $txn->unit_cost_price,
                            'unit_sell' => (float) ($txn->unit_selling_price ?? 0),
                            'source'    => 'log',
                        ];
                        continue;
                    }

                    $results[$item->id] = $this->fallbackPricingFromCache(
                        $item, $variants, $inventory, $isSingleShop
                    );
                }
            }
        }

        return $results;
    }

    /**
     * Fallback using pre-loaded caches. Avoids extra queries in the loop.
     */
    protected function fallbackPricingFromCache(
        OrderItem $item,
        $variants,
        $inventory,
        bool $isSingleShop
    ): array {
        $order   = $item->order;
        $variant = $variants->get($item->variant_id);

        if (! $variant) {
            return ['unit_cost' => 0.0, 'unit_sell' => 0.0, 'source' => 'unknown'];
        }

        // Safety net for callers that didn't select the pricing columns.
        if ($this->variantMissingPricingColumns($variant)) {
            $variant = ProductVariant::find($variant->id) ?? $variant;
        }

        if (! $isSingleShop && $order->location_id && $order->department_id) {
            $invKey = "{$variant->id}|{$order->location_id}|{$order->department_id}";
            $inv    = $inventory->get($invKey);

            if ($inv && (float) $inv->grand_total_cost_price > 0) {
                return [
                    'unit_cost' => (float) $inv->grand_total_cost_price,
                    'unit_sell' => (float) ($inv->discount_selling_price ?? $inv->selling_price ?? 0),
                    'source'    => 'item',
                ];
            }
        }

        $grand = (float) ($variant->grand_total_cost_price ?? 0);
        if ($grand > 0) {
            return [
                'unit_cost' => $grand,
                'unit_sell' => (float) ($variant->discount_selling_price ?? $variant->selling_price ?? 0),
                'source'    => 'variant',
            ];
        }

        $recomputed = (float) ($variant->supplier_cost_price ?? 0)
                    + (float) ($variant->total_shipping_cost ?? 0)
                    + (float) ($variant->ura_taxes_applied   ?? 0)
                    + (float) ($variant->additional_expenses ?? 0);

        return [
            'unit_cost' => $recomputed,
            'unit_sell' => (float) ($variant->discount_selling_price ?? $variant->selling_price ?? 0),
            'source'    => 'variant',
        ];
    }

    /**
     * Fallback: InventoryItems (only has_custom_pricing = 1) → Variant.
     */
    protected function fallbackPricing(OrderItem $item): array
    {
        $order   = $item->order;
        $variant = $item->productVariant;

        if (! $variant) {
            return ['unit_cost' => 0.0, 'unit_sell' => 0.0, 'source' => 'unknown'];
        }

        $isSingleShop = tenant_is_single_shop($order->tenant_id ?? $variant->tenant_id);

        // InventoryItems override — only when has_custom_pricing = 1
        if (! $isSingleShop && $order->location_id && $order->department_id) {
            $inv = InventoryItems::where('tenant_id', $variant->tenant_id)
                ->where('variant_id', $variant->id)
                ->where('location_id', $order->location_id)
                ->where('department_id', $order->department_id)
                ->where('has_custom_pricing', 1)   // ★
                ->first();

            if ($inv && (float) $inv->grand_total_cost_price > 0) {
                return [
                    'unit_cost' => (float) $inv->grand_total_cost_price,
                    'unit_sell' => (float) ($inv->discount_selling_price ?? $inv->selling_price ?? 0),
                    'source'    => 'item',
                ];
            }
        }

        // Variant overall
        $grand = (float) ($variant->grand_total_cost_price ?? 0);
        if ($grand > 0) {
            return [
                'unit_cost' => $grand,
                'unit_sell' => (float) ($variant->discount_selling_price ?? $variant->selling_price ?? 0),
                'source'    => 'variant',
            ];
        }

        // Recompute
        $recomputed = (float) ($variant->supplier_cost_price ?? 0)
                    + (float) ($variant->total_shipping_cost ?? 0)
                    + (float) ($variant->ura_taxes_applied   ?? 0)
                    + (float) ($variant->additional_expenses ?? 0);

        return [
            'unit_cost' => $recomputed,
            'unit_sell' => (float) ($variant->discount_selling_price ?? $variant->selling_price ?? 0),
            'source'    => 'variant',
        ];
    }
}