<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\{ Auth, DB };
use Illuminate\Validation\Rule;
use App\Models\{ Tax, Product, ProductVariant };

class TaxController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasPermissionTo('view tax')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }
        
        // Build the query
        $query = Tax::query();
        
        // If user is NOT super_admin, filter by tenant
        if (!$user->hasRole('super_admin')) {
            $query->where('tenant_id', $user->tenant_id);
        }
        
        $taxes = $query->latest()->get();

        $bladeToReload = $request->query('bladeFileToReload');
        switch ($bladeToReload) {
            case 'reloadTaxComponent':
                return view('promotion.tax.component', [
                    'all_taxes' => $taxes,
                ]);
            default:
                return view('promotion.tax-index', [
                    'all_taxes' => $taxes,
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
        if (!$user->hasPermissionTo('create tax')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:55',
                Rule::unique('taxes')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId);
                })
            ],
            'code' => [
                'required',
                'string',
                'max:6',
                'min:5',
                Rule::unique('taxes')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId);
                })
            ],
            'rate' => 'required',
            'type' => 'required',
        ]);

        $tax = Tax::create([
            'name' => $request->name,
            'code' => $request->code,
            'rate' => $request->rate,
            'type' => $request->type,
            'created_by' => $user->id,
            'tenant_id' => $tenantId, // Use dynamic tenant_id
        ]);

        return response()->json([
            'success' => true,
            'reload' => true,
            'componentId' => 'reloadTaxComponent',
            'refresh' => false,
            'message' => __('auth._created'),
            'redirect' => route('tax.index'),
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
        if (!$user->hasPermissionTo('edit tax')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        $tax = Tax::where('id', $id)
                ->where('tenant_id', $tenantId)
                ->first();

        // Check if tax exists and belongs to tenant
        if (!$tax) {
            return response()->json([
                'success' => false,
                'message' => __('auth.tax_not_found'),
            ]);
        }

        $request->validate([
            'name' => [
                'required',
                'max:255',
                Rule::unique('taxes')->where(function ($query) use ($tenantId, $id) {
                    return $query->where('tenant_id', $tenantId)
                            ->where('id', '!=', $id);
                })->ignore($tax->id),
            ],
            'code' => [
                'required',
                'string',
                'max:6',
                'min:5',
                Rule::unique('taxes')->where(function ($query) use ($tenantId, $id) {
                    return $query->where('tenant_id', $tenantId)
                            ->where('id', '!=', $id);
                })->ignore($tax->id),
            ],
            'rate' => 'required',
            'type' => 'required',
        ]);

        $tax->update([
            'name' => $request->name,
            'code' => $request->code,
            'rate' => $request->rate,
            'type' => $request->type,
            'created_by' => $user->id,
            // Don't update tenant_id on update
        ]);

        return response()->json([
            'success' => true,
            'reload' => true,
            'componentId' => 'reloadTaxComponent',
            'refresh' => false,
            'message' => __('auth._updated'),
            'redirect' => route('tax.index'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('delete tax')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }
        // Find tax and ensure it belongs to the tenant
        $tax = Tax::where('id', $id)
                ->where('tenant_id', $tenantId)
                ->first();

        if (!$tax) {
            return response()->json([
                'success' => false,
                'message' => __('auth.tax_not_found'),
            ]);
        }

        // Check if tax is active
        if ($tax->is_active === 1) {
            return response()->json([
                'success' => false,
                'message' => __('auth.still_active'),
            ]);
        }

        // Check if tax is attached to any products through the pivot table
        $attachedToProducts = DB::table('tax_product')
            ->where('tax_id', $id)
            ->exists();

        if ($attachedToProducts) {
            return response()->json([
                'success' => false,
                'message' => __('auth.tax_attached_to_products'),
            ]);
        }

        // Check if tax is attached to any product variants through the pivot table
        $attachedToVariants = DB::table('variant_taxes')
            ->where('tax_id', $id)
            ->exists();

        if ($attachedToVariants) {
            return response()->json([
                'success' => false,
                'message' => __('auth.tax_attached_to_variants'),
            ]);
        }

        // If all checks pass, delete the tax
        $tax->delete();
        
        return response()->json([
            'success' => true,
            'reload' => true,
            'componentId' => 'reloadTaxComponent',
            'refresh' => false,
            'message' => __('auth._deleted'),
            'redirect' => route('tax.index'),
        ]);
    }

    public function updateTaxStatus(Request $request, $id) 
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('update tax')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }
        // \Log::info($id);
        // Validate the request data for status
        $validated = $request->validate([
            'status' => 'required|in:1,0',  // Ensures only 'active' or 'inactive' are allowed
        ]);
        
        $tax = Tax::where('id', $id)->where('tenant_id', $tenantId)->first();
    
        if ($tax) {
            $tax->is_active = $validated['status']; 
            if ($tax->save()) {  // Save the user object
                return response()->json([
                    'success' => true,
                    'reload' => true,
                    'refresh' => false,
                    'componentId' => 'reloadTaxComponent',
                    'message' => __('auth._updated'),
                    'redirect' => route('tax.index'),
                ]);
            }
        }
    
        // If user not found or status update failed
        return response()->json([
            'success' => false,
            'message' => __('auth._not_found'),
        ]);
    }
}
