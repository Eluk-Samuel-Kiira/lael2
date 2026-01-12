<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\InventoryItems;
use App\Models\InventoryAdjustments;
use App\Models\InventoryTransactions;

class InventoryAdjustmentsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Retrieve only those items that have its variants is active
        $items = InventoryItems::with(['variant', 'itemCreater', 'itemLocation'])
                ->whereHas('variant', function ($query) {
                $query->where('is_active', 1);
            })
            ->orderBy('department_id', 'asc')
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
        $validated = $request->validate([
            'overal_quantity_at_hand' => 'required',
            'current_quantity' => 'required',
            'adjust_amount' => 'required',
            'new_quantity' => 'required',
        ]);

        $item = InventoryItems::findOrFail($id);
        // \Log::info($item);

        // update adjustment
        $item->update([
            'quantity_allocated' => (int) $validated['new_quantity']
        ]);

        if ($item->variant) {
            // \Log::info($item->variant);
            
            // Update overall quantity
            $item->variant->overal_quantity_at_hand = max(0, $validated['overal_quantity_at_hand']);
            $item->variant->save();

            // ✅ Record adjustment (audit trail) — use $item->id here
            InventoryAdjustments::create([
                'quantity_before' => (int) $validated['current_quantity'],
                'quantity_after'  => (int) $validated['new_quantity'],
                'reason'          => 'stock_adjustment',
                'notes'           => 'Adding or removing stock quantity of product_variant #' . $item->variant->name,
                'inventory_id'    => $item->id, // 🔹 use inventory item ID, not variant ID
                'created_by'      => auth()->id() ?? null,
                'tenant_id'       => $item->tenant_id,
            ]);

            // ✅ Record transaction (movement) — also use $item->id
            InventoryTransactions::create([
                'quantity'       => (int) $validated['adjust_amount'],
                'reference_id'   => $item->id,
                'reference_type' => 'adjustment',
                'type'           => 'adjustment',
                'notes'          => 'Adjusted ' . (int) $validated['adjust_amount'] . ' units of ' . $item->variant->name,
                'inventory_id'   => $item->id, // 🔹 use inventory item ID
                'created_by'     => auth()->id() ?? null,
                'tenant_id'      => $item->tenant_id,
            ]);
        }


        return response()->json([
            'success' => true,
            'reload' => true,
            'refresh' => false,
            'componentId' => 'reloadStockComponent',
            'message' => __('passwords._stock_adjusted'),
            'redirect' => route('stocks.index'),
        ]);

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
        // 1️⃣ Validate input
        $validated = $request->validate([
            'department_id'    => 'required|integer',
            'location_id'      => 'required|integer',
            'current_quantity' => 'required|integer|min:0',
            'adjust_amount'    => 'required|integer|min:1',
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
        $sourceItem = InventoryItems::findOrFail($id);

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
