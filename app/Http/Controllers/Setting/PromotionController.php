<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\{ Auth, DB };
use Illuminate\Validation\Rule;
use App\Models\{ Promotion, User };

class PromotionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasPermissionTo('view promotion')) {
            abort(403, __('payments.not_authorized'));
        }

        return view('promotion.promotion-index', [
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

        if (!$user->hasPermissionTo('view promotion')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }

        $perPage = (int) $request->get('per_page', 15);
        if (!in_array($perPage, [15, 25, 50, 100], true)) $perPage = 15;

        $query = Promotion::with('Promotioncreator:id,name')
            ->where('tenant_id', $tenantId);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('discount_type', 'like', "%{$search}%")
                ->orWhere('discount_value', 'like', "%{$search}%")
                ->orWhereHas('Promotioncreator', fn($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', (int) $request->input('status'));
        }

        if ($request->filled('discount_type')) {
            $query->where('discount_type', $request->input('discount_type'));
        }

        $all = (clone $query)->get(['id', 'is_active', 'discount_type', 'end_date']);

        $summary = [
            'total'       => $all->count(),
            'active'      => $all->where('is_active', 1)->count(),
            'inactive'    => $all->where('is_active', 0)->count(),
            'expired'     => $all->filter(fn($p) => $p->end_date && $p->end_date->isPast())->count(),
        ];

        $paginator = $query->latest()->paginate($perPage);

        $rows = collect($paginator->items())->map(fn(Promotion $p) => [
            'id'             => $p->id,
            'name'           => $p->name,
            'discount_type'  => $p->discount_type,
            'discount_value' => (string) $p->discount_value,
            'start_date'     => optional($p->start_date)->format('Y-m-d'),
            'end_date'       => optional($p->end_date)->format('Y-m-d'),
            'start_date_human' => optional($p->start_date)->format('d M Y'),
            'end_date_human'   => optional($p->end_date)->format('d M Y'),
            'is_active'      => (bool) $p->is_active,
            'is_expired'     => $p->end_date ? $p->end_date->isPast() : false,
            'creator_name'   => $p->Promotioncreator?->name,
            'created_at'     => optional($p->created_at)->format('d M Y'),
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

        if (!$user->hasPermissionTo('view promotion')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }

        $promotion = Promotion::with('Promotioncreator:id,name,email')
            ->where('tenant_id', $tenantId)
            ->find($id);

        if (!$promotion) {
            return response()->json(['success' => false, 'message' => __('auth.promotion_not_found')], 404);
        }

        // How many products/variants are attached
        $productCount = DB::table('promotion_products')
            ->where('promotion_id', $promotion->id)
            ->count();

        $can = [
            'can_edit'   => $user->can('edit promotion'),
            'can_delete' => $user->can('delete promotion'),
            'can_toggle' => $user->can('update promotion'),
        ];

        $isExpired = $promotion->end_date && $promotion->end_date->isPast();

        return response()->json([
            'success'   => true,
            'promotion' => array_merge([
                'id'                => $promotion->id,
                'name'              => $promotion->name,
                'discount_type'     => $promotion->discount_type,
                'discount_value'    => (string) $promotion->discount_value,
                'start_date'        => optional($promotion->start_date)->format('Y-m-d'),
                'end_date'          => optional($promotion->end_date)->format('Y-m-d'),
                'start_date_human'  => optional($promotion->start_date)->format('d M Y'),
                'end_date_human'    => optional($promotion->end_date)->format('d M Y'),
                'is_active'         => (bool) $promotion->is_active,
                'is_expired'        => (bool) $isExpired,
                'product_count'     => $productCount,
                'usage_count'       => $productCount,
                'creator_name'      => $promotion->Promotioncreator?->name,
                'creator_email'     => $promotion->Promotioncreator?->email,
                'created_at'        => optional($promotion->created_at)->format('d M Y, H:i'),
                'updated_at'        => optional($promotion->updated_at)->format('d M Y, H:i'),
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

        if (!$user->hasPermissionTo('create promotion')) {
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
                Rule::unique('promotions')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId);
                })
            ],
            'discount_type' => 'required|string|in:fixed_amount,percentage,buy_x_get_y',
            'discount_value' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        $promotion = Promotion::create([
            'name' => $request->name,
            'discount_type' => $request->discount_type,
            'discount_value' => $request->discount_value,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'created_by' => $user->id,
            'tenant_id' => $tenantId, 
        ]);

        return response()->json([
            'success' => true,
            'reload' => true,
            'componentId' => 'reloadPromotionComponent',
            'refresh' => false,
            'message' => __('auth._created'),
            'redirect' => route('promotion.index'),
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

        if (!$user->hasPermissionTo('edit promotion')) {
            // abort(403, __('payments.not_authorized')); for reloading pages
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        // Find promotion and ensure it belongs to tenant
        $promotion = Promotion::where('id', $id)
                            ->where('tenant_id', $tenantId)
                            ->first();

        if (!$promotion) {
            return response()->json([
                'success' => false,
                'message' => __('auth.promotion_not_found'),
            ]);
        }

        // // ── CHECK IF PROMOTION IS EXPIRED ─────────────────────────────────────
        // if ($promotion->end_date && now()->gt($promotion->end_date)) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => __('auth.cannot_update_expired'),
        //         // 'error_code' => 'PROMOTION_EXPIRED',
        //         'expired_at' => $promotion->end_date->format('Y-m-d H:i:s'),
        //     ]);
        // }

        // // Also check if start_date is in the past but promotion is still active
        // if ($promotion->start_date && now()->lt($promotion->start_date)) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => __('auth.cannot_update_not_started'),
        //         // 'error_code' => 'PROMOTION_NOT_STARTED',
        //         'starts_at' => $promotion->start_date->format('Y-m-d H:i:s'),
        //     ]);
        // }

        $request->validate([
            'name' => [
                'required',
                'max:255',
                Rule::unique('promotions')->where(function ($query) use ($tenantId, $id) {
                    return $query->where('tenant_id', $tenantId)
                            ->where('id', '!=', $id);
                })->ignore($promotion->id),
            ],
            'discount_type' => 'required|string|in:fixed_amount,percentage,buy_x_get_y',
            'discount_value' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        $promotion->update([
            'name' => $request->name,
            'discount_type' => $request->discount_type,
            'discount_value' => $request->discount_value,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'created_by' => $user->id,
            // Don't update tenant_id on update
        ]);

        return response()->json([
            'success' => true,
            'reload' => true,
            'componentId' => 'reloadPromotionComponent',
            'refresh' => false,
            'message' => __('auth._updated'), // Fixed: should be _updated not _created
            'redirect' => route('promotion.index'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        if (!$user->hasPermissionTo('delete promotion')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        // Find promotion and ensure it belongs to tenant
        $promotion = Promotion::where('id', $id)
                            ->where('tenant_id', $tenantId)
                            ->first();

        if (!$promotion) {
            return response()->json([
                'success' => false,
                'message' => __('auth.promotion_not_found'),
            ]);
        }

        if ($promotion->is_active == 1) {
            return response()->json([
                'success' => false,
                'message' => __('auth.still_active'),
            ]);
        }

        // Check if promotion is attached to any products
        $attachedToProducts = DB::table('promotion_products')
            ->where('promotion_id', $id)
            ->exists();

        if ($attachedToProducts) {
            return response()->json([
                'success' => false,
                'message' => __('auth.promotion_attached_to_products'),
            ]);
        }

        $promotion->delete();
        
        return response()->json([
            'success' => true,
            'reload' => true,
            'componentId' => 'reloadPromotionComponent',
            'refresh' => false,
            'message' => __('auth._deleted'), // Fixed: should be _deleted not _updated
            'redirect' => route('promotion.index'),
        ]);
    }
    

    public function updatePromotionStatus(Request $request, $id) 
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        if (!$user->hasPermissionTo('update promotion')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }
        $validated = $request->validate([
            'status' => 'required|in:1,0',  // Ensures only 'active' or 'inactive' are allowed
        ]);
        
        $promotion = Promotion::where('id', $id)
                            ->where('tenant_id', $tenantId)
                            ->first();

    
        if ($promotion) {
            $promotion->is_active = $validated['status']; 
            if ($promotion->save()) {  // Save the user object
                return response()->json([
                    'success' => true,
                    'reload' => true,
                    'componentId' => 'reloadPromotionComponent',
                    'refresh' => false,
                    'message' => __('auth._updated'),
                    'redirect' => route('promotion.index'),
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
