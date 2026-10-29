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
            abort(403, __('payments.not_authorized'));
        }

        return view('promotion.tax-index', [
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

        if (!$user->hasPermissionTo('view tax')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }

        $perPage = (int) $request->get('per_page', 15);
        if (!in_array($perPage, [15, 25, 50, 100], true)) $perPage = 15;

        $query = Tax::with('taxCreater:id,name')
            ->where('tenant_id', $tenantId);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")
                ->orWhere('rate', 'like', "%{$search}%")
                ->orWhere('type', 'like', "%{$search}%")
                ->orWhereHas('taxCreater', fn($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', (int) $request->input('status'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        $all = (clone $query)->get(['id', 'is_active', 'is_withholding_tax']);

        $summary = [
            'total'       => $all->count(),
            'active'      => $all->where('is_active', 1)->count(),
            'inactive'    => $all->where('is_active', 0)->count(),
            'withholding' => $all->where('is_withholding_tax', 1)->count(),
        ];

        $paginator = $query->latest()->paginate($perPage);

        $rows = collect($paginator->items())->map(fn(Tax $t) => [
            'id'                 => $t->id,
            'name'               => $t->name,
            'code'               => $t->code,
            'rate'               => (float) $t->rate,
            'type'               => $t->type,
            'is_active'          => (bool) $t->is_active,
            'is_withholding_tax' => (bool) $t->is_withholding_tax,
            'creator_name'       => $t->taxCreater?->name,
            'created_at'         => optional($t->created_at)->format('d M Y'),
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

        if (!$user->hasPermissionTo('view tax')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }

        $tax = Tax::with('taxCreater:id,name,email')
            ->where('tenant_id', $tenantId)
            ->find($id);

        if (!$tax) {
            return response()->json(['success' => false, 'message' => __('auth.tax_not_found')], 404);
        }

        // Usage counts
        $productCount = DB::table('tax_product')->where('tax_id', $tax->id)->count();
        $variantCount = DB::table('variant_taxes')->where('tax_id', $tax->id)->count();

        $can = [
            'can_edit'   => $user->can('edit tax'),
            'can_delete' => $user->can('delete tax'),
            'can_toggle' => $user->can('update tax'),
        ];

        return response()->json([
            'success' => true,
            'tax'     => array_merge([
                'id'                 => $tax->id,
                'name'               => $tax->name,
                'code'               => $tax->code,
                'rate'               => (float) $tax->rate,
                'type'               => $tax->type,
                'is_active'          => (bool) $tax->is_active,
                'is_withholding_tax' => (bool) $tax->is_withholding_tax,

                'product_count'      => $productCount,
                'variant_count'      => $variantCount,
                'usage_count'        => $productCount + $variantCount,

                'creator_name'       => $tax->taxCreater?->name,
                'creator_email'      => $tax->taxCreater?->email,
                'created_at'         => optional($tax->created_at)->format('d M Y, H:i'),
                'updated_at'         => optional($tax->updated_at)->format('d M Y, H:i'),
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
            'rate' => 'required|numeric|min:0',
            'type' => 'required|in:percentage,fixed',
            'is_withholding_tax' => 'sometimes|boolean',
        ]);

        $tax = Tax::create([
            'name' => $request->name,
            'code' => $request->code,
            'rate' => $request->rate,
            'type' => $request->type,
            'is_withholding_tax' => $request->has('is_withholding_tax') ? true : false,
            'created_by' => $user->id,
            'tenant_id' => $tenantId,
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
                }),
            ],
            'code' => [
                'required',
                'string',
                'max:6',
                'min:5',
                Rule::unique('taxes')->where(function ($query) use ($tenantId, $id) {
                    return $query->where('tenant_id', $tenantId)
                            ->where('id', '!=', $id);
                }),
            ],
            'rate' => 'required|numeric|min:0',
            'type' => 'required|in:percentage,fixed',
            'is_withholding_tax' => 'sometimes|boolean',
        ]);

        $tax->update([
            'name' => $request->name,
            'code' => $request->code,
            'rate' => $request->rate,
            'type' => $request->type,
            'is_withholding_tax' => $request->has('is_withholding_tax') ? true : false,
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
