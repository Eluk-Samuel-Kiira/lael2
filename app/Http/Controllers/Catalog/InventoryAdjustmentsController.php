<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\InventoryItems;
use App\Models\InventoryAdjustments;
use App\Models\{ InventoryTransactions, Department, Location };
use Illuminate\Support\Facades\{ Auth, DB };

class InventoryAdjustmentsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
                
        if (!$user->hasPermissionTo('update stock levels')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        // Retrieve only those items that have its variants is active
        $items = InventoryItems::with(['variant', 'itemCreater'])
                ->whereHas('variant', function ($query) use ($tenantId) {
                    $query->where('is_active', 1)
                        ->where('tenant_id', $tenantId);
                })
                ->where('tenant_id', $tenantId)
                ->latest()
                ->get();

        $bladeToReload = $request->query('bladeFileToReload');
        switch ($bladeToReload) {
            case 'reloadStockComponent':
                return view('store.inventory-adjustment.component', [
                    'items' => $items,
                ]);
            default:
                return view('store.adjustment-index', [
                    'items' => $items,
                ]);
        }
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
        $item = InventoryItems::with('variant')
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

        // Validate only the fields we need from the request
        $validated = $request->validate([
            'overal_quantity_at_hand' => 'required|integer|min:0',
            'current_quantity' => 'required|integer|min:0',
            'adjust_amount' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) use ($item) {
                    // For positive (adding to branch), check overall stock
                    if ($value > 0 && $value > $item->variant->overal_quantity_at_hand) {
                        $fail(__('pagination.adjust_amount_exceeds_stock'));
                    }
                    // For negative (removing from branch), check branch stock
                    if ($value < 0 && abs($value) > $item->quantity_allocated) {
                        $fail(__('pagination.cannot_remove_more_than_allocated'));
                    }
                }
            ],
        ]);

        // Calculate new quantity automatically
        $validated['new_quantity'] = $validated['current_quantity'] + $validated['adjust_amount'];

        // Ensure new quantity is not negative
        if ($validated['new_quantity'] < 0) {
            return response()->json([
                'success' => false,
                'message' => __('pagination.new_quantity_negative'),
            ]);
        }

        // Skip if no actual adjustment is being made
        if ($validated['adjust_amount'] == 0) {
            return response()->json([
                'success' => true,
                'message' => __('pagination.no_adjustment_needed'),
            ]);
        }

        // Start transaction
        DB::beginTransaction();

        try {
            // Update inventory item quantity with calculated value
            $item->update([
                'quantity_allocated' => (int) $validated['new_quantity']
            ]);

            // Update variant overall quantity - FIXED LOGIC
            if ($validated['adjust_amount'] > 0) {
                // Moving FROM overall TO branch: decrease overall
                $newOverallQuantity = $item->variant->overal_quantity_at_hand - $validated['adjust_amount'];
            } else {
                // Moving FROM branch BACK TO overall: increase overall
                $newOverallQuantity = $item->variant->overal_quantity_at_hand + abs($validated['adjust_amount']);
            }
            
            $item->variant->overal_quantity_at_hand = max(0, $newOverallQuantity);
            $item->variant->save();

            // Determine the direction for notes
            $direction = $validated['adjust_amount'] > 0 
                ? 'from overall stock to branch' 
                : 'from branch back to overall stock';
            
            $action = $validated['adjust_amount'] > 0 ? 'Added' : 'Returned';

            // ✅ Record adjustment (audit trail)
            InventoryAdjustments::create([
                'quantity_before' => (int) $validated['current_quantity'],
                'quantity_after'  => (int) $validated['new_quantity'],
                'reason'          => 'stock_adjustment',
                'notes'           => $action . ' ' . abs($validated['adjust_amount']) . ' units ' . $direction . ' for ' . $item->variant->name,
                'inventory_id'    => $item->id,
                'created_by'      => auth()->id() ?? null,
                'tenant_id'       => $item->tenant_id,
            ]);

            // ✅ Record transaction (movement)
            InventoryTransactions::create([
                'quantity'       => (int) $validated['adjust_amount'], // Keep original sign
                'reference_id'   => $item->id,
                'reference_type' => 'adjustment',
                'type'           => 'adjustment',
                'notes'          => $action . ' ' . abs($validated['adjust_amount']) . ' units ' . $direction,
                'inventory_id'   => $item->id,
                'created_by'     => auth()->id() ?? null,
                'tenant_id'      => $item->tenant_id,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'reload' => true,
                'refresh' => false,
                'componentId' => 'reloadStockComponent',
                'message' => __('passwords._stock_adjusted'),
                'redirect' => route('stocks.index'),
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
            ]);
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
        $user = Auth::user();
        $tenantId = $user->tenant_id;
                
        if (!$user->hasPermissionTo('transfer stock')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }
        
        // 1️⃣ Validate input with department-location relationship check
        $validated = $request->validate([
            'department_id'    => [
                'required',
                'integer',
                'exists:departments,id',
                function ($attribute, $value, $fail) use ($tenantId, $request) {
                    // Check if department exists and belongs to tenant
                    $department = Department::where('id', $value)
                                        ->where('tenant_id', $tenantId)
                                        ->first();
                    
                    if (!$department) {
                        $fail(__('pagination.department_invalid'));
                        return;
                    }
                    
                    // Check if department belongs to the selected location
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
            'current_quantity' => 'required|integer|min:0',
            'adjust_amount'    => 'required|integer|min:1',
        ], [
            'department_id.required' => __('pagination.department_required'),
            'location_id.required' => __('pagination.location_required'),
            'current_quantity.required' => __('pagination.current_quantity_required'),
            'current_quantity.integer' => __('pagination.current_quantity_integer'),
            'current_quantity.min' => __('pagination.current_quantity_min'),
            'adjust_amount.required' => __('pagination.adjust_amount_required'),
            'adjust_amount.integer' => __('pagination.adjust_amount_integer'),
            'adjust_amount.min' => __('pagination.adjust_amount_min'),
        ]);

        $adjustAmount = (int) $validated['adjust_amount'];
        $current      = (int) $validated['current_quantity'];

        // 2️⃣ Check if adjust amount exceeds current quantity
        if ($adjustAmount > $current) {
            return response()->json([
                'success' => false,
                'message' => __('pagination.max_quantity_reached')
            ]);
        }

        // 3️⃣ Source inventory item
        $sourceItem = InventoryItems::where('id', $id)
                        ->where('tenant_id', $tenantId)
                        ->first();

        if (!$sourceItem) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ]);
        }

        // 4️⃣ If source and target are same location & department, do nothing
        if ($sourceItem->location_id == $validated['location_id'] &&
            $sourceItem->department_id == $validated['department_id']) {
            
            return response()->json([
                'success' => false,
                'message' =>  __('passwords.stock_already_present'),
            ]);
        }

        // 5️⃣ Find target inventory item
        $targetItem = InventoryItems::where('variant_id', $sourceItem->variant_id)
            ->where('location_id', $validated['location_id'])
            ->where('department_id', $validated['department_id'])
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$targetItem) {
            return response()->json([
                'success' => false,
                'message' => __('passwords.create_inv_first')
            ]);
        }

        // 6️⃣ Update target stock
        $targetBefore = $targetItem->quantity_allocated;
        $targetItem->quantity_allocated += $adjustAmount;
        $targetItem->save();
        $targetAfter = $targetItem->quantity_allocated;

        // 7️⃣ Update source stock
        $sourceBefore = $sourceItem->quantity_allocated;
        $sourceItem->quantity_allocated -= $adjustAmount;
        $sourceItem->save();
        $sourceAfter = $sourceItem->quantity_allocated;

        // 8️⃣ Log adjustments (audit trail)
        InventoryAdjustments::create([
            'inventory_id'    => $sourceItem->id,
            'quantity_before' => $sourceBefore,
            'quantity_after'  => $sourceAfter,
            'reason'          => 'stock_transfer',
            'notes'           => 'Transferred ' . $adjustAmount . ' units to location #' . $validated['location_id'] . ', department #' . $validated['department_id'],
            'created_by'      => auth()->id() ?? null,
            'tenant_id'       => $sourceItem->tenant_id,
        ]);

        InventoryAdjustments::create([
            'inventory_id'    => $targetItem->id,
            'quantity_before' => $targetBefore,
            'quantity_after'  => $targetAfter,
            'reason'          => 'stock_transfer',
            'notes'           => 'Received ' . $adjustAmount . ' units from inventory #' . $sourceItem->id,
            'created_by'      => auth()->id() ?? null,
            'tenant_id'       => $targetItem->tenant_id,
        ]);

        // 9️⃣ Log transactions (movement)
        InventoryTransactions::create([
            'inventory_id'   => $sourceItem->id,
            'quantity'       => -$adjustAmount,
            'reference_id'   => $targetItem->id,
            'reference_type' => 'transfer',
            'type'           => 'transfer_out',
            'notes'          => 'Transferred ' . $adjustAmount . ' units to inventory #' . $targetItem->id,
            'created_by'     => auth()->id() ?? null,
            'tenant_id'      => $sourceItem->tenant_id,
        ]);

        InventoryTransactions::create([
            'inventory_id'   => $targetItem->id,
            'quantity'       => $adjustAmount,
            'reference_id'   => $sourceItem->id,
            'reference_type' => 'transfer',
            'type'           => 'transfer_in',
            'notes'          => 'Received ' . $adjustAmount . ' units from inventory #' . $sourceItem->id,
            'created_by'     => auth()->id() ?? null,
            'tenant_id'      => $targetItem->tenant_id,
        ]);

        return response()->json([
            'success' => true,
            'reload' => true,
            'refresh' => false,
            'componentId' => 'reloadStockComponent',
            'message' => __('passwords.stock_transfer_success'),
            'redirect' => route('stocks.index'),
        ]);
    }


}
