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
        
        // Build the query
        $query = Promotion::query();
        
        // If user is NOT super_admin, filter by tenant
        if (!$user->hasRole('super_admin')) {
            $query->where('tenant_id', current_tenant_id());
        }
        
        $promotions = $query->latest()->get();

        $bladeToReload = $request->query('bladeFileToReload');
        switch ($bladeToReload) {
            case 'reloadPromotionComponent':
                return view('promotion.promotion.component', [
                    'all_promotions' => $promotions,
                ]);
            default:
                return view('promotion.promotion-index', [
                    'all_promotions' => $promotions,
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
        // \Log::info($id);
        // Validate the request data for status
        $validated = $request->validate([
            'status' => 'required|in:1,0',  // Ensures only 'active' or 'inactive' are allowed
        ]);
        
        $promotion = Promotion::find($id);
    
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
