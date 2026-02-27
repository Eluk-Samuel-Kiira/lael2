<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\InventoryItems;
use App\Models\{ ProductVariant, Department, Location };
use Illuminate\Support\Str;
use Illuminate\Support\Facades\{ Auth, DB };
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
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }
        
        // Build the query
        $query = InventoryItems::with(['variant', 'itemCreater', 'itemLocation']);
        
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
        
        $items = $query->orderBy('department_id', 'asc')
                    ->latest()
                    ->get();

        $bladeToReload = $request->query('bladeFileToReload');
        switch ($bladeToReload) {
            case 'reloadItemComponent':
                return view('store.inventory-items.component', [
                    'items' => $items,
                ]);
            default:
                return view('store.items-index', [
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

        // Fetch live variant stock from DB and ensure it belongs to tenant
        $variant = $item->variant()->where('tenant_id', $tenantId)->first();
        
        if (!$variant) {
            return response()->json([
                'success' => false,
                'message' => __('auth.variant_not_found'),
            ]);
        }

        // Validate request
        $validated = $request->validate([
            'department_id'       => [
                'required',
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
            'location_id'         => [
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
            'expiry_date'         => ['nullable','date','after_or_equal:today'],
            'quantity_on_hand'    => ['required','integer','min:0'],
            'quantity_allocated'  => [
                'required',
                'integer',
                'min:0',
                function ($attribute, $value, $fail) use ($variant, $item) {
                    $available = $variant->overal_quantity_at_hand + $item->quantity_allocated;
                    if ($value > $available) {
                        $fail(__('pagination.allocated_not_greater_than_at_hand'));
                    }
                }
            ],
        ]);

        // Add unique rule separately
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
            // Update inventory item
            $item->update($validated);

            // Adjust variant stock manually
            $variant->overal_quantity_at_hand = 
                ($variant->overal_quantity_at_hand + $item->quantity_allocated) - $validated['quantity_allocated'];

            $variant->save();

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
                'message' => __('auth.update_failed'),
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
}
