<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{ Location, User };
use Illuminate\Support\Facades\{ Auth, DB };
use Illuminate\Validation\Rule;

class LocationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasPermissionTo('view location')) {
            // abort(403, __('payments.not_authorized'));
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }
        
        // Start the query with relationships
        $query = Location::with('locationCreater', 'locationManager', 'departments');
        
        // Apply tenant filter only if not super_admin
        if (!$user->hasRole('super_admin')) {
            $query->where('tenant_id', $user->tenant_id);
        }
        
        $locations = $query->latest()->get();
        
        $bladeToReload = $request->query('bladeFileToReload');
        switch ($bladeToReload) {
            case 'locationIndexTable':
                return view('unit-of-measure.location.component', [
                    'all_locations' => $locations,
                ]);
            default:
                return view('unit-of-measure.location-index', [
                    'all_locations' => $locations,
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
        if (!$user->hasPermissionTo('create location')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        $request->validate([
            'name' => 'required|string|max:55|unique:locations,name',
            'address' => 'required|string|max:55',
            'manager_id' => 'required|exists:users,id',
            'currency_id' => 'required|exists:currencies,id',
        ]);


        // Check maximum locations limit
        $currentLocationCount = Location::where('tenant_id', $tenantId)->count();
        $maxLocations = tenant_setting($tenantId, 'max_locations', 1);

        if ($currentLocationCount >= $maxLocations) {
            return response()->json([
                'success' => false,
                'message' => __('auth.maximum_locations_reached', ['max' => $maxLocations]),
            ]);
        }

        $location = Location::create([
            'name' => $request->name,
            'address' => $request->address,
            'created_by' => Auth::user()->id,
            'manager_id' => $request->manager_id,
            'currency_id' => $request->currency_id,
            'tenant_id' => $tenantId = $user->tenant_id,
        ]);

        return response()->json([
            'success' => true,
            'reload' => true,
            'componentId' => 'locationIndexTable',
            'refresh' => false,
            'message' => __('auth._created'),
            'redirect' => route('locations.index'),
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
        if (!$user->hasPermissionTo('edit location')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        // Find location and ensure it belongs to tenant
        $location = Location::where('id', $id)
                        ->where('tenant_id', $tenantId)
                        ->first();

        if (!$location) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ]);
        }

        $request->validate([
            'name' => [
                'required',
                'max:255',
                Rule::unique('locations')->where(function ($query) use ($tenantId, $id) {
                    return $query->where('tenant_id', $tenantId)
                            ->where('id', '!=', $id);
                })->ignore($location->id),
            ],
            'manager_id' => [
                'required',
                'exists:users,id',
                function ($attribute, $value, $fail) use ($tenantId) {
                    $manager = User::where('tenant_id', $tenantId)
                                ->first();
                    if (!$manager) {
                        $fail('The selected manager is invalid.');
                    }
                }
            ],
            'currency_id' => [
                'required',
                'exists:currencies,id',
                function ($attribute, $value, $fail) use ($tenantId) {
                    $manager = User::where('tenant_id', $tenantId)
                                ->first();
                    if (!$manager) {
                        $fail('The selected currency is invalid.');
                    }
                }
            ],
            'address' => 'required|string|max:55',
        ]);

        $location->update([
            'name' => $request->name,
            'address' => $request->address,
            'created_by' => $user->id,
            'manager_id' => $request->manager_id,
            'currency_id' => $request->currency_id,
        ]);

        return response()->json([
            'success' => true,
            'reload' => true,
            'componentId' => 'locationIndexTable',
            'refresh' => false,
            'message' => __('auth._updated'),
            'redirect' => route('locations.index'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('delete location')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        $location = Location::where('id', $id)
                        ->where('tenant_id', $tenantId)
                        ->first();
        
        // Check if location exists and belongs to tenant
        if (!$location) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ]);
        }

        if (($location->departments->count() > 0)) {
            return response()->json([
                'success' => false,
                'message' => __('auth.unauthorized_access'),
            ]);
        }

        if ($location->is_active === 1) {
            return response()->json([
                'success' => false,
                'message' => __('auth.still_active'),
            ]);
        }

        // Check if location is attached to any products in location_product pivot table
        $attachedToProducts = DB::table('location_product')
            ->where('location_id', $id)
            ->exists();

        if ($attachedToProducts) {
            return response()->json([
                'success' => false,
                'message' => __('auth.location_attached_to_products'),
            ]);
        }

        // Check if location is referenced in inventory_items table
        $hasInventoryItems = DB::table('inventory_items')
            ->where('location_id', $id)
            ->exists();

        if ($hasInventoryItems) {
            return response()->json([
                'success' => false,
                'message' => __('auth.location_has_inventory_items'),
            ]);
        }

        // Check if location is referenced in orders table
        $hasOrders = DB::table('orders')
            ->where('location_id', $id)
            ->exists();

        if ($hasOrders) {
            return response()->json([
                'success' => false,
                'message' => __('auth.location_has_orders'),
            ]);
        }

        $location->delete();
        
        return response()->json([
            'success' => true,
            'reload' => true,
            'componentId' => 'locationIndexTable',
            'refresh' => false,
            'message' => __('auth._deleted'),
            'redirect' => route('locations.index'),
        ]);
    }

        
    public function updatePrimaryStatus(Request $request, $id) 
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('update location')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        // Validate the request data for status
        $validated = $request->validate([
            'status' => 'required|in:1,0',  // Ensures only 'active' or 'inactive' are allowed
        ]);
        
        $location = Location::where('id', $id)
                        ->where('tenant_id', $tenantId)
                        ->first();
    
        if ($location) {
            $location->is_primary = $validated['status']; 
            if ($location->save()) { 
                return response()->json([
                    'success' => true,
                    'reload' => true,
                    'componentId' => 'locationIndexTable',
                    'refresh' => false,
                    'message' => __('auth._updated'),
                    'redirect' => route('locations.index'),
                ]);
            }
        }
    
        // If user not found or status update failed
        return response()->json([
            'success' => false,
            'message' => __('auth._not_found'),
        ]);
    }
    
    public function updateLocationStatus(Request $request, $id) 
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('update location')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }
        // Validate the request data for status
        $validated = $request->validate([
            'status' => 'required|in:1,0',  // Ensures only 'active' or 'inactive' are allowed
        ]);
        // \Log::info($request->all());
        
        $location = Location::where('id', $id)
                        ->where('tenant_id', $tenantId)
                        ->first();
    
        if ($location) {
            $location->is_active = $validated['status']; 
            if ($location->save()) { 
                return response()->json([
                    'success' => true,
                    'reload' => true,
                    'componentId' => 'locationIndexTable',
                    'refresh' => false,
                    'message' => __('auth._updated'),
                    'redirect' => route('locations.index'),
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
