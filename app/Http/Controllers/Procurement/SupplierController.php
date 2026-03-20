<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{ Supplier, PurchaseOrder, Product };
use Illuminate\Support\Facades\{ Mail, Auth, DB };
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
     * Store a newly created supplier in storage.
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
            // Identity
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('suppliers')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId);
                })
            ],
            'trading_name' => 'nullable|string|max:150',
            'supplier_type' => 'required|in:individual,company,government,ngo,foreign',
            'is_active' => 'sometimes|boolean',
            'supplier_code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('suppliers')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId);
                })
            ],

            // Contact
            'contact_person' => 'nullable|string|max:100',
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('suppliers')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId);
                })
            ],
            'phone' => 'nullable|string|max:50',
            'phone_secondary' => 'nullable|string|max:50',
            'website' => 'nullable|string|max:255|url',

            // Address
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country_code' => 'nullable|string|size:2',

            // Tax & Compliance
            'tax_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('suppliers')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId);
                })
            ],
            'is_vat_registered' => 'sometimes|boolean',
            'vat_number' => 'nullable|string|max:50|required_if:is_vat_registered,1',
            'withholding_tax_applicable' => 'sometimes|boolean',
            'withholding_tax_rate' => 'nullable|numeric|min:0|max:100|required_if:withholding_tax_applicable,1',
            'withholding_tax_exemption_ref' => 'nullable|string|max:100',
            'withholding_tax_exemption_expiry' => 'nullable|date|after:today',

            // Banking
            'bank_name' => 'nullable|string|max:100',
            'bank_branch' => 'nullable|string|max:100',
            'bank_account_name' => 'nullable|string|max:150',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_swift_code' => 'nullable|string|max:20',
            'mobile_money_number' => 'nullable|string|max:50',
            'mobile_money_provider' => 'nullable|string|max:50|required_with:mobile_money_number',

            // Payment Terms
            'payment_terms_days' => 'nullable|integer|min:0|max:365',
            'payment_terms_type' => 'nullable|in:net,cod,prepaid,installment',
            'preferred_payment_method' => 'nullable|in:bank_transfer,mobile_money,cash,cheque,other',
            'credit_limit' => 'nullable|numeric|min:0',

            // Classification
            'category' => 'nullable|string|max:100',
            'risk_level' => 'nullable|in:low,medium,high',
            'currency_code' => 'nullable|string|size:3',

            // Notes
            'notes' => 'nullable|string|max:1000',
        ]);

        // Check maximum suppliers limit
        $currentSupplierCount = Supplier::where('tenant_id', $tenantId)->count();
        $maxSuppliers = tenant_setting($tenantId, 'max_suppliers', 50);

        if ($currentSupplierCount >= $maxSuppliers) {
            return response()->json([
                'success' => false,
                'message' => __('auth.maximum_suppliers_reached', ['max' => $maxSuppliers]),
            ]);
        }

        // Set defaults
        $validated['tenant_id'] = $tenantId;
        $validated['created_by'] = $user->id;
        
        // Handle boolean fields
        $validated['is_active'] = $request->has('is_active');
        $validated['is_vat_registered'] = $request->has('is_vat_registered');
        $validated['withholding_tax_applicable'] = $request->has('withholding_tax_applicable');
        
        // Convert credit limit to smallest currency unit if provided
        if (isset($validated['credit_limit']) && $validated['credit_limit'] !== null) {
            $validated['credit_limit'] = to_base_currency($validated['credit_limit']);
        }

        DB::beginTransaction();
        
        try {
            $supplier = Supplier::create($validated);
            
            DB::commit();

            return response()->json([
                'success' => true,
                'reload' => true,
                'componentId' => 'reloadSupplierComponent',
                'refresh' => false,
                'message' => __('auth._created'),
                'redirect' => route('suppliers.index'),
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating supplier', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => __('auth.error_creating') . ': ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified supplier in storage.
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

        $validated = $request->validate([
            // Identity
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('suppliers')->where(function ($query) use ($tenantId, $id) {
                    return $query->where('tenant_id', $tenantId)
                            ->where('id', '!=', $id);
                }),
            ],
            'trading_name' => 'nullable|string|max:150',
            'supplier_type' => 'required|in:individual,company,government,ngo,foreign',
            'is_active' => 'sometimes|boolean',
            'supplier_code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('suppliers')->where(function ($query) use ($tenantId, $id) {
                    return $query->where('tenant_id', $tenantId)
                            ->where('id', '!=', $id);
                }),
            ],

            // Contact
            'contact_person' => 'nullable|string|max:100',
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('suppliers')->where(function ($query) use ($tenantId, $id) {
                    return $query->where('tenant_id', $tenantId)
                            ->where('id', '!=', $id);
                }),
            ],
            'phone' => 'nullable|string|max:50',
            'phone_secondary' => 'nullable|string|max:50',
            'website' => 'nullable|string|max:255|url',

            // Address
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country_code' => 'nullable|string|size:2',

            // Tax & Compliance
            'tax_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('suppliers')->where(function ($query) use ($tenantId, $id) {
                    return $query->where('tenant_id', $tenantId)
                            ->where('id', '!=', $id);
                }),
            ],
            'is_vat_registered' => 'sometimes|boolean',
            'vat_number' => 'nullable|string|max:50|required_if:is_vat_registered,1',
            'withholding_tax_applicable' => 'sometimes|boolean',
            'withholding_tax_rate' => 'nullable|numeric|min:0|max:100|required_if:withholding_tax_applicable,1',
            'withholding_tax_exemption_ref' => 'nullable|string|max:100',
            'withholding_tax_exemption_expiry' => 'nullable|date|after:today',

            // Banking
            'bank_name' => 'nullable|string|max:100',
            'bank_branch' => 'nullable|string|max:100',
            'bank_account_name' => 'nullable|string|max:150',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_swift_code' => 'nullable|string|max:20',
            'mobile_money_number' => 'nullable|string|max:50',
            'mobile_money_provider' => 'nullable|string|max:50|required_with:mobile_money_number',

            // Payment Terms
            'payment_terms_days' => 'nullable|integer|min:0|max:365',
            'payment_terms_type' => 'nullable|in:net,cod,prepaid,installment',
            'preferred_payment_method' => 'nullable|in:bank_transfer,mobile_money,cash,cheque,other',
            'credit_limit' => 'nullable|numeric|min:0',

            // Classification
            'category' => 'nullable|string|max:100',
            'risk_level' => 'nullable|in:low,medium,high',
            'currency_code' => 'nullable|string|size:3',

            // Notes
            'notes' => 'nullable|string|max:1000',
        ]);

        // Handle boolean fields
        $validated['is_active'] = $request->has('is_active');
        $validated['is_vat_registered'] = $request->has('is_vat_registered');
        $validated['withholding_tax_applicable'] = $request->has('withholding_tax_applicable');
        
        // Convert credit limit to smallest currency unit if provided
        if (isset($validated['credit_limit']) && $validated['credit_limit'] !== null) {
            $validated['credit_limit'] = to_base_currency($validated['credit_limit']);
        }

        DB::beginTransaction();
        
        try {
            $supplier->update($validated);
            
            DB::commit();

            return response()->json([
                'success' => true,
                'reload' => true,
                'componentId' => 'reloadSupplierComponent',
                'refresh' => false,
                'message' => __('auth._updated'),
                'redirect' => route('suppliers.index'),
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating supplier', [
                'error' => $e->getMessage(),
                'id' => $id,
                'tenant_id' => $tenantId
            ]);
            
            return response()->json([
                'success' => false,
                'message' => __('auth.error_updating') . ': ' . $e->getMessage(),
            ], 500);
        }
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
        $hasPurchaseOrders = PurchaseOrder::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->exists();

        if ($hasPurchaseOrders) {
            return response()->json([
                'success' => false,
                'message' => __('auth.supplier_has_purchase_orders'),
            ]);
        }

        // Check if supplier is attached to any products
        $hasProducts = Product::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->orWhereHas('variants', function ($query) use ($id, $tenantId) {
                $query->where('id', $id)
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
        // $hasInvoices = Invoice::where('id', $id)
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
