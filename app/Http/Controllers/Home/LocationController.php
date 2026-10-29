<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{ Location, User, Department };
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
            abort(403, __('payments.not_authorized'));
        }

        return view('unit-of-measure.location-index', [
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

        if (!$user->hasPermissionTo('view location')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }

        $perPage = (int) $request->get('per_page', 15);
        if (!in_array($perPage, [15, 25, 50, 100], true)) $perPage = 15;

        $query = Location::with(['locationCreater:id,name', 'locationManager:id,name', 'currency:id,name,code'])
            ->withCount('departments');

        if (!$user->hasRole('super_admin')) {
            $query->where('tenant_id', $tenantId);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('address', 'like', "%{$search}%")
                ->orWhereHas('locationCreater', fn($c) => $c->where('name', 'like', "%{$search}%"))
                ->orWhereHas('locationManager', fn($m) => $m->where('name', 'like', "%{$search}%"))
                ->orWhereHas('currency', fn($cur) =>
                        $cur->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', (int) $request->input('status'));
        }

        if ($request->filled('is_primary')) {
            $query->where('is_primary', (int) $request->input('is_primary'));
        }

        // Summary over the filtered set
        $all = (clone $query)->get(['id', 'is_active', 'is_primary']);

        $summary = [
            'total'    => $all->count(),
            'active'   => $all->where('is_active', 1)->count(),
            'inactive' => $all->where('is_active', 0)->count(),
            'primary'  => $all->where('is_primary', 1)->count(),
        ];

        $paginator = $query->latest()->paginate($perPage);

        $rows = collect($paginator->items())->map(fn(Location $l) => [
            'id'               => $l->id,
            'name'             => $l->name,
            'address'          => $l->address,
            'currency_code'    => $l->currency?->code,
            'creator_name'     => $l->locationCreater?->name,
            'manager_name'     => $l->locationManager?->name,
            'is_active'        => (bool) $l->is_active,
            'is_primary'       => (bool) $l->is_primary,
            'departments_count'=> $l->departments_count ?? 0,
            'created_at'       => optional($l->created_at)->format('d M Y'),
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

        if (!$user->hasPermissionTo('view location')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }

        $location = Location::with([
                'locationCreater:id,name,email',
                'locationManager:id,name,email',
                'currency:id,name,code,symbol',
                'departments' => fn($q) => $q->with('departmentManager:id,name')->orderBy('name'),
            ])
            ->where('tenant_id', $tenantId)
            ->find($id);

        if (!$location) {
            return response()->json(['success' => false, 'message' => __('auth._not_found')], 404);
        }

        $can = [
            'can_edit'            => $user->can('edit location'),
            'can_delete'          => $user->can('delete location'),
            'can_toggle_active'   => $user->can('update location'),
            'can_toggle_primary'  => $user->can('update location'),
            'can_view_department' => $user->can('view department'),
            'can_create_department' => $user->can('create department'),
        ];

        return response()->json([
            'success'  => true,
            'location' => array_merge([
                'id'              => $location->id,
                'name'            => $location->name,
                'address'         => $location->address,
                'is_active'       => (bool) $location->is_active,
                'is_primary'      => (bool) $location->is_primary,
                'currency_code'   => $location->currency?->code,
                'currency_name'   => $location->currency?->name,
                'currency_symbol' => $location->currency?->symbol,
                'creator_name'    => $location->locationCreater?->name,
                'creator_email'   => $location->locationCreater?->email,
                'manager_name'    => $location->locationManager?->name,
                'manager_email'   => $location->locationManager?->email,
                'created_at'      => optional($location->created_at)->format('d M Y, H:i'),
                'updated_at'      => optional($location->updated_at)->format('d M Y, H:i'),

                'manager_id'     => $location->manager_id,
                'manager_name'   => $location->locationManager?->name,
                'manager_email'  => $location->locationManager?->email,

                'currency_id'    => $location->currency_id,
                'currency_code'  => $location->currency?->code,
                'currency_name'  => $location->currency?->name,
                'currency_symbol'=> $location->currency?->symbol,

                'departments' => $location->departments->map(fn(Department $d) => [
                    'id'         => $d->id,
                    'name'       => $d->name,
                    'manager'    => $d->departmentManager?->name,
                    'is_active'  => (bool) $d->isActive,
                    'created_at' => optional($d->created_at)->format('d M Y'),
                ])->values(),

                'departments_count' => $location->departments->count(),
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


        // Clear cache to ensure fresh data
        tenant_clear_settings_cache($tenantId);

        // Check maximum locations limit
        $currentLocationCount = Location::where('tenant_id', $tenantId)->count();
        $maxLocations = tenant_limit('locations', 1, $tenantId); // Using tenant_limit instead of tenant_setting

        if ($currentLocationCount >= $maxLocations) {
            return response()->json([
                'success' => false,
                'message' => __('auth.maximum_locations_reached', ['max' => $maxLocations]),
                'current' => $currentLocationCount,
                'limit' => $maxLocations,
                'remaining' => 0
            ]);
        }

        $location = Location::create([
            'name' => $request->name,
            'address' => $request->address,
            'created_by' => Auth::user()->id,
            'manager_id' => $request->manager_id,
            'currency_id' => $request->currency_id,
            'tenant_id' => $tenantId = $user->tenant_id,
            'is_primary'  => false,  
            'is_active'   => true, 
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
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('update location')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:1,0',
        ]);

        $location = Location::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$location) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ], 404);
        }

        $newStatus = (int) $validated['status'];

        DB::beginTransaction();
        try {
            if ($newStatus === 1) {
                // Revoke primary from every other location in the tenant
                Location::where('tenant_id', $tenantId)
                    ->where('id', '!=', $location->id)
                    ->where('is_primary', 1)
                    ->update(['is_primary' => 0]);
            }

            $location->is_primary = $newStatus;
            $location->save();

            DB::commit();

            return response()->json([
                'success'     => true,
                'reload'      => true,
                'componentId' => 'locationIndexTable',
                'refresh'     => false,
                'message'     => $newStatus === 1
                    ? __('auth.location_marked_primary') ?? 'This location is now the primary one.'
                    : __('auth._updated'),
                'redirect'    => route('locations.index'),
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Primary location update failed', [
                'tenant_id'   => $tenantId,
                'location_id' => $id,
                'status'      => $newStatus,
                'error'       => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => __('auth.update_failed') . ': ' . $e->getMessage(),
            ], 500);
        }
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
