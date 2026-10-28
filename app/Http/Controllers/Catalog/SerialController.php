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

        // Filter dropdown data
        $filterProducts = Product::where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->where('inventory_strategy', 'serial')
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);

        $statuses = [
            ['value' => '',                              'label' => __('pagination.all_statuses')],
            ['value' => SerialNumber::STATUS_AVAILABLE,  'label' => __('passwords.available')],
            ['value' => SerialNumber::STATUS_RESERVED,   'label' => __('passwords.reserved')],
            ['value' => SerialNumber::STATUS_SOLD,       'label' => __('passwords.sold')],
            ['value' => SerialNumber::STATUS_RETURNED,   'label' => __('passwords.returned')],
            ['value' => SerialNumber::STATUS_LOST,       'label' => __('passwords.lost')],
            ['value' => SerialNumber::STATUS_DAMAGED,    'label' => __('passwords.damaged')],
        ];

        $locations   = collect();
        $departments = collect();

        if (! $isSingleShop) {
            $locations = Location::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);
            $departments = Department::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name', 'location_id']);
        }

        return view('store.serials.index', compact(
            'isSingleShop', 'filterProducts', 'statuses', 'locations', 'departments'
        ));
    }

    public function data(Request $request)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (! $user->hasPermissionTo('view inventory')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }

        $isSingleShop = tenant_is_single_shop($tenantId);

        $locationId   = $isSingleShop ? null : $request->input('location_id');
        $departmentId = $isSingleShop ? null : $request->input('department_id');

        // ── Products (serial strategy only) ──
        $productQuery = Product::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->where('inventory_strategy', 'serial')
            ->with(['variants' => fn($q) => $q->where('is_active', 1)->orderBy('name')]);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $productQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('sku',  'like', "%{$search}%")
                ->orWhereHas('variants', fn($v) =>
                        $v->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%"));
            });
        }
        if ($request->filled('product_id')) {
            $productQuery->where('id', $request->product_id);
        }

        $products   = $productQuery->orderBy('name')->get();
        $productIds = $products->pluck('id');

        // ── Serials ──
        $serials = SerialNumber::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('variant_id', function ($q) use ($productIds) {
                $q->select('id')->from('product_variants')->whereIn('product_id', $productIds);
            })
            ->when($locationId,   fn($q) => $q->where('location_id',   $locationId))
            ->when($departmentId, fn($q) => $q->where('department_id', $departmentId))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->with(['location:id,name', 'department:id,name', 'order:id,order_number'])
            ->orderByDesc('created_at')
            ->get();

        $serialsByVariant = $serials->groupBy('variant_id');

        // ── Inventory overrides ──
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

        // ── Build tree ──
        $tree = $products->map(function ($product) use ($serialsByVariant, $inventoryByVariant, $isSingleShop) {
            $variants = $product->variants->map(function ($variant) use ($serialsByVariant, $inventoryByVariant, $isSingleShop) {
                $variantSerials = $serialsByVariant->get($variant->id, collect());

                [$unitCost, $unitSell, $source] = $this->resolveVariantPricing(
                    $variant, $inventoryByVariant->get($variant->id), $isSingleShop
                );

                return [
                    'variant' => [
                        'id'                 => $variant->id,
                        'name'               => $variant->name,
                        'sku'                => $variant->sku,
                        'effective_cost'     => (float) $unitCost,
                        'effective_sell'     => (float) $unitSell,
                        'pricing_source'     => $source,
                        'has_custom_pricing' => $source !== 'variant',
                    ],
                    'serials' => $variantSerials->map(fn(SerialNumber $s) => [
                        'id'              => $s->id,
                        'serial_number'   => $s->serial_number,
                        'status'          => $s->status,
                        'status_label'    => $s->status_label,
                        'status_color'    => $s->status_color,
                        'location_name'   => $s->location?->name,
                        'department_name' => $s->department?->name,
                        'sold_unit_price' => $s->sold_unit_price !== null ? (float) $s->sold_unit_price : null,
                        'order_number'    => $s->order?->order_number,
                    ])->values(),
                    'counts' => [
                        'total'     => $variantSerials->count(),
                        'available' => $variantSerials->where('status', SerialNumber::STATUS_AVAILABLE)->count(),
                        'sold'      => $variantSerials->where('status', SerialNumber::STATUS_SOLD)->count(),
                        'reserved'  => $variantSerials->where('status', SerialNumber::STATUS_RESERVED)->count(),
                        'returned'  => $variantSerials->where('status', SerialNumber::STATUS_RETURNED)->count(),
                        'lost'      => $variantSerials->where('status', SerialNumber::STATUS_LOST)->count(),
                        'damaged'   => $variantSerials->where('status', SerialNumber::STATUS_DAMAGED)->count(),
                    ],
                ];
            })->values();

            return [
                'product'  => [
                    'id'             => $product->id,
                    'name'           => $product->name,
                    'sku'            => $product->sku,
                    'variants_count' => $product->variants->count(),
                ],
                'variants' => $variants,
            ];
        })->values();

        // ── Aggregate stats across the full filtered set ──
        $allSerials = $serials;
        $stats = [
            'products'    => $tree->count(),
            'variants'    => $tree->sum(fn($n) => count($n['variants'])),
            'total'       => $allSerials->count(),
            'available'   => $allSerials->where('status', SerialNumber::STATUS_AVAILABLE)->count(),
            'sold'        => $allSerials->where('status', SerialNumber::STATUS_SOLD)->count(),
            'reserved'    => $allSerials->where('status', SerialNumber::STATUS_RESERVED)->count(),
            'returned'    => $allSerials->where('status', SerialNumber::STATUS_RETURNED)->count(),
            'lost'        => $allSerials->where('status', SerialNumber::STATUS_LOST)->count(),
            'damaged'     => $allSerials->where('status', SerialNumber::STATUS_DAMAGED)->count(),

            // Sales figures — sum of frozen sold prices
            'sold_value'  => (float) $allSerials->sum(fn($s) => $s->sold_unit_price ?? 0),
        ];

        return response()->json([
            'success' => true,
            'tree'    => $tree,
            'stats'   => $stats,
        ]);
    }

    public function variantDrawerData(Request $request, $variantId)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (! $user->hasPermissionTo('view inventory')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ], 403);
        }

        $isSingleShop = tenant_is_single_shop($tenantId);

        $variant = ProductVariant::with([
                'product:id,name,sku,inventory_strategy',
                'unitMeasure:id,name',
                'variantCreater:id,name',
            ])
            ->where('tenant_id', $tenantId)
            ->where('id', $variantId)
            ->first();

        if (! $variant) {
            return response()->json(['success' => false, 'message' => __('auth._not_found')], 404);
        }

        // Pricing resolution (same cascade as SerialController::index)
        [$unitCost, $unitSell, $source] = $this->resolveVariantPricing($variant, null, $isSingleShop);

        // Serials, latest first
        $serials = SerialNumber::with(['location:id,name', 'department:id,name', 'order:id,order_number'])
            ->where('tenant_id', $tenantId)
            ->where('variant_id', $variant->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn(SerialNumber $s) => [
                'id'              => $s->id,
                'serial_number'   => $s->serial_number,
                'status'          => $s->status,
                'status_label'    => $s->status_label,
                'status_color'    => $s->status_color,
                'location_id'     => $s->location_id,
                'location_name'   => $s->location?->name,
                'department_id'   => $s->department_id,
                'department_name' => $s->department?->name,
                'order_id'        => $s->order_id,
                'order_number'    => $s->order?->order_number,
                'sold_unit_price' => $s->sold_unit_price !== null ? (float) $s->sold_unit_price : null,
                'has_custom_pricing' => (bool) ($s->has_custom_pricing ?? false),
                'created_at'      => optional($s->created_at)->format('d M Y, H:i'),
            ]);

        $summary = [
            'total'     => $serials->count(),
            'available' => $serials->where('status', SerialNumber::STATUS_AVAILABLE)->count(),
            'sold'      => $serials->where('status', SerialNumber::STATUS_SOLD)->count(),
            'reserved'  => $serials->where('status', SerialNumber::STATUS_RESERVED)->count(),
            'returned'  => $serials->where('status', SerialNumber::STATUS_RETURNED)->count(),
            'lost'      => $serials->where('status', SerialNumber::STATUS_LOST)->count(),
            'damaged'   => $serials->where('status', SerialNumber::STATUS_DAMAGED)->count(),
        ];

        $can = [
            'can_edit_pricing' => $user->can('edit variant'),
            'can_delete'       => $user->can('delete variant'),
            'can_change_status'=> $user->can('edit variant'),
        ];

        return response()->json([
            'success' => true,
            'variant' => array_merge([
                'id'                => $variant->id,
                'product_id'        => $variant->product_id,
                'product_name'      => $variant->product?->name,
                'product_sku'       => $variant->product?->sku,
                'name'              => $variant->name,
                'sku'               => $variant->sku,
                'weight_unit_name'  => $variant->unitMeasure?->name,
                'effective_cost'    => $unitCost,
                'effective_sell'    => $unitSell,
                'pricing_source'    => $source,
                'creator_name'      => $variant->variantCreater?->name,
                'is_single_shop'    => $isSingleShop,
            ], $can),
            'serials' => $serials->values(),
            'summary' => $summary,
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