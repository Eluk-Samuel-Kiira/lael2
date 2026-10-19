<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\InventoryItems;
use App\Models\InventoryAdjustments;
use App\Models\{ InventoryTransactions, Department, Location };
use Illuminate\Support\Facades\{ Auth, DB, Log };

class InventoryAdjustmentsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('update stock levels')) {
            abort(403, __('payments.not_authorized'));
        }

        $locations = Location::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $departments = Department::where('tenant_id', $tenantId)
            ->where('isActive', true)
            ->orderBy('name')
            ->get(['id', 'name', 'location_id']);

        $initialFilters = [
            'search'        => (string) $request->get('search', ''),
            'location_id'   => (string) $request->get('location_id', ''),
            'department_id' => (string) $request->get('department_id', ''),
            'per_page'      => in_array((int) $request->get('per_page', 15), [15, 25, 50, 100], true)
                                ? (int) $request->get('per_page', 15)
                                : 15,
        ];

        return view('store.adjustment-index', compact('locations', 'departments', 'initialFilters'));
    }

    /**
     * JSON list for the adjustments table.
     * Mirrors index()'s filters exactly.
     *
     * GET /stocks/data
     */
    public function data(Request $request)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('update stock levels')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ], 403);
        }

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
            ])
            ->where('tenant_id', $tenantId)
            ->whereHas('variant', fn($q) => $q->where('is_active', 1)->where('tenant_id', $tenantId));

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->whereHas('variant', fn($v) =>
                    $v->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%"))
                ->orWhereHas('itemLocation', fn($l) => $l->where('name', 'like', "%{$search}%"))
                ->orWhereHas('departmentItem', fn($d) => $d->where('name', 'like', "%{$search}%"));
            });
        }

        if ($locationId)   $query->where('location_id', $locationId);
        if ($departmentId) $query->where('department_id', $departmentId);

        // Summary over the full filtered set
        $allFiltered = (clone $query)->get(['id', 'quantity_allocated']);

        $summary = [
            'total'           => $allFiltered->count(),
            'total_allocated' => (float) $allFiltered->sum('quantity_allocated'),
        ];

        $paginator = $query->latest()->paginate($perPage);

        $rows = collect($paginator->items())->map(function (InventoryItems $item) {
            return [
                'id'                 => $item->id,
                'variant_id'         => $item->variant_id,
                'variant_sku'        => $item->variant?->sku ?? '—',
                'variant_name'       => $item->variant?->name ?? '—',
                'variant_image'      => $item->variant?->image_url,

                'overall_quantity'   => (float) ($item->variant?->overal_quantity_at_hand ?? 0),
                'quantity_allocated' => (float) ($item->quantity_allocated ?? 0),

                'location_id'        => $item->location_id,
                'location_name'      => $item->itemLocation?->name ?? '—',
                'department_id'      => $item->department_id,
                'department_name'    => ucwords(str_replace('_', ' ', $item->departmentItem?->name ?? '—')),
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

    public function details(Request $request, $id)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('update stock levels')) {
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

        // ── Pricing cascade (unchanged) ─────────────────────────────
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

        // ── Audit trail ─────────────────────────────────────────────
        // Enrich each adjustment with the resolved actor name and a
        // human-readable *short* reason (in addition to the raw reason key).
        $adjustments = InventoryAdjustments::with('createdBy:id,name')
            ->where('inventory_id', $item->id)
            ->where('tenant_id', $tenantId)
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn($a) => [
                'id'              => $a->id,
                'reason'          => $a->reason,
                'reason_label'    => $this->reasonLabel($a->reason),
                'quantity_before' => (float) $a->quantity_before,
                'quantity_after'  => (float) $a->quantity_after,
                'delta'           => (float) $a->quantity_after - (float) $a->quantity_before,
                'notes'           => $a->notes,
                'created_by_name' => $a->createdBy?->name,
                'created_at'      => optional($a->created_at)->format('d M Y H:i'),
                'created_at_iso'  => optional($a->created_at)->toIso8601String(),
            ]);

        $transactions = InventoryTransactions::with('createdBy:id,name')
            ->where('inventory_id', $item->id)
            ->where('tenant_id', $tenantId)
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn($t) => [
                'id'              => $t->id,
                'type'            => $t->type,
                'type_label'      => $this->transactionLabel($t->type),
                'quantity'        => (float) $t->quantity,
                'reference_id'    => $t->reference_id,
                'reference_type'  => $t->reference_type,
                'notes'           => $t->notes,
                'created_by_name' => $t->creator?->name,
                'created_at'      => optional($t->created_at)->format('d M Y H:i'),
                'created_at_iso'  => optional($t->created_at)->toIso8601String(),
            ]);

        // ── Recent movement summary (last 30 days) ─────────────────
        $recentIn  = (float) InventoryTransactions::where('inventory_id', $item->id)
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', now()->subDays(30))
            ->whereIn('type', ['transfer_in', 'adjustment', 'inventory_creation'])
            ->where('quantity', '>', 0)
            ->sum('quantity');

        $recentOut = (float) InventoryTransactions::where('inventory_id', $item->id)
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', now()->subDays(30))
            ->where('quantity', '<', 0)
            ->sum('quantity');

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

                // ── NEW: record metadata ────────────────────────────
                'batch_number'          => $item->batch_number,
                'quantity_on_hand'      => (float) ($item->quantity_on_hand ?? 0),
                'quantity_on_order'     => (float) ($item->quantity_on_order ?? 0),

                'location_id'           => $item->location_id,
                'location_name'         => $item->itemLocation?->name,
                'department_id'         => $item->department_id,
                'department_name'       => ucwords(str_replace('_', ' ', $item->departmentItem?->name ?? '')),

                'expiry_date'           => optional($item->expiry_date)->format('Y-m-d'),
                'expiry_date_human'     => optional($item->expiry_date)->format('d M Y'),
                'expiry_days_remaining' => $item->expiry_date
                    ? (int) now()->startOfDay()->diffInDays($item->expiry_date, false)
                    : null,

                'created_by_name'       => $item->itemCreater?->name,
                'created_at'            => optional($item->created_at)->format('d M Y, h:i a'),
                'updated_at'            => optional($item->updated_at)->format('d M Y, h:i a'),

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
            'movement_summary' => [
                'window_days'      => 30,
                'recent_in'        => $recentIn,
                'recent_out'       => abs($recentOut),
                'net'              => $recentIn + $recentOut,
            ],
            'permissions' => [
                'can_edit'           => $user->can('update stock levels'),
                'can_adjust'         => $user->can('update stock levels'),
                'can_transfer'       => $user->can('transfer stock'),
                'can_delete'         => $user->can('delete inventory') && (int) $item->quantity_allocated === 0,
                'can_change_pricing' => $user->can('edit inventory'),
            ],
        ]);
    }

    /**
     * Human-readable label for an adjustment reason key.
     */
    private function reasonLabel(?string $reason): string
    {
        return match ($reason) {
            'inventory_created'         => 'Record created',
            'inventory_allocation_update'=> 'Allocation updated',
            'inventory_metadata_update' => 'Metadata updated',
            'inventory_record_deleted'  => 'Record deleted',
            'stock_adjustment'          => 'Stock adjusted',
            'stock_transfer'            => 'Stock transferred',
            default                     => $reason ? ucwords(str_replace('_', ' ', $reason)) : '—',
        };
    }

    /**
     * Human-readable label for a transaction type.
     */
    private function transactionLabel(?string $type): string
    {
        return match ($type) {
            'transfer_in'         => 'Stock in',
            'transfer_out'        => 'Stock out',
            'adjustment'          => 'Adjustment',
            'inventory_creation'  => 'Created',
            default               => $type ? ucwords(str_replace('_', ' ', $type)) : '—',
        };
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
        //
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
                
        if (!$user->hasPermissionTo('update stock levels')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        // Get the item first to check variant stock
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

        if (!$item->variant) {
            return response()->json([
                'success' => false,
                'message' => __('auth.variant_not_found'),
            ]);
        }

        // ✅ Validate the request with proper custom rules
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'overal_quantity_at_hand' => 'required|integer|min:0',
            'current_quantity' => 'required|integer|min:0',
            'adjust_amount' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) use ($item) {
                    // For positive (adding to branch), check overall stock
                    if ($value > 0 && $value > $item->variant->overal_quantity_at_hand) {
                        $fail(__('pagination.adjust_amount_exceeds_stock') . ' Available: ' . $item->variant->overal_quantity_at_hand);
                    }
                    // For negative (removing from branch), check branch stock
                    if ($value < 0 && abs($value) > $item->quantity_allocated) {
                        $fail(__('pagination.cannot_remove_more_than_allocated') . ' Available: ' . $item->quantity_allocated);
                    }
                }
            ],
        ]);

        // ✅ Check if validation failed
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        // ✅ Calculate new quantity automatically
        $validated['new_quantity'] = $validated['current_quantity'] + $validated['adjust_amount'];

        // ✅ Ensure new quantity is not negative
        if ($validated['new_quantity'] < 0) {
            return response()->json([
                'success' => false,
                'message' => __('pagination.new_quantity_negative'),
                'errors' => [
                    'adjust_amount' => [__('pagination.new_quantity_negative')]
                ]
            ], 422);
        }

        // ✅ Skip if no actual adjustment is being made
        if ($validated['adjust_amount'] == 0) {
            return response()->json([
                'success' => true,
                'message' => __('pagination.no_adjustment_needed'),
            ]);
        }

        // ✅ Additional check: Ensure we don't go below zero
        if ($validated['new_quantity'] < 0) {
            return response()->json([
                'success' => false,
                'message' => __('pagination.new_quantity_negative'),
                'errors' => [
                    'adjust_amount' => [__('pagination.new_quantity_negative')]
                ]
            ], 422);
        }

        // Capture BEFORE mutation
        $beforeOverall = (int) $item->variant->overal_quantity_at_hand;

        // Compute AFTER
        $afterOverall = $validated['adjust_amount'] > 0
            ? $beforeOverall - (int) $validated['adjust_amount']   // inbound to branch ⇒ drain overall
            : $beforeOverall + (int) abs($validated['adjust_amount']); // outbound from branch ⇒ refund overall

        if ($afterOverall < 0) {
            return response()->json([
                'success' => false,
                'message' => __('pagination.overall_stock_cannot_be_negative'),
                'errors'  => ['adjust_amount' => [__('pagination.overall_stock_cannot_be_negative')]],
            ], 422);
        }



        // Start transaction
        DB::beginTransaction();

        try {

            $item->update(['quantity_allocated' => (int) $validated['new_quantity']]);
            $item->variant->overal_quantity_at_hand = max(0, $afterOverall);
            $item->variant->save();

            // ─── Gather context ────────────────────────────────────────
            $actor          = $this->actorLabel($user);
            $variantName    = $item->variant->name ?? 'Unknown Item';
            $variantSku     = $item->variant->sku  ?? 'N/A';
            $locationName   = $item->itemLocation?->name   ?? "Location #{$item->location_id}";
            $departmentName = $item->departmentItem?->name ?? "Department #{$item->department_id}";

            $adjustAmount = (int) abs($validated['adjust_amount']);
            $isInbound    = $validated['adjust_amount'] > 0;

            $beforeAllocated = (int) $validated['current_quantity'];
            $afterAllocated  = (int) $validated['new_quantity'];

            $beforeOverall   = (int) $item->variant->overall_quantity_at_hand
                                + ($isInbound ? $adjustAmount : -$adjustAmount); // original before mutation
            $afterOverall    = (int) $item->variant->overal_quantity_at_hand;    // already saved above

            $action  = $isInbound ? 'moved' : 'returned';
            $flowTxt = $isInbound
                ? "from Overall Stock into {$locationName} ({$departmentName})"
                : "from {$locationName} ({$departmentName}) back into Overall Stock";

            // ★ The full audit sentence
            $narrative = sprintf(
                '%s %s %s unit(s) of "%s" (SKU %s) %s. '
                . 'Branch allocation: %s → %s units. Overall stock: %s → %s units.',
                $actor,
                $action,
                number_format($adjustAmount),
                $variantName,
                $variantSku,
                $flowTxt,
                number_format($beforeAllocated),
                number_format($afterAllocated),
                number_format($beforeOverall),
                number_format($afterOverall)
            );

            // Short label for the response message (used in the toast)
            $shortNote = ucfirst($action) . " {$adjustAmount} × \"{$variantName}\" {$flowTxt}";


            // ✅ Record adjustment (audit trail)
            InventoryAdjustments::create([
                'quantity_before' => (int) $validated['current_quantity'],
                'quantity_after'  => (int) $validated['new_quantity'],
                'reason'          => 'stock_adjustment',
                'notes'           => $narrative,
                'inventory_id'    => $item->id,
                'created_by'      => $user->id,
                'tenant_id'       => $item->tenant_id,
            ]);

            // ✅ Record transaction (movement)
            InventoryTransactions::create([
                'quantity'       => (int) $validated['adjust_amount'],
                'reference_id'   => $item->id,
                'reference_type' => 'adjustment',
                'type'           => 'adjustment',
                'notes'          => $narrative,
                'inventory_id'   => $item->id,
                'created_by'     => $user->id, 
                'tenant_id'      => $item->tenant_id,
            ]);

            DB::commit();

            return response()->json([
                'success'     => true,
                'reload'      => true,
                'refresh'     => false,
                'componentId' => 'reloadStockComponent',
                'message'     => __('passwords._stock_adjusted') . " ({$shortNote})",
                'redirect'    => route('stocks.index'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Stock adjustment failed', [
                'item_id' => $id,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => __('pagination.adjustment_failed') . ': ' . $e->getMessage(),
            ], 500);
        }
    }
    
    
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function transferStock(Request $request, string $id)
    {
        // Log::info('=== TRANSFER STOCK START ===');
        // Log::info('Request data:', $request->all());
        // Log::info('ID: ' . $id);
        
        $user = Auth::user();
        $tenantId = $user->tenant_id;
                
        if (!$user->hasPermissionTo('transfer stock')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }
        
        DB::beginTransaction();
        try {
            // ✅ Validate the essential fields
            $validated = $request->validate([
                'department_id'    => [
                    'required',
                    'integer',
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
                'location_id'      => [
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
                'transfer_amount'  => 'required|integer|min:1',
            ], [
                'department_id.required' => __('pagination.department_required'),
                'location_id.required' => __('pagination.location_required'),
                'transfer_amount.required' => __('pagination.transfer_amount_required'),
                'transfer_amount.integer' => __('pagination.transfer_amount_integer'),
                'transfer_amount.min' => __('pagination.transfer_amount_min'),
            ]);

            $adjustAmount = (int) $validated['transfer_amount'];

            // ✅ Get source inventory item with eager loaded relationships
            $sourceItem = InventoryItems::with(['variant', 'itemLocation', 'departmentItem'])
                            ->where('id', $id)
                            ->where('tenant_id', $tenantId)
                            ->first();

            if (!$sourceItem) {
                return response()->json([
                    'success' => false,
                    'message' => __('auth._not_found'),
                ]);
            }

            // ✅ Get target location and department names
            $targetLocation = Location::where('id', $validated['location_id'])
                                ->where('tenant_id', $tenantId)
                                ->first();
            
            $targetDepartment = Department::where('id', $validated['department_id'])
                                ->where('tenant_id', $tenantId)
                                ->first();

            if (!$targetLocation || !$targetDepartment) {
                return response()->json([
                    'success' => false,
                    'message' => __('pagination.invalid_target_location_or_department'),
                ]);
            }

            // ✅ Get the REAL current quantity from the database
            $current = (int) $sourceItem->quantity_allocated;
            
            Log::info("Source item found. Real current quantity: $current");

            // ✅ Check if adjust amount exceeds current quantity
            if ($adjustAmount > $current) {
                Log::warning("Adjust amount ($adjustAmount) exceeds current ($current)");
                return response()->json([
                    'success' => false,
                    'message' => __('pagination.max_quantity_reached')
                ]);
            }

            // ✅ If source and target are same location & department, do nothing
            if ($sourceItem->location_id == $validated['location_id'] &&
                $sourceItem->department_id == $validated['department_id']) {
                
                Log::warning('Source and target are the same');
                return response()->json([
                    'success' => false,
                    'message' => __('passwords.stock_already_present'),
                ]);
            }

            // ✅ Find target inventory item
            $targetItem = InventoryItems::with(['variant', 'itemLocation', 'departmentItem'])
                ->where('variant_id', $sourceItem->variant_id)
                ->where('location_id', $validated['location_id'])
                ->where('department_id', $validated['department_id'])
                ->where('tenant_id', $tenantId)
                ->first();

            if (!$targetItem) {
                Log::error('Target inventory item not found');
                return response()->json([
                    'success' => false,
                    'message' => __('passwords.create_inv_first')
                ]);
            }

            // ─── Gather context ────────────────────────────────────────
            $actor = $this->actorLabel($user);

            $variantName = $sourceItem->variant?->name ?? 'Unknown Item';
            $variantSku  = $sourceItem->variant?->sku  ?? 'N/A';

            $sourceLocationName   = $sourceItem->itemLocation?->name   ?? "Location #{$sourceItem->location_id}";
            $sourceDepartmentName = $sourceItem->departmentItem?->name ?? "Department #{$sourceItem->department_id}";
            $targetLocationName   = $targetLocation->name;
            $targetDepartmentName = $targetDepartment->name;

            // ✅ Update target stock
            $targetBefore = (int) $targetItem->quantity_allocated;
            $targetItem->quantity_allocated += $adjustAmount;
            $targetItem->save();
            $targetAfter = (int) $targetItem->quantity_allocated;

            // ✅ Update source stock
            $sourceBefore = (int) $sourceItem->quantity_allocated;
            $sourceItem->quantity_allocated -= $adjustAmount;
            $sourceItem->save();
            $sourceAfter = (int) $sourceItem->quantity_allocated;

            // ─── The two sides of the story ────────────────────────────
            $sharedHeader = sprintf(
                '%s transferred %s unit(s) of "%s" (SKU %s)',
                $actor,
                number_format($adjustAmount),
                $variantName,
                $variantSku
            );

            $fromTo = sprintf(
                'from %s (%s) to %s (%s)',
                $sourceLocationName,
                $sourceDepartmentName,
                $targetLocationName,
                $targetDepartmentName
            );

            // ★ Source side: sending
            $transferNarrative = sprintf(
                '%s %s. Source branch stock: %s → %s units. '
                . 'The stock now belongs to %s (%s).',
                $sharedHeader,
                $fromTo,
                number_format($sourceBefore),
                number_format($sourceAfter),
                $targetLocationName,
                $targetDepartmentName
            );

            // ★ Target side: receiving
            $receivedNarrative = sprintf(
                '%s %s. Receiving branch stock: %s → %s units. '
                . 'The stock came from %s (%s).',
                $sharedHeader,
                $fromTo,
                number_format($targetBefore),
                number_format($targetAfter),
                $sourceLocationName,
                $sourceDepartmentName
            );

            // ✅ Update target stock
            $targetBefore = $targetItem->quantity_allocated;
            $targetItem->quantity_allocated += $adjustAmount;
            $targetItem->save();
            $targetAfter = $targetItem->quantity_allocated;

            // ✅ Update source stock
            $sourceBefore = $sourceItem->quantity_allocated;
            $sourceItem->quantity_allocated -= $adjustAmount;
            $sourceItem->save();
            $sourceAfter = $sourceItem->quantity_allocated;

            // Log::info("Source: $sourceBefore → $sourceAfter, Target: $targetBefore → $targetAfter");

            // ✅ Source adjustment
            InventoryAdjustments::create([
                'inventory_id'    => $sourceItem->id,
                'quantity_before' => $sourceBefore,
                'quantity_after'  => $sourceAfter,
                'reason'          => 'stock_transfer',
                'notes'           => $transferNarrative,
                'created_by'      => $user->id,
                'tenant_id'       => $sourceItem->tenant_id,
            ]);

            // ✅ Target adjustment
            InventoryAdjustments::create([
                'inventory_id'    => $targetItem->id,
                'quantity_before' => $targetBefore,
                'quantity_after'  => $targetAfter,
                'reason'          => 'stock_transfer',
                'notes'           => $receivedNarrative,
                'created_by'      => $user->id,
                'tenant_id'       => $targetItem->tenant_id,
            ]);

            // ✅ Source transaction (money OUT)
            InventoryTransactions::create([
                'inventory_id'   => $sourceItem->id,
                'quantity'       => -$adjustAmount,
                'reference_id'   => $targetItem->id,
                'reference_type' => 'transfer',
                'type'           => 'transfer_out',
                'notes'          => $transferNarrative,
                'created_by'     => $user->id,
                'tenant_id'      => $sourceItem->tenant_id,

                
            ]);

            // ✅ Target transaction (money IN)
            InventoryTransactions::create([
                'inventory_id'   => $targetItem->id,
                'quantity'       => $adjustAmount,
                'reference_id'   => $sourceItem->id,
                'reference_type' => 'transfer',
                'type'           => 'transfer_in',
                'notes'          => $receivedNarrative,
                'created_by'     => $user->id,
                'tenant_id'      => $targetItem->tenant_id,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'reload' => true,
                'refresh' => false,
                'componentId' => 'reloadStockComponent',
                'message' => __('passwords.stock_transfer_success'),
                'redirect' => route('stocks.index'),
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::error('Validation errors:', $e->errors());
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Transfer error:', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
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
}
