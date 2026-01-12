<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{ UnitOfMeasure, ProductVariant};
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UnitOfMeasureController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Build the query
        $query = UnitOfMeasure::query();
        
        // If user is NOT super_admin, filter by tenant
        if (!$user->hasRole('super_admin')) {
            $query->where('tenant_id', current_tenant_id());
        }
        
        $uoms = $query->latest()->get();
        
        $bladeToReload = $request->query('bladeFileToReload');
        switch ($bladeToReload) {
            case 'uomIndexTable':
                return view('unit-of-measure.partials.uom-component', [
                    'all_uoms' => $uoms,
                ]);
            default:
                return view('unit-of-measure.index', [
                    'all_uoms' => $uoms,
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

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('unit_of_measures')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId);
                })
            ],
            'symbol' => 'required',
        ]);

        $uom = UnitOfMeasure::create([
            'name' => $request->name,
            'symbol' => $request->symbol,
            'created_by' => $user->id,
            'tenant_id' => $tenantId,
        ]);

        return response()->json([
            'success' => true,
            'reload' => true,
            'componentId' => 'uomIndexTable',
            'refresh' => false,
            'message' => __('auth._created'),
            'redirect' => route('uom.index'),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(UnitOfMeasure $unitOfMeasure)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(UnitOfMeasure $unitOfMeasure)
    {
        //
    }

    /**
     * Update the specified resource in storage.
    */
    public function update(Request $request, $id)
    {
        $uOM = UnitOfMeasure::find($id);
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        \Log::info($uOM);
        
        $request->validate([
            'name' => [
                'required',
                'max:255',
                Rule::unique('unit_of_measures')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId);
                })->ignore($uOM->id),
            ],
            'symbol' => 'required',
        ]);

        $uOM->update([
            'name' => $request->name,
            'symbol' => $request->symbol,
            'created_by' => $user->id
        ]);

        return response()->json([
            'success' => true,
            'reload' => true,
            'componentId' => 'uomIndexTable',
            'refresh' => false,
            'message' => __('auth._updated'),
            'redirect' => route('uom.index'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        $unitOfMeasure = UnitOfMeasure::find($id);
        
        // Check if UOM exists
        if (!$unitOfMeasure) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ]);
        }

        // Check if UOM belongs to current tenant
        if ($unitOfMeasure->tenant_id !== $tenantId) {
            return response()->json([
                'success' => false,
                'message' => __('auth.unauthorized_access'),
            ]);
        }

        // Check if UOM is active
        if ($unitOfMeasure->isActive === 1) {
            return response()->json([
                'success' => false,
                'message' => __('auth.still_active'),
            ]);
        }

        // Check if UOM is attached to any product variants as weight_unit
        $attachedToVariants = ProductVariant::where('weight_unit', $id)
            ->exists();

        if ($attachedToVariants) {
            return response()->json([
                'success' => false,
                'message' => __('auth.uom_attached_to_variants'),
            ]);
        }

        $unitOfMeasure->delete();
        
        return response()->json([
            'success' => true,
            'reload' => true,
            'componentId' => 'uomIndexTable',
            'refresh' => false,
            'message' => __('auth._deleted'),
            'redirect' => route('uom.index'),
        ]);
    }

    public function changeUOMStatus(Request $request, $id) 
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        // Validate the request data for status
        $validated = $request->validate([
            'status' => 'required|in:1,0',  // Ensures only 'active' or 'inactive' are allowed
        ]);

        // Find the UOM by ID
        $uom = UnitOfMeasure::find($id);

        if (!$uom) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ]);
        }

        // Check if UOM belongs to current tenant
        if ($uom->tenant_id !== $tenantId) {
            return response()->json([
                'success' => false,
                'message' => __('auth.unauthorized_access'),
            ]);
        }

        $uom->isActive = $validated['status']; 
        
        if ($uom->save()) {
            $statusMessage = $validated['status'] == 1 ? __('auth.activated') : __('auth.deactivated');
            
            return response()->json([
                'success' => true,
                'reload' => true,
                'componentId' => 'uomIndexTable',
                'refresh' => false,
                'message' => $statusMessage,
                'redirect' => route('uom.index'),
            ]);
        }

        // If status update failed
        return response()->json([
            'success' => false,
            'message' => __('auth.update_failed'),
        ]);
    }
}
