<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\InventoryItems;
use App\Models\{ ProductVariant, Department, Location, InventoryAdjustments, InventoryTransactions };
use Illuminate\Support\Str;
use Illuminate\Support\Facades\{ Auth, DB, Log };
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use App\Models\InventoryPricingHistory;

class InventoryItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    // public function index(Request $request)
    // {
    //     $user = Auth::user();
    //     $tenantId = $user->tenant_id;
                
    //     if (!$user->hasPermissionTo('view inventory')) {
    //         if ($request->ajax() || $request->wantsJson()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => __('payments.not_authorized'),
    //             ]);
    //         }
    //         abort(403);
    //     }
        
    //     // Get per_page from request, default to 15
    //     $perPage = $request->input('per_page', 15);
        
    //     // Validate per_page is in allowed values
    //     $allowedPerPage = [15, 25, 50, 100];
    //     if (!in_array($perPage, $allowedPerPage)) {
    //         $perPage = 15;
    //     }
        
    //     // Build the query
    //     $query = InventoryItems::with(['variant', 'itemCreater', 'itemLocation', 'departmentItem']);
        
    //     // If user is NOT super_admin, filter by tenant
    //     if (!$user->hasRole('super_admin')) {
    //         $query->where('tenant_id', $tenantId)
    //             ->whereHas('variant', function ($query) use ($tenantId) {
    //                 $query->where('is_active', 1)
    //                     ->where('tenant_id', $tenantId);
    //             });
    //     } else {
    //         // Super_admin sees all active variants across all tenants
    //         $query->whereHas('variant', function ($query) {
    //             $query->where('is_active', 1);
    //         });
    //     }
        
    //     // Apply search if provided
    //     if ($request->filled('search')) {
    //         $search = $request->search;
    //         $query->where(function($q) use ($search) {
    //             $q->where('batch_number', 'like', "%{$search}%")
    //             ->orWhereHas('variant', fn($v) => $v->where('name', 'like', "%{$search}%")
    //                                                 ->orWhere('sku', 'like', "%{$search}%"))
    //             ->orWhereHas('itemLocation', fn($l) => $l->where('name', 'like', "%{$search}%"))
    //             ->orWhereHas('departmentItem', fn($d) => $d->where('name', 'like', "%{$search}%"));
    //         });
    //     }
        
    //     // Apply location filter if provided
    //     if ($request->filled('location_id')) {
    //         $query->where('location_id', $request->location_id);
    //     }
        
    //     // Apply department filter if provided
    //     if ($request->filled('department_id')) {
    //         $query->where('department_id', $request->department_id);
    //     }
        
    //     // Paginate with dynamic per_page
    //     $items = $query->orderBy('department_id', 'asc')
    //                 ->latest()
    //                 ->paginate($perPage);
        
    //     // Preserve filters in pagination links
    //     $items->appends([
    //         'per_page' => $perPage, 
    //         'search' => $request->search,
    //         'location_id' => $request->location_id,
    //         'department_id' => $request->department_id
    //     ]);
        
    //     $bladeToReload = $request->query('bladeFileToReload');
        
    //     // For AJAX requests - return just the component HTML
    //     if ($request->ajax() && $bladeToReload === 'reloadItemComponent') {
    //         return view('store.inventory-items.component', [
    //             'items' => $items,
    //         ])->render();
    //     }
        
    //     // For filter requests - return full view with filters applied
    //     if ($request->ajax() && ($request->filled('location_id') || $request->filled('department_id'))) {
    //         return view('store.inventory-items.component', [
    //             'items' => $items,
    //         ])->render();
    //     }
        
    //     // Regular page load with locations and departments for filters
    //     $locations = Location::where('tenant_id', $tenantId)->get();
    //     $departments = Department::where('tenant_id', $tenantId)->get();

    //     // Active variants for this tenant — used by both the single-item
    //     // "Create" modal and the new "Bulk Add" modal. (Kept explicit here
    //     // rather than relying on it being available some other way, so the
    //     // bulk modal doesn't silently break if that assumption ever changes.)
    //     $variantsQuery = ProductVariant::query()->where('is_active', 1);
    //     if (!$user->hasRole('super_admin')) {
    //         $variantsQuery->where('tenant_id', $tenantId);
    //     }
    //     $variants = $variantsQuery->orderBy('name')->get(['id', 'sku', 'name', 'overal_quantity_at_hand']);
        
    //     return view('store.items-index', [
    //         'items' => $items,
    //         'locations' => $locations,
    //         'departments' => $departments,
    //         'variants' => $variants,
    //     ]);
    // }

    public function index(Request $request)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('view inventory')) {
            abort(403, __('payments.not_authorized'));
        }

        $isAdmin         = $user->hasAnyRole(['super_admin', 'admin']);
        $userLocationIds = $user->locations()->pluck('locations.id')->toArray();

        // Dropdown data for filters + bulk-add modal
        $locationsQuery = Location::where('tenant_id', $tenantId)->orderBy('name');
        if (!$isAdmin) {
            $locationsQuery->whereIn('id', $userLocationIds);
        }
        $locations = $locationsQuery->get();

        $departments = Department::where('tenant_id', $tenantId)
            ->where('isActive', true)
            ->orderBy('name')
            ->get(['id', 'name', 'location_id']);

        $variantsQuery = ProductVariant::query()->where('is_active', 1);
        if (!$user->hasRole('super_admin')) {
            $variantsQuery->where('tenant_id', $tenantId);
        }
        $variants = $variantsQuery->orderBy('name')->get(['id', 'sku', 'name', 'overal_quantity_at_hand']);

        $initialFilters = [
            'search'        => (string) $request->get('search', ''),
            'location_id'   => (string) $request->get('location_id', ''),
            'department_id' => (string) $request->get('department_id', ''),
            'per_page'      => in_array((int) $request->get('per_page', 15), [15, 25, 50, 100], true)
                                ? (int) $request->get('per_page', 15)
                                : 15,
        ];

        return view('store.items-index', compact(
            'locations',
            'departments',
            'variants',
            'initialFilters',
        ));
    }

    /**
     * JSON list endpoint for the inventory items table.
     *
     * Mirrors index()'s filters and visibility rules, returns JS-shaped
     * rows + summary + pagination. Replaces the LiveBlade `component` render.
     *
     * GET /items/data
     */
    public function data(Request $request)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('view inventory')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ], 403);
        }

        $isAdmin         = $user->hasAnyRole(['super_admin', 'admin']);
        $userLocationIds = $user->locations()->pluck('locations.id')->toArray();

        $search       = trim((string) $request->get('search', ''));
        $locationId   = $request->get('location_id');
        $departmentId = $request->get('department_id');

        $perPage = (int) $request->get('per_page', 15);
        if (!in_array($perPage, [15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        $query = InventoryItems::query()
            ->with([
                'variant:id,sku,name,image_url,overal_quantity_at_hand,tenant_id',
                'itemLocation:id,name',
                'departmentItem:id,name,location_id',
                'itemCreater:id,name',
            ]);

        if (!$user->hasRole('super_admin')) {
            $query->where('tenant_id', $tenantId)
                ->whereHas('variant', function ($q) use ($tenantId) {
                    $q->where('is_active', 1)->where('tenant_id', $tenantId);
                });
        } else {
            $query->whereHas('variant', fn($q) => $q->where('is_active', 1));
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('batch_number', 'like', "%{$search}%")
                ->orWhereHas('variant', fn($v) =>
                    $v->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%"))
                ->orWhereHas('itemLocation', fn($l) => $l->where('name', 'like', "%{$search}%"))
                ->orWhereHas('departmentItem', fn($d) => $d->where('name', 'like', "%{$search}%"));
            });
        }

        if ($locationId) {
            if ($isAdmin || in_array((int) $locationId, $userLocationIds, true)) {
                $query->where('location_id', $locationId);
            }
        }

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        // Summary from the full filtered set (before pagination)
        $allFiltered = (clone $query)->get(['id', 'quantity_allocated', 'preferred_stock_level']);

        $summary = [
            'total'                    => $allFiltered->count(),
            'total_allocated'          => (float) $allFiltered->sum('quantity_allocated'),
            'total_preferred'          => (float) $allFiltered->sum('preferred_stock_level'),
            'below_preferred'          => $allFiltered
                ->filter(fn($i) => (int) $i->preferred_stock_level > 0
                                && (int) $i->quantity_allocated < (int) $i->preferred_stock_level)
                ->count(),
        ];

        $paginator = $query->orderBy('department_id', 'asc')->latest()->paginate($perPage);

        $rows = collect($paginator->items())->map(function (InventoryItems $item) {
            return [
                'id'                    => $item->id,
                'variant_id'            => $item->variant_id,
                'variant_sku'           => $item->variant?->sku ?? '—',
                'variant_name'          => $item->variant?->name ?? '—',
                'variant_image'         => $item->variant?->image_url,

                'overall_quantity'      => (float) ($item->variant?->overal_quantity_at_hand ?? 0),
                'quantity_allocated'    => (float) ($item->quantity_allocated ?? 0),
                'preferred_stock_level' => (int) ($item->preferred_stock_level ?? 0),
                'below_preferred'       => (int) ($item->preferred_stock_level ?? 0) > 0
                                        && (int) ($item->quantity_allocated ?? 0) < (int) ($item->preferred_stock_level ?? 0),

                'location_id'           => $item->location_id,
                'location_name'         => $item->itemLocation?->name ?? '—',
                'department_id'         => $item->department_id,
                'department_name'       => ucwords(str_replace('_', ' ', $item->departmentItem?->name ?? '—')),

                'expiry_date'           => optional($item->expiry_date)->format('Y-m-d'),
                'expiry_date_human'     => optional($item->expiry_date)->format('d M Y'),

                'created_by_name'       => $item->itemCreater?->name ?? '—',
                'created_at'            => optional($item->created_at)->format('d M Y, h:i a'),
            ];
        });

        return response()->json([
            'success'    => true,
            'data'       => $rows,
            'summary'    => $summary,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
                'has_more'     => $paginator->hasMorePages(),
            ],
            'filters' => [
                'search'        => $search,
                'location_id'   => $locationId,
                'department_id' => $departmentId,
            ],
        ]);
    }

    /**
     * Drawer payload for one inventory row.
     *
     * Returns the row, its variant state, the audit trail (last 100
     * adjustments + transactions), and the resolved pricing so the
     * drawer's panels can render without follow-up fetches.
     *
     * GET /items/{id}/details
     */
    public function details(Request $request, $id)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('view inventory')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ], 403);
        }

        $item = InventoryItems::with([
                'variant',
                'itemLocation:id,name',
                'departmentItem:id,name,location_id',
                'itemCreater:id,name',
            ])
            ->where('tenant_id', $tenantId)
            ->find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ], 404);
        }

        $variant = $item->variant;

        // Pricing resolution — same cascade as getPricing()
        $variantCost = (float) ($variant->grand_total_cost_price ?? 0);
        if ($variantCost <= 0) {
            $variantCost = (float) ($variant->supplier_cost_price ?? 0)
                        + (float) ($variant->total_shipping_cost ?? 0)
                        + (float) ($variant->ura_taxes_applied   ?? 0)
                        + (float) ($variant->additional_expenses ?? 0);
        }
        $variantSelling = (float) ($variant->selling_price ?? 0);
        $variantDisc    = (float) ($variant->discount_selling_price ?? 0);
        $hasCustom      = ((int) ($item->has_custom_pricing ?? 0) === 1);

        // Audit trail — last 100 rows each
        $adjustments = InventoryAdjustments::where('inventory_id', $item->id)
            ->where('tenant_id', $tenantId)
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn($a) => [
                'id'              => $a->id,
                'reason'          => $a->reason,
                'quantity_before' => (float) $a->quantity_before,
                'quantity_after'  => (float) $a->quantity_after,
                'notes'           => $a->notes,
                'created_at'      => optional($a->created_at)->format('d M Y H:i'),
            ]);

        $transactions = InventoryTransactions::where('inventory_id', $item->id)
            ->where('tenant_id', $tenantId)
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn($t) => [
                'id'         => $t->id,
                'type'       => $t->type,
                'quantity'   => (float) $t->quantity,
                'notes'      => $t->notes,
                'created_at' => optional($t->created_at)->format('d M Y H:i'),
            ]);

        return response()->json([
            'success' => true,
            'item' => [
                'id'                    => $item->id,
                'variant_id'            => $item->variant_id,
                'variant_sku'           => $variant?->sku,
                'variant_name'          => $variant?->name,
                'variant_image'         => $variant?->image_url,

                'overall_quantity'      => (float) ($variant?->overal_quantity_at_hand ?? 0),
                'quantity_allocated'    => (float) ($item->quantity_allocated ?? 0),
                'preferred_stock_level' => (int) ($item->preferred_stock_level ?? 0),
                'below_preferred'       => (int) ($item->preferred_stock_level ?? 0) > 0
                                        && (int) ($item->quantity_allocated ?? 0) < (int) ($item->preferred_stock_level ?? 0),

                'location_id'           => $item->location_id,
                'location_name'         => $item->itemLocation?->name,
                'department_id'         => $item->department_id,
                'department_name'       => ucwords(str_replace('_', ' ', $item->departmentItem?->name ?? '')),

                'expiry_date'           => optional($item->expiry_date)->format('Y-m-d'),
                'expiry_date_human'     => optional($item->expiry_date)->format('d M Y'),

                'created_by_name'       => $item->itemCreater?->name,
                'created_at'            => optional($item->created_at)->format('d M Y, h:i a'),

                'pricing' => [
                    'has_custom_pricing'     => $hasCustom,
                    'raw' => [
                        'supplier_cost_price'    => $item->supplier_cost_price,
                        'total_shipping_cost'    => $item->total_shipping_cost,
                        'ura_taxes_applied'      => $item->ura_taxes_applied,
                        'additional_expenses'    => $item->additional_expenses,
                        'grand_total_cost_price' => $item->grand_total_cost_price,
                        'selling_price'          => $item->selling_price,
                        'discount_selling_price' => $item->discount_selling_price,
                        'discount_percentage'    => $item->discount_percentage,
                    ],
                    'variant_fallback' => [
                        'grand_total_cost_price' => $variantCost,
                        'selling_price'          => $variantSelling,
                        'discount_selling_price' => $variantDisc,
                    ],
                    'effective' => [
                        'cost_price'     => $hasCustom ? (float) ($item->grand_total_cost_price ?? 0) : $variantCost,
                        'selling_price'  => $hasCustom ? (float) ($item->selling_price ?? 0)          : $variantSelling,
                        'discount_price' => $hasCustom ? (float) ($item->discount_selling_price ?? 0) : $variantDisc,
                        'pricing_source' => $hasCustom ? 'item' : 'variant',
                    ],
                ],
            ],
            'adjustments'  => $adjustments,
            'transactions' => $transactions,
            'permissions' => [
                'can_edit'          => $user->can('update stock levels'),
                'can_delete'        => $user->can('delete inventory') && (int) $item->quantity_allocated === 0,
                'can_change_pricing'=> $user->can('edit inventory'),
            ],
        ]);
    }

    /**
     * Sentence fragment: "Fred (fred@shop.com)".
     */
    private function actorLabel($user = null): string
    {
        $user = $user ?? Auth::user();
        return $user ? "{$user->name} ({$user->email})" : 'System';
    }

    /**
     * Sentence fragment: "at Main Store (Sales)" — from a resolved item.
     */
    private function itemContextLabel(?InventoryItems $item): string
    {
        if (!$item) return '';
        $loc  = $item->itemLocation?->name   ?? "Location #{$item->location_id}";
        $dept = $item->departmentItem?->name ?? "Department #{$item->department_id}";
        return " at {$loc} ({$dept})";
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
                
        if (!$user->hasPermissionTo('create inventory record')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        // Fetch the variant first and ensure it belongs to tenant
        $variant = \DB::table('product_variants')
            ->where('id', $request->variant_id)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$variant) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ]);
        }

        $data = $request->validate([
            'quantity_on_hand'     => 'required|integer|min:0',
            'quantity_allocated'   => [
                    'required',
                    'integer',
                    'min:0',
                    function ($attribute, $value, $fail) use ($variant) {
                        if ($value > $variant->overal_quantity_at_hand) {
                            $fail(__('pagination.allocated_not_greater_than_at_hand'));
                        }
                    }
                ],
            'preferred_stock_level'=> 'required|integer|min:0',
            'department_id'        => [
                'required',
                'integer',
                'exists:departments,id',
                function ($attribute, $value, $fail) use ($tenantId, $request) {
                    // Check if department exists and belongs to tenant
                    $department = Department::where('id', $value)
                                        ->where('tenant_id', $tenantId)
                                        ->first();
                    
                    if (!$department) {
                        $fail(__('pagination.selected_dpt_invalid'));
                        return;
                    }
                    
                    // Check if department belongs to the selected location
                    if ($department->location_id != $request->location_id) {
                        $fail('The selected department does not belong to the selected location.');
                    }
                }
            ],
            'location_id'          => [
                'required',
                'integer',
                'exists:locations,id',
                function ($attribute, $value, $fail) use ($tenantId) {
                    $location = Location::where('id', $value)
                                    ->where('tenant_id', $tenantId)
                                    ->first();
                    if (!$location) {
                        $fail('The selected location is invalid.');
                    }
                }
            ],
            'batch_number'         => 'nullable|string|max:50',
            'expiry_date'          => 'nullable|date|after_or_equal:today',
            'variant_id'           => [
                'required',
                'exists:product_variants,id',
                function ($attribute, $value, $fail) use ($tenantId) {
                    $variant = ProductVariant::where('id', $value)
                                            ->where('tenant_id', $tenantId)
                                            ->first();
                    if (!$variant) {
                        $fail('The selected variant is invalid.');
                    }
                },
                Rule::unique('inventory_items')->where(function ($q) use ($tenantId, $request) {
                    return $q->where('variant_id', $request->variant_id)
                            ->where('department_id', $request->department_id)
                            ->where('location_id', $request->location_id)
                            ->where('tenant_id', $tenantId);
                }),
            ],
        ], [
            'variant_id.unique' => __('pagination.variant_already_exists_in_department_location'),
        ]);

        $data['created_by'] = $user->id;
        $data['tenant_id']  = $tenantId;

        if (empty($data['batch_number'])) {
            $data['batch_number'] = strtoupper('BTH-' . strtoupper(Str::random(6)));
        }

        // Start transaction for data consistency
        DB::beginTransaction();

        try {
            $inventoryItem = InventoryItems::create($data);

            // Update variant stock
            \DB::table('product_variants')
                ->where('id', $data['variant_id'])
                ->where('tenant_id', $tenantId)
                ->update([
                    'overal_quantity_at_hand' => $variant->overal_quantity_at_hand - $data['quantity_allocated'],
                ]);

            // ─── Gather context ────────────────────────────────────────
            $actor = $this->actorLabel($user);

            $variantName = $variant->name ?? 'Unknown Item';
            $variantSku  = $variant->sku  ?? 'N/A';

            $locationName   = Location::find($data['location_id'])?->name   ?? "Location #{$data['location_id']}";
            $departmentName = Department::find($data['department_id'])?->name ?? "Department #{$data['department_id']}";

            $allocated = (int) $data['quantity_allocated'];
            $overallBefore = (int) $variant->overal_quantity_at_hand;
            $overallAfter  = $overallBefore - $allocated;

            // ★ The full audit sentence
            $narrative = sprintf(
                '%s created an inventory record for "%s" (SKU %s)%s and allocated %s unit(s) to that branch. '
                . 'Branch allocation: 0 → %s units. Overall stock: %s → %s units.',
                $actor,
                $variantName,
                $variantSku,
                " at {$locationName} ({$departmentName})",
                number_format($allocated),
                number_format($allocated),
                number_format($overallBefore),
                number_format($overallAfter)
            );

            InventoryAdjustments::create([
                'inventory_id'    => $inventoryItem->id,
                'quantity_before' => 0,
                'quantity_after'  => $allocated,
                'reason'          => 'inventory_created',
                'notes'           => $narrative,
                'created_by'      => $user->id,
                'tenant_id'       => $tenantId,
            ]);

            if ($allocated > 0) {
                InventoryTransactions::create([
                    'inventory_id'   => $inventoryItem->id,
                    'quantity'       => $allocated,
                    'reference_id'   => $variant->id,
                    'reference_type' => 'inventory_creation',
                    'type'           => 'transfer_in',
                    'notes'          => $narrative,
                    'created_by'     => $user->id,
                    'tenant_id'      => $tenantId,
                ]);
            }

            DB::commit();

            return response()->json([
                'success'     => true,
                'reload'      => true,
                'refresh'     => false,
                'componentId' => 'reloadItemComponent',
                'message'     => __('auth._created')
                                . " — \"{$variantName}\" allocated to {$locationName} ({$departmentName})",
                'redirect'    => route('items.index'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Inventory item creation failed', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => __('auth.create_failed'),
            ]);
        }
    }

    /**
     * Batch-create inventory placeholder rows for every selected
     * variant × department combination, quantity fixed at zero.
     *
     * This exists purely to remove the tedium of creating one inventory
     * item at a time through the single-item modal when rolling a large
     * catalog (hundreds of variants) out across many locations/departments
     * (hundreds of combinations). Nothing about stock levels is decided
     * here — every row starts at 0 on-hand / 0 allocated and gets adjusted
     * later through the normal edit flow or a stock receipt.
     *
     * Combinations that already exist (same variant + department + tenant)
     * are silently skipped rather than erroring the whole batch out, since
     * re-running this against an already-seeded department is expected
     * ("select everything again" is easier than remembering what's missing).
     *
     * (POST) /items/batch
     */
    public function storeBatch(Request $request)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('create inventory record')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        $validated = $request->validate([
            'variant_ids'       => 'required|array|min:1',
            'variant_ids.*'     => 'integer',
            'department_ids'    => 'required|array|min:1',
            'department_ids.*'  => 'integer',
        ]);

        // ── Re-scope everything to this tenant server-side. Never trust the
        // ids the browser sent beyond "the user picked these checkboxes" —
        // a tampered payload could otherwise reach into another tenant's
        // variants/departments. ──────────────────────────────────────────
        $variantIds = ProductVariant::where('tenant_id', $tenantId)
                            ->whereIn('id', $validated['variant_ids'])
                            ->pluck('id');

        $departments = Department::where('tenant_id', $tenantId)
                            ->whereIn('id', $validated['department_ids'])
                            ->get(['id', 'location_id']);

        if ($variantIds->isEmpty() || $departments->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => __('pagination.no_valid_selection'),
            ]);
        }

        // ── Work out which of the requested combinations already exist so
        // we skip them instead of tripping a unique-constraint error and
        // losing the whole batch. ────────────────────────────────────────
        $existingKeys = DB::table('inventory_items')
            ->where('tenant_id', $tenantId)
            ->whereIn('variant_id', $variantIds)
            ->whereIn('department_id', $departments->pluck('id'))
            ->get(['variant_id', 'department_id'])
            ->map(fn ($row) => $row->variant_id . '-' . $row->department_id)
            ->flip();

        $now     = now();
        $rows    = [];
        $created = 0;
        $skipped = 0;

        foreach ($departments as $department) {
            foreach ($variantIds as $variantId) {
                $key = $variantId . '-' . $department->id;

                if (isset($existingKeys[$key])) {
                    $skipped++;
                    continue;
                }

                $rows[] = [
                    'variant_id'            => $variantId,
                    'department_id'         => $department->id,
                    'location_id'           => $department->location_id,
                    'quantity_on_hand'      => 0,
                    'quantity_allocated'    => 0,
                    'preferred_stock_level' => 0,
                    'batch_number'          => null,
                    'expiry_date'           => null,
                    'created_by'            => $user->id,
                    'tenant_id'             => $tenantId,
                    'created_at'            => $now,
                    'updated_at'            => $now,
                ];
                $created++;
            }
        }

        DB::beginTransaction();

        try {
            // Insert in chunks so one giant catalog rollout never becomes a
            // single multi-thousand-row query.
            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('inventory_items')->insert($chunk);
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Batch inventory item creation failed', [
                'tenant_id' => $tenantId,
                'error'     => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('auth.create_failed'),
            ]);
        }

        return response()->json([
            'success'     => true,
            'reload'      => true,
            'refresh'     => false,
            'componentId' => 'reloadItemComponent',
            'message'     => __('pagination.batch_create_summary', [
                'created' => $created,
                'skipped' => $skipped,
            ]),
            'created'     => $created,
            'skipped'     => $skipped,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
                
        if (!$user->hasPermissionTo('edit inventory')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        // Find inventory item and ensure it belongs to tenant
        $item = InventoryItems::with(['variant', 'itemLocation', 'departmentItem'])
                ->where('id', $id)
                ->where('tenant_id', $tenantId)
                ->first();

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ]);
        }

        // Fetch live variant stock
        $variant = $item->variant()->where('tenant_id', $tenantId)->first();
        
        if (!$variant) {
            return response()->json([
                'success' => false,
                'message' => __('auth.variant_not_found'),
            ]);
        }

        // Validate request
        $validated = $request->validate([
            'department_id' => [
                'required',
                'exists:departments,id',
                function ($attribute, $value, $fail) use ($tenantId, $request) {
                    $department = Department::where('id', $value)
                                        ->where('tenant_id', $tenantId)
                                        ->first();
                    if (!$department) {
                        $fail(__('pagination.department_invalid'));
                        return;
                    }
                    if ($department->location_id != $request->location_id) {
                        $fail(__('pagination.department_not_belong_to_location'));
                    }
                }
            ],
            'location_id' => [
                'required',
                'integer',
                'exists:locations,id',
                function ($attribute, $value, $fail) use ($tenantId) {
                    $location = Location::where('id', $value)
                                    ->where('tenant_id', $tenantId)
                                    ->first();
                    if (!$location) {
                        $fail(__('pagination.location_invalid'));
                    }
                }
            ],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:today'],
            'preferred_stock_level' => ['nullable', 'integer', 'min:0'],
            'quantity_allocated' => [
                'required',
                'integer',
                'min:0',
                function ($attribute, $value, $fail) use ($variant, $item) {
                    $available = $variant->overal_quantity_at_hand + $item->quantity_allocated;
                    if ($value > $available) {
                        $fail(__('pagination.allocated_not_greater_than_at_hand') . " Available: {$available}");
                    }
                }
            ],
        ]);

        // Unique rule
        $uniqueRule = Rule::unique('inventory_items')->where(function ($q) use ($tenantId, $item, $request) {
            return $q->where('variant_id', $item->variant_id)
                    ->where('department_id', $request->department_id)
                    ->where('location_id', $request->location_id)
                    ->where('tenant_id', $tenantId);
        })->ignore($item->id);

        $request->validate([
            'variant_id' => [$uniqueRule],
        ], [
            'variant_id.unique' => __('pagination.variant_already_exists_in_department_location'),
        ]);

        // Start transaction
        DB::beginTransaction();

        try {
            // ✅ Calculate allocation difference
            $oldAllocated = $item->quantity_allocated;
            $newAllocated = (int) $validated['quantity_allocated'];
            $allocationDiff = $newAllocated - $oldAllocated;
            
            // ✅ Check stock availability
            if ($allocationDiff > $variant->overal_quantity_at_hand) {
                throw new \Exception("Insufficient stock available");
            }

            // ─── Gather context ────────────────────────────────────────
            $actor = $this->actorLabel($user);

            $variantName = $variant->name ?? 'Unknown Item';
            $variantSku  = $variant->sku  ?? 'N/A';

            $locationName   = $item->itemLocation?->name   ?? "Location #{$item->location_id}";
            $departmentName = $item->departmentItem?->name ?? "Department #{$item->department_id}";

            $oldAllocated   = (int) $item->quantity_allocated;
            $newAllocated   = (int) $validated['quantity_allocated'];
            $allocationDiff = $newAllocated - $oldAllocated;
            $absDiff        = abs($allocationDiff);

            $overallBefore = (int) $variant->overal_quantity_at_hand;
            $overallAfter  = $overallBefore - $allocationDiff;

            // ★ The full audit sentence — shape varies by direction
            if ($allocationDiff > 0) {
                $narrative = sprintf(
                    '%s increased branch allocation of "%s" (SKU %s) at %s (%s) by %s unit(s). '
                    . 'Branch allocation: %s → %s units. Overall stock: %s → %s units. '
                    . 'Stock moved from Overall into this branch.',
                    $actor,
                    $variantName,
                    $variantSku,
                    $locationName,
                    $departmentName,
                    number_format($absDiff),
                    number_format($oldAllocated),
                    number_format($newAllocated),
                    number_format($overallBefore),
                    number_format($overallAfter)
                );
            } elseif ($allocationDiff < 0) {
                $narrative = sprintf(
                    '%s reduced branch allocation of "%s" (SKU %s) at %s (%s) by %s unit(s). '
                    . 'Branch allocation: %s → %s units. Overall stock: %s → %s units. '
                    . 'Stock returned from this branch back into Overall.',
                    $actor,
                    $variantName,
                    $variantSku,
                    $locationName,
                    $departmentName,
                    number_format($absDiff),
                    number_format($oldAllocated),
                    number_format($newAllocated),
                    number_format($overallBefore),
                    number_format($overallAfter)
                );
            } else {
                // Metadata-only edit (no allocation change) — still worth recording
                $narrative = sprintf(
                    '%s edited the inventory record for "%s" (SKU %s) at %s (%s) — '
                    . 'no allocation change (still %s unit(s)). Edited fields: %s.',
                    $actor,
                    $variantName,
                    $variantSku,
                    $locationName,
                    $departmentName,
                    number_format($newAllocated),
                    implode(', ', array_diff(array_keys($validated), ['quantity_allocated']))
                );
            }

            // Short label for toast
            $shortNote = $allocationDiff > 0
                ? "Allocated {$absDiff} × \"{$variantName}\" to {$locationName}"
                : ($allocationDiff < 0
                    ? "Returned {$absDiff} × \"{$variantName}\" from {$locationName}"
                    : "Updated \"{$variantName}\" metadata (no stock change)");
                    

            // ✅ Record adjustment (audit trail — always written, even for metadata edits)
            InventoryAdjustments::create([
                'quantity_before' => $oldAllocated,
                'quantity_after'  => $newAllocated,
                'reason'          => $allocationDiff === 0 ? 'inventory_metadata_update' : 'inventory_allocation_update',
                'notes'           => $narrative,
                'inventory_id'    => $item->id,
                'created_by'      => $user->id,
                'tenant_id'       => $item->tenant_id,
            ]);

            // ✅ Update inventory item
            $item->update($validated);

            // ✅ Update variant overall stock
            $variant->overal_quantity_at_hand = $overallAfter;
            $variant->save();

            // ✅ Record transaction ONLY if allocation actually moved
            if ($allocationDiff !== 0) {
                InventoryTransactions::create([
                    'quantity'       => $allocationDiff,
                    'reference_id'   => $item->id,
                    'reference_type' => 'inventory_item',
                    'type'           => $allocationDiff > 0 ? 'transfer_in' : 'transfer_out',
                    'notes'          => $narrative,
                    'inventory_id'   => $item->id,
                    'created_by'     => $user->id,
                    'tenant_id'      => $item->tenant_id,
                ]);
            }

            DB::commit();

            return response()->json([
                'success'     => true,
                'reload'      => true,
                'refresh'     => false,
                'componentId' => 'reloadItemComponent',
                'message'     => __('auth._updated') . " ({$shortNote})",
                'redirect'    => route('items.index'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Inventory item update failed', [
                'item_id' => $id,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => __('auth.update_failed') . ': ' . $e->getMessage(),
            ]);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('delete inventory')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ], 403);
        }

        $item = InventoryItems::with(['variant', 'itemLocation', 'departmentItem'])
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ], 404);
        }

        // ─── Refuse to delete a branch row that still has stock ───
        // Force the user to return stock to Overall first, so we never
        // silently vaporize inventory. Adjust the guard as your policy dictates.
        if ((int) $item->quantity_allocated > 0) {
            return response()->json([
                'success' => false,
                'message' => 'This inventory record still holds '
                        . $item->quantity_allocated
                        . ' unit(s). Return them to Overall Stock before deleting.',
            ], 422);
        }

        DB::beginTransaction();

        try {
            $actor = $this->actorLabel($user);

            $variantName = $item->variant?->name ?? 'Unknown Item';
            $variantSku  = $item->variant?->sku  ?? 'N/A';

            $locationName   = $item->itemLocation?->name   ?? "Location #{$item->location_id}";
            $departmentName = $item->departmentItem?->name ?? "Department #{$item->department_id}";

            $narrative = sprintf(
                '%s deleted the inventory record for "%s" (SKU %s) at %s (%s). '
                . 'The record held %s unit(s) at the time of deletion (all returned previously). '
                . 'Overall stock is unchanged.',
                $actor,
                $variantName,
                $variantSku,
                $locationName,
                $departmentName,
                number_format((int) $item->quantity_allocated)
            );

            // Write the audit row BEFORE deleting, so the FK still resolves
            InventoryAdjustments::create([
                'inventory_id'    => $item->id,
                'quantity_before' => (int) $item->quantity_allocated,
                'quantity_after'  => 0,
                'reason'          => 'inventory_record_deleted',
                'notes'           => $narrative,
                'created_by'      => $user->id,
                'tenant_id'       => $item->tenant_id,
            ]);

            $item->delete();

            DB::commit();

            return response()->json([
                'success'     => true,
                'reload'      => true,
                'refresh'     => false,
                'componentId' => 'reloadItemComponent',
                'message'     => __('auth._deleted') . " ({$variantName} at {$locationName})",
                'redirect'    => route('items.index'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Inventory item deletion failed', [
                'item_id'   => $id,
                'tenant_id' => $tenantId,
                'error'     => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('auth.delete_failed') . ': ' . $e->getMessage(),
            ], 500);
        }
    }



    /**
     * Return the resolved pricing for one inventory item.
     *
     * Cascade:
     *   1. The item's own override — only when has_custom_pricing = 1
     *      and selling_price > 0
     *   2. The parent variant's grand_total_cost_price / selling_price /
     *      discount_selling_price
     *
     * Also returns the raw override values so the edit modal can
     * prefill the form when has_custom_pricing is on.
     */
    public function getPricing(string $id)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (! $user->hasPermissionTo('view inventory')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ], 403);
        }

        $item = InventoryItems::with(['variant', 'departmentItem', 'itemLocation'])
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        if (! $item) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ], 404);
        }

        $variant = $item->variant;

        // ── Variant fallback values ────────────────────────────
        $variantCost = (float) ($variant->grand_total_cost_price ?? 0);
        if ($variantCost <= 0) {
            $variantCost = (float) ($variant->supplier_cost_price ?? 0)
                         + (float) ($variant->total_shipping_cost ?? 0)
                         + (float) ($variant->ura_taxes_applied   ?? 0)
                         + (float) ($variant->additional_expenses ?? 0);
        }

        $variantSelling = (float) ($variant->selling_price ?? 0);
        $variantDisc    = (float) ($variant->discount_selling_price ?? 0);

        // ── Item override (only used when has_custom_pricing = 1) ─
        $hasCustom = ((int) ($item->has_custom_pricing ?? 0) === 1);

        $payload = [
            'item' => [
                'id'        => $item->id,
                'variant'   => [
                    'id'   => $variant?->id,
                    'name' => $variant?->name,
                    'sku'  => $variant?->sku,
                ],
                'department' => $item->departmentItem?->name,
                'location'   => $item->itemLocation?->name,
                'quantity_allocated' => (float) ($item->quantity_allocated ?? 0),
            ],

            'has_custom_pricing' => $hasCustom,

            // Raw override fields — for prefilling the edit form
            'raw' => [
                'supplier_cost_price'    => $item->supplier_cost_price,
                'total_shipping_cost'    => $item->total_shipping_cost,
                'ura_taxes_applied'      => $item->ura_taxes_applied,
                'additional_expenses'    => $item->additional_expenses,
                'grand_total_cost_price' => $item->grand_total_cost_price,
                'selling_price'          => $item->selling_price,
                'discount_selling_price' => $item->discount_selling_price,
                'discount_percentage'    => $item->discount_percentage,
            ],

            // Variant fallback — always provided so the UI can show
            // "inherited" values even when has_custom_pricing = 0
            'variant' => [
                'grand_total_cost_price' => $variantCost,
                'selling_price'          => $variantSelling,
                'discount_selling_price' => $variantDisc,
            ],

            // Resolved effective values — what the POS would charge
            'effective' => [
                'cost_price'     => $hasCustom ? (float) ($item->grand_total_cost_price ?? 0) : $variantCost,
                'selling_price'  => $hasCustom ? (float) ($item->selling_price ?? 0)          : $variantSelling,
                'discount_price' => $hasCustom ? (float) ($item->discount_selling_price ?? 0) : $variantDisc,
                'pricing_source' => $hasCustom ? 'item' : 'variant',
            ],
        ];

        return response()->json([
            'success' => true,
            'data'    => $payload,
        ]);
    }

    /**
     * Update an inventory item's per-scope pricing.
     *
     * Passing has_custom_pricing = false clears every override column and
     * reverts the item to inheriting from the variant.
     */
    public function updatePricing(Request $request, string $id)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('edit inventory')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ], 403);
        }

        $item = InventoryItems::with(['variant', 'itemLocation', 'departmentItem'])
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ], 404);
        }

        $validated = $request->validate([
            'has_custom_pricing'  => 'required|boolean',
            'supplier_cost_price' => 'nullable|numeric|min:0',
            'total_shipping_cost' => 'nullable|numeric|min:0',
            'ura_taxes_applied'   => 'nullable|numeric|min:0',
            'additional_expenses' => 'nullable|numeric|min:0',
            'selling_price'       => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'reason'              => 'nullable|string|max:191',
        ]);

        $recorder = app('inventory-pricing-history');

        // ── Snapshot BEFORE any mutation ──────────────────────────
        $oldValues = $recorder->snapshot($item);
        $hadCustomBefore = (bool) $item->has_custom_pricing;

        DB::beginTransaction();

        try {
            // ── Determine which change type this is ────────────────
            if (! $validated['has_custom_pricing']) {
                // ★ Turning OFF — clear every override, revert to variant
                $item->fill([
                    'has_custom_pricing'     => 0,
                    'supplier_cost_price'    => null,
                    'total_shipping_cost'    => null,
                    'ura_taxes_applied'      => null,
                    'additional_expenses'    => null,
                    'grand_total_cost_price' => null,
                    'selling_price'          => null,
                    'discount_selling_price' => null,
                    'discount_percentage'    => null,
                    'markup_percentage'      => null,
                    'pricing_source'         => 'variant',
                ]);
                $item->save();

                // Record history ONLY if it was on before — turning off
                // an already-off item is a no-op.
                if ($hadCustomBefore) {
                    $recorder->record(
                        $item,
                        $oldValues,
                        InventoryPricingHistory::CHANGE_DISABLED,
                        $validated['reason'] ?? null
                    );
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'reload'  => true,
                    'message' => __('passwords.custom_pricing_removed'),
                ]);
            }

            // ★ Turning ON (or already ON and being updated)
            $item->fill([
                'supplier_cost_price' => $validated['supplier_cost_price'] ?? null,
                'total_shipping_cost' => $validated['total_shipping_cost'] ?? null,
                'ura_taxes_applied'   => $validated['ura_taxes_applied']   ?? null,
                'additional_expenses' => $validated['additional_expenses'] ?? null,
                'selling_price'       => $validated['selling_price']       ?? null,
                'discount_percentage' => $validated['discount_percentage'] ?? null,
                'has_custom_pricing'  => 1,
                'pricing_source'      => 'item',
            ]);

            // Recompute grand_total_cost_price from components
            $item->grand_total_cost_price = (float) ($item->supplier_cost_price ?? 0)
                                        + (float) ($item->total_shipping_cost ?? 0)
                                        + (float) ($item->ura_taxes_applied   ?? 0)
                                        + (float) ($item->additional_expenses ?? 0);

            // Recompute discount_selling_price
            if ($item->selling_price !== null) {
                $item->discount_selling_price = ($item->discount_percentage > 0)
                    ? $item->selling_price - (($item->selling_price * (float) $item->discount_percentage) / 100)
                    : $item->selling_price;
            }

            // Recompute markup
            if ($item->grand_total_cost_price > 0 && $item->selling_price > 0) {
                $item->markup_percentage =
                    (($item->selling_price - $item->grand_total_cost_price)
                    / $item->grand_total_cost_price) * 100;
            }

            $item->save();

            // ★ Record history — pick ENABLED if it wasn't custom before,
            //   otherwise UPDATED.
            $changeType = $hadCustomBefore
                ? InventoryPricingHistory::CHANGE_UPDATED
                : InventoryPricingHistory::CHANGE_ENABLED;

            $recorder->record(
                $item,
                $oldValues,
                $changeType,
                $validated['reason'] ?? null
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'reload'  => true,
                'message' => $hadCustomBefore
                    ? __('passwords.custom_pricing_updated')
                    : __('passwords.custom_pricing_enabled'),
                'data'    => [
                    'grand_total_cost_price' => $item->grand_total_cost_price,
                    'selling_price'          => $item->selling_price,
                    'discount_selling_price' => $item->discount_selling_price,
                    'has_custom_pricing'     => (bool) $item->has_custom_pricing,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Inventory pricing update failed', [
                'item_id' => $id,
                'error'   => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('auth.update_failed') . ': ' . $e->getMessage(),
            ], 500);
        }
    }

}