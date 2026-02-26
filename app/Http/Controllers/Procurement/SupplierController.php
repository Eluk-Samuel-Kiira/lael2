<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{ Supplier, PurchaseOrder };
use Illuminate\Support\Facades\{ Mail, Auth };
use Illuminate\Validation\Rule;


class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasPermissionTo('view supplier')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }
        
        // Build the query
        $query = Supplier::query();
        
        // If user is NOT super_admin, filter by tenant
        if (!$user->hasRole('super_admin')) {
            $query->where('tenant_id', current_tenant_id());
        }
        
        $suppliers = $query->latest()->get();

        $bladeToReload = $request->query('bladeFileToReload');
        switch ($bladeToReload) {
            case 'reloadSupplierComponent':
                return view('procurement.supplier.component', [
                    'all_suppliers' => $suppliers,
                ]);
            default:
                return view('procurement.supplier-index', [
                    'all_suppliers' => $suppliers,
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
        if (!$user->hasPermissionTo('create supplier')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('suppliers')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId);
                })
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('suppliers')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId);
                })
            ],
            'tax_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('suppliers')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId);
                })
            ],
            'contact_person' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country_code' => 'nullable|string|size:2',
            'payment_terms' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        // Check maximum suppliers limit
        $currentSupplierCount = Supplier::where('tenant_id', $tenantId)->count();
        $maxSuppliers = tenant_setting($tenantId, 'max_suppliers', 50); // Default to 50 if not set

        if ($currentSupplierCount >= $maxSuppliers) {
            return response()->json([
                'success' => false,
                'message' => __('auth.maximum_suppliers_reached', ['max' => $maxSuppliers]),
            ]);
        }

        $validated['tenant_id'] = $tenantId;
        $validated['created_by'] = $user->id;

        $supplier = Supplier::create($validated);

        return response()->json([
            'success' => true,
            'reload' => true,
            'componentId' => 'reloadSupplierComponent',
            'refresh' => false,
            'message' => __('auth._created'),
            'redirect' => route('suppliers.index'),
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
        if (!$user->hasPermissionTo('edit supplier')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        // Find supplier and ensure it belongs to tenant
        $supplier = Supplier::where('id', $id)
                        ->where('tenant_id', $tenantId)
                        ->first();

        if (!$supplier) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ]);
        }

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('suppliers')->where(function ($query) use ($tenantId, $id) {
                    return $query->where('tenant_id', $tenantId)
                            ->where('id', '!=', $id);
                })->ignore($supplier->id),
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('suppliers')->where(function ($query) use ($tenantId, $id) {
                    return $query->where('tenant_id', $tenantId)
                            ->where('id', '!=', $id);
                })->ignore($supplier->id),
            ],
            'tax_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('suppliers')->where(function ($query) use ($tenantId, $id) {
                    return $query->where('tenant_id', $tenantId)
                            ->where('id', '!=', $id);
                })->ignore($supplier->id),
            ],
            'contact_person' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country_code' => 'nullable|string|size:2',
            'payment_terms' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $supplier->update([
            'name' => $request->name,
            'email' => $request->email,
            'tax_number' => $request->tax_number,
            'contact_person' => $request->contact_person,
            'phone' => $request->phone,
            'address' => $request->address,
            'city' => $request->city,
            'state' => $request->state,
            'postal_code' => $request->postal_code,
            'country_code' => $request->country_code,
            'payment_terms' => $request->payment_terms,
            'notes' => $request->notes,
            // Don't update tenant_id or created_by - they should remain the same
        ]);

        return response()->json([
            'success' => true,
            'reload' => true,
            'componentId' => 'reloadSupplierComponent', // Update to match your component ID
            'refresh' => false,
            'message' => __('auth._updated'),
            'redirect' => route('suppliers.index'),
        ]);
    }    


    
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        if (!$user->hasPermissionTo('delete supplier')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        $supplier = Supplier::find($id);
        
        // Check if supplier exists and belongs to tenant
        if (!$supplier) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ]);
        }

        if ($supplier->tenant_id !== $tenantId) {
            return response()->json([
                'success' => false,
                'message' => __('auth.unauthorized_access'),
            ]);
        }

        if ($supplier->is_active === 1) {
            return response()->json([
                'success' => false,
                'message' => __('auth.still_active'),
            ]);
        }

        // Check if supplier has any purchase orders
        $hasPurchaseOrders = PurchaseOrder::where('supplier_id', $id)
            ->where('tenant_id', $tenantId)
            ->exists();

        if ($hasPurchaseOrders) {
            return response()->json([
                'success' => false,
                'message' => __('auth.supplier_has_purchase_orders'),
            ]);
        }

        // Check if supplier is attached to any products
        $hasProducts = Product::where('supplier_id', $id)
            ->where('tenant_id', $tenantId)
            ->orWhereHas('variants', function ($query) use ($id, $tenantId) {
                $query->where('supplier_id', $id)
                    ->where('tenant_id', $tenantId);
            })
            ->exists();

        if ($hasProducts) {
            return response()->json([
                'success' => false,
                'message' => __('auth.supplier_has_products'),
            ]);
        }

        // Check if supplier has any invoices
        // $hasInvoices = Invoice::where('supplier_id', $id)
        //     ->where('tenant_id', $tenantId)
        //     ->exists();

        // if ($hasInvoices) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => __('auth.supplier_has_invoices'),
        //     ]);
        // }

        $supplier->delete();
        
        return response()->json([
            'success' => true,
            'reload' => true,
            'componentId' => 'reloadSupplierComponent',
            'refresh' => false,
            'message' => __('auth._deleted'),
            'redirect' => route('suppliers.index'),
        ]);
    }


    public function updateSupplierStatus(Request $request, $id) 
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        if (!$user->hasPermissionTo('update supplier')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }
        
        // Validate the request data for status
        $validated = $request->validate([
            'status' => 'required|in:1,0',
        ]);
        
        $supplier = Supplier::where('id', $id)
                        ->where('tenant_id', $tenantId)
                        ->first();
        
        if ($supplier) {
            $supplier->is_active = $validated['status']; 
            if ($supplier->save()) {  
                return response()->json([
                    'success' => true,
                    'reload' => true,
                    'refresh' => false,
                    'componentId' => 'reloadSupplierComponent',
                    'message' => __('auth._updated'),
                    'redirect' => route('suppliers.index'),
                ]);
            }
        }
        
        // If supplier not found or status update failed
        return response()->json([
            'success' => false,
            'message' => __('auth._not_found'),
        ]);
    }
}
