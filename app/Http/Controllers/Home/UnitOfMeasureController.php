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

        if (!$user->hasPermissionTo('view uom')) {
            abort(403, __('payments.not_authorized'));
        }

        return view('unit-of-measure.index', [
            'initialFilters' => [
                'search'   => (string) $request->get('search', ''),
                'per_page' => in_array((int) $request->get('per_page', 15), [15, 25, 50, 100], true)
                    ? (int) $request->get('per_page', 15)
                    : 15,
            ],
        ]);
    }

    public function data(Request $request)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('view uom')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }

        $perPage = (int) $request->get('per_page', 15);
        if (!in_array($perPage, [15, 25, 50, 100], true)) $perPage = 15;

        $query = UnitOfMeasure::where('tenant_id', $tenantId);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('symbol', 'like', "%{$search}%")
                ->orWhereHas('UOMCreater', fn($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('isActive', (int) $request->input('status'));
        }

        // Summary over the filtered set
        $all = (clone $query)->get(['id', 'isActive']);

        $summary = [
            'total'    => $all->count(),
            'active'   => $all->where('isActive', 1)->count(),
            'inactive' => $all->where('isActive', 0)->count(),
        ];

        $paginator = $query->latest()->paginate($perPage);

        $rows = collect($paginator->items())->map(fn(UnitOfMeasure $u) => [
            'id'         => $u->id,
            'name'       => $u->name,
            'symbol'     => $u->symbol,
            'is_active'  => (bool) $u->isActive,
            'creator'    => $u->UOMCreater?->name,
            'created_at' => optional($u->created_at)->format('d M Y, H:i'),
        ]);

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
        ]);
    }

    public function drawerData(Request $request, $id)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('view uom')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }

        $uom = UnitOfMeasure::with('UOMCreater:id,name,email')
            ->where('tenant_id', $tenantId)
            ->find($id);

        if (!$uom) {
            return response()->json(['success' => false, 'message' => __('auth._not_found')], 404);
        }

        // How many variants use this UOM
        $variantCount = ProductVariant::where('weight_unit', $uom->id)->count();

        $can = [
            'can_edit'   => $user->can('edit uom'),
            'can_delete' => $user->can('delete uom'),
            'can_toggle' => $user->can('update uom'),
        ];

        return response()->json([
            'success' => true,
            'uom'     => array_merge([
                'id'             => $uom->id,
                'name'           => $uom->name,
                'symbol'         => $uom->symbol,
                'is_active'      => (bool) $uom->isActive,
                'variant_count'  => $variantCount,
                'creator_name'   => $uom->UOMCreater?->name,
                'creator_email'  => $uom->UOMCreater?->email,
                'created_at'     => optional($uom->created_at)->format('d M Y, H:i'),
                'updated_at'     => optional($uom->updated_at)->format('d M Y, H:i'),
            ], $can),
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

        if (!$user->hasPermissionTo('create uom')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

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
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('edit uom')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }
        
        $uOM = UnitOfMeasure::where('id', $id)
                        ->where('tenant_id', $tenantId)
                        ->first();
        // \Log::info($uOM);
        
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
        

        if (!$user->hasPermissionTo('delete uom')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        $unitOfMeasure = UnitOfMeasure::where('id', $id)
                            ->where('tenant_id', $tenantId)
                            ->first();
        
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
        
        if (!$user->hasPermissionTo('update uom')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        // Validate the request data for status
        $validated = $request->validate([
            'status' => 'required|in:1,0',  // Ensures only 'active' or 'inactive' are allowed
        ]);

        // Find the UOM by ID
        $uom = UnitOfMeasure::where('id', $id)
                        ->where('tenant_id', $tenantId)
                        ->first();

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
