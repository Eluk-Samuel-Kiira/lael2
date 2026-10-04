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

class InventoryItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
                
        if (!$user->hasPermissionTo('view inventory')) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.not_authorized'),
                ]);
            }
            abort(403);
        }
        
        // Get per_page from request, default to 15
        $perPage = $request->input('per_page', 15);
        
        // Validate per_page is in allowed values
        $allowedPerPage = [15, 25, 50, 100];
        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 15;
        }
        
        // Build the query
        $query = InventoryItems::with(['variant', 'itemCreater', 'itemLocation', 'departmentItem']);
        
        // If user is NOT super_admin, filter by tenant
        if (!$user->hasRole('super_admin')) {
            $query->where('tenant_id', $tenantId)
                ->whereHas('variant', function ($query) use ($tenantId) {
                    $query->where('is_active', 1)
                        ->where('tenant_id', $tenantId);
                });
        } else {
            // Super_admin sees all active variants across all tenants
            $query->whereHas('variant', function ($query) {
                $query->where('is_active', 1);
            });
        }
        
        // Apply search if provided
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('batch_number', 'like', "%{$search}%")
                ->orWhereHas('variant', fn($v) => $v->where('name', 'like', "%{$search}%")
                                                    ->orWhere('sku', 'like', "%{$search}%"))
                ->orWhereHas('itemLocation', fn($l) => $l->where('name', 'like', "%{$search}%"))
                ->orWhereHas('departmentItem', fn($d) => $d->where('name', 'like', "%{$search}%"));
            });
        }
        
        // Apply location filter if provided
        if ($request->filled('location_id')) {
            $query->where('location_id', $request->location_id);
        }
        
        // Apply department filter if provided
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }
        
        // Paginate with dynamic per_page
        $items = $query->orderBy('department_id', 'asc')
                    ->latest()
                    ->paginate($perPage);
        
        // Preserve filters in pagination links
        $items->appends([
            'per_page' => $perPage, 
            'search' => $request->search,
            'location_id' => $request->location_id,
            'department_id' => $request->department_id
        ]);
        
        $bladeToReload = $request->query('bladeFileToReload');
        
        // For AJAX requests - return just the component HTML
        if ($request->ajax() && $bladeToReload === 'reloadItemComponent') {
            return view('store.inventory-items.component', [
                'items' => $items,
            ])->render();
        }
        
        // For filter requests - return full view with filters applied
        if ($request->ajax() && ($request->filled('location_id') || $request->filled('department_id'))) {
            return view('store.inventory-items.component', [
                'items' => $items,
            ])->render();
        }
        
        // Regular page load with locations and departments for filters
        $locations = Location::where('tenant_id', $tenantId)->get();
        $departments = Department::where('tenant_id', $tenantId)->get();

        // Active variants for this tenant — used by both the single-item
        // "Create" modal and the new "Bulk Add" modal. (Kept explicit here
        // rather than relying on it being available some other way, so the
        // bulk modal doesn't silently break if that assumption ever changes.)
        $variantsQuery = ProductVariant::query()->where('is_active', 1);
        if (!$user->hasRole('super_admin')) {
            $variantsQuery->where('tenant_id', $tenantId);
        }
        $variants = $variantsQuery->orderBy('name')->get(['id', 'sku', 'name', 'overal_quantity_at_hand']);
        
        return view('store.items-index', [
            'items' => $items,
            'locations' => $locations,
            'departments' => $departments,
            'variants' => $variants,
        ]);
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

            DB::commit();

            return response()->json([
                'success' => true,
                'reload' => true,
                'refresh' => false,
                'componentId' => 'reloadItemComponent',
                'message' => __('auth._created'),
                'redirect' => route('items.index'),
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

            // ─── Get Location and Department Names ────────────────────
            $locationName = $item->itemLocation ? $item->itemLocation->name : 'Unknown Location';
            $departmentName = $item->departmentItem ? $item->departmentItem->name : 'Unknown Department';
            $variantName = $variant->name ?? 'Unknown Variant';
            $adjustAmount = abs($allocationDiff);
            
            // ─── Build Readable Notes ──────────────────────────────────
            if ($allocationDiff > 0) {
                // Allocating MORE to branch (moving from overall to branch)
                $action = 'Allocated';
                $directionText = $adjustAmount . ' unit(s) of "' . $variantName . '" from Overall Stock to ' 
                                . $locationName . ' (' . $departmentName . ')';
            } elseif ($allocationDiff < 0) {
                // Returning from branch to overall (reducing branch allocation)
                $action = 'Returned';
                $directionText = $adjustAmount . ' unit(s) of "' . $variantName . '" from ' 
                                . $locationName . ' (' . $departmentName . ') back to Overall Stock';
            } else {
                // No change
                $action = 'No change';
                $directionText = 'No allocation change for "' . $variantName . '" at ' 
                                . $locationName . ' (' . $departmentName . ')';
            }

            // ✅ Record adjustment BEFORE updating (audit trail)
            InventoryAdjustments::create([
                'quantity_before' => $oldAllocated,
                'quantity_after'  => $newAllocated,
                'reason'          => 'inventory_allocation_update',
                'notes'           => $directionText,
                'inventory_id'    => $item->id,
                'created_by'      => auth()->id() ?? null,
                'tenant_id'       => $item->tenant_id,
            ]);

            // ✅ Update inventory item
            $item->update($validated);

            // ✅ Update variant stock
            $variant->overal_quantity_at_hand = $variant->overal_quantity_at_hand - $allocationDiff;
            $variant->save();

            // ✅ Record transaction (movement) ONLY if there's an actual change
            if ($allocationDiff != 0) {
                InventoryTransactions::create([
                    'quantity'       => $allocationDiff,
                    'reference_id'   => $item->id,
                    'reference_type' => 'inventory_item',
                    'type'           => $allocationDiff > 0 ? 'transfer_in' : 'transfer_out',
                    'notes'          => $directionText,
                    'inventory_id'   => $item->id,
                    'created_by'     => auth()->id() ?? null,
                    'tenant_id'      => $item->tenant_id,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'reload' => true,
                'refresh' => false,
                'componentId' => 'reloadItemComponent',
                'message' => __('auth._updated'),
                'redirect' => route('items.index'),
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
    public function destroy(string $id)
    {
        $item = InventoryItems::find($id);

        $item->delete();

        return response()->json([
            'success' => true,
            'reload' => true,
            'refresh' => false,
            'componentId' => 'reloadItemComponent',
            'message' => __('auth._deleted'),
            'redirect' => route('items.index'),
        ]);
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

        if (! $user->hasPermissionTo('edit inventory')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ], 403);
        }

        $item = InventoryItems::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        if (! $item) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ], 404);
        }

        $validated = $request->validate([
            'has_custom_pricing'     => 'required|boolean',
            'supplier_cost_price'    => 'nullable|numeric|min:0',
            'total_shipping_cost'    => 'nullable|numeric|min:0',
            'ura_taxes_applied'      => 'nullable|numeric|min:0',
            'additional_expenses'    => 'nullable|numeric|min:0',
            'selling_price'          => 'nullable|numeric|min:0',
            'discount_percentage'    => 'nullable|numeric|min:0|max:100',
        ]);

        DB::beginTransaction();

        try {
            if (! $validated['has_custom_pricing']) {
                // ★ Turn off — clear all override columns, revert to variant
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

                DB::commit();

                return response()->json([
                    'success' => true,
                    'reload'  => true,
                    'message' => __('passwords.custom_pricing_removed'),
                ]);
            }

            // ★ Turn on (or update) — write the override values
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

            // Recompute discount_selling_price from selling price and %
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

            DB::commit();

            return response()->json([
                'success' => true,
                'reload'  => true,
                'message' => __('passwords.custom_pricing_updated'),
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