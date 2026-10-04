<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{
    SerialNumber, Product, ProductVariant,
    Department, Location, InventoryItems
};
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SerialController extends Controller
{
    /**
     * Serial numbers index — grouped by Product → Variant → Serials.
     */
    public function index(Request $request)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (! $user->hasPermissionTo('view inventory')) {
            abort(403);
        }

        $isSingleShop = tenant_is_single_shop($tenantId);

        $perPage = in_array((int) $request->input('per_page', 15), [15, 25, 50, 100])
            ? (int) $request->input('per_page', 15)
            : 15;

        // ── Scope: location + department, only meaningful in multi-shop ──
        $locationId   = $isSingleShop ? null : $request->input('location_id');
        $departmentId = $isSingleShop ? null : $request->input('department_id');

        // ── Base query: only serial-tracked products ──
        $productQuery = Product::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->where('inventory_strategy', 'serial')
            ->with([
                'variants' => function ($q) {
                    $q->where('is_active', 1)->orderBy('name');
                },
            ]);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $productQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku',  'like', "%{$search}%")
                  ->orWhereHas('variants', function ($v) use ($search) {
                      $v->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('product_id')) {
            $productQuery->where('id', $request->product_id);
        }

        $products   = $productQuery->orderBy('name')->get();
        $productIds = $products->pluck('id');

        // ── Load every serial for these products ──
        $serials = SerialNumber::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('variant_id', function ($q) use ($productIds) {
                $q->select('id')
                  ->from('product_variants')
                  ->whereIn('product_id', $productIds);
            })
            ->when($locationId,   fn($q) => $q->where('location_id',   $locationId))
            ->when($departmentId, fn($q) => $q->where('department_id', $departmentId))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->with(['location:id,name', 'department:id,name', 'order:id,order_number'])
            ->orderByDesc('created_at')
            ->get();

        // Group by variant
        $serialsByVariant = $serials->groupBy('variant_id');

        // ── Preload scoped inventory items for pricing context ──
        $variantIds = $products->flatMap(fn($p) => $p->variants->pluck('id'))->filter()->unique();

        $inventoryByVariant = collect();
        if (! $isSingleShop && ($locationId || $departmentId) && $variantIds->isNotEmpty()) {
            $inventoryByVariant = InventoryItems::query()
                ->where('tenant_id', $tenantId)
                ->whereIn('variant_id', $variantIds)
                ->when($locationId,   fn($q) => $q->where('location_id',   $locationId))
                ->when($departmentId, fn($q) => $q->where('department_id', $departmentId))
                ->get()
                ->keyBy('variant_id');
        }

        // ── Build the display tree ──
        $displayTree = $products->map(function ($product) use ($serialsByVariant, $inventoryByVariant, $isSingleShop) {
            $variants = $product->variants->map(function ($variant) use ($serialsByVariant, $inventoryByVariant, $isSingleShop) {
                $variantSerials = $serialsByVariant->get($variant->id, collect());

                // Resolve the effective pricing once per variant — same
                // cascade the POS uses.
                [$unitCost, $unitSell, $source] = $this->resolveVariantPricing(
                    $variant,
                    $inventoryByVariant->get($variant->id),
                    $isSingleShop
                );

                return (object) [
                    'variant'             => $variant,
                    'serials'             => $variantSerials,
                    'total'               => $variantSerials->count(),
                    'available'           => $variantSerials->where('status', SerialNumber::STATUS_AVAILABLE)->count(),
                    'sold'                => $variantSerials->where('status', SerialNumber::STATUS_SOLD)->count(),
                    'reserved'            => $variantSerials->where('status', SerialNumber::STATUS_RESERVED)->count(),
                    'returned'            => $variantSerials->where('status', SerialNumber::STATUS_RETURNED)->count(),
                    'lost'                => $variantSerials->where('status', SerialNumber::STATUS_LOST)->count(),
                    'damaged'             => $variantSerials->where('status', SerialNumber::STATUS_DAMAGED)->count(),
                    'effective_cost'      => $unitCost,
                    'effective_sell'      => $unitSell,
                    'pricing_source'      => $source,
                    'has_custom_pricing'  => $source !== 'variant',
                ];
            });

            return (object) [
                'product'  => $product,
                'variants' => $variants,
            ];
        });

        // ── Filter dropdown data ──
        $filterProducts = Product::where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->where('inventory_strategy', 'serial')
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);

        $statuses = [
            ['value' => '',                                'label' => __('pagination.all_statuses')],
            ['value' => SerialNumber::STATUS_AVAILABLE,   'label' => __('passwords.available')],
            ['value' => SerialNumber::STATUS_RESERVED,    'label' => __('passwords.reserved')],
            ['value' => SerialNumber::STATUS_SOLD,        'label' => __('passwords.sold')],
            ['value' => SerialNumber::STATUS_RETURNED,    'label' => __('passwords.returned')],
            ['value' => SerialNumber::STATUS_LOST,        'label' => __('passwords.lost')],
            ['value' => SerialNumber::STATUS_DAMAGED,     'label' => __('passwords.damaged')],
        ];

        $locations   = collect();
        $departments = collect();

        if (! $isSingleShop) {
            $locations   = Location::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);
            $departments = Department::where('tenant_id', $tenantId)
                ->when($locationId, fn($q) => $q->where('location_id', $locationId))
                ->orderBy('name')
                ->get(['id', 'name', 'location_id']);
        }

        return view('store.serials.index', [
            'displayTree'        => $displayTree,
            'filterProducts'     => $filterProducts,
            'statuses'           => $statuses,
            'locations'          => $locations,
            'departments'        => $departments,
            'isSingleShop'       => $isSingleShop,
            'search'             => $request->search,
            'productId'          => $request->product_id,
            'status'             => $request->status,
            'locationId'         => $locationId,
            'departmentId'       => $departmentId,
            'perPage'            => $perPage,
        ]);
    }

    /**
     * Resolve variant pricing with the same cascade the POS uses:
     *   inventory item (has_custom_pricing = 1) → variant
     *
     * Returns [unitCost, unitSell, source].
     */
    private function resolveVariantPricing(
        ProductVariant $variant,
        ?InventoryItems $inventory,
        bool $isSingleShop
    ): array {
        // ── Cost ──
        $unitCost = 0.0;
        $source   = 'variant';

        if (! $isSingleShop
            && $inventory
            && ((int) ($inventory->has_custom_pricing ?? 0) === 1)
            && (float) ($inventory->grand_total_cost_price ?? 0) > 0) {
            $unitCost = (float) $inventory->grand_total_cost_price;
            $source   = 'item';
        } else {
            $grand = (float) ($variant->grand_total_cost_price ?? 0);
            if ($grand > 0) {
                $unitCost = $grand;
            } else {
                $unitCost = (float) ($variant->supplier_cost_price ?? 0)
                          + (float) ($variant->total_shipping_cost ?? 0)
                          + (float) ($variant->ura_taxes_applied   ?? 0)
                          + (float) ($variant->additional_expenses ?? 0);
            }
        }

        // ── Selling ──
        $unitSell = 0.0;
        if (! $isSingleShop && $inventory) {
            $unitSell = (float) (
                $inventory->discount_selling_price
                ?? $inventory->selling_price
                ?? 0
            );
        }
        if ($unitSell <= 0) {
            $unitSell = (float) (
                $variant->discount_selling_price
                ?? $variant->selling_price
                ?? 0
            );
        }

        return [$unitCost, $unitSell, $source];
    }
}