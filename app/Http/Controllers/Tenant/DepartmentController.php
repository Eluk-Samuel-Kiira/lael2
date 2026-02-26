<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\{ Department, User };
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{ Auth, DB };
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasPermissionTo('view department')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }
        
        // If user is super_admin, get all departments without tenant filter
        if ($user->hasRole('super_admin')) {
            $departments = Department::with('location')->latest()->get();
        } else {
            // Regular users only see their tenant's departments
            $departments = Department::with('location')->where('tenant_id', $user->tenant_id)->latest()->get();
        }
        
        $bladeToReload = $request->query('bladeFileToReload');
        switch ($bladeToReload) {
            case 'departmentIndexTable':
                return view('department.partials.department-component', [
                    'all_departments' => $departments,
                ]);
            default:
                return view('department.department-index', [
                    'all_departments' => $departments,
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

        if (!$user->hasPermissionTo('create department')) {
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
                Rule::unique('departments')->where(function ($query) use ($tenantId, $request) {
                    return $query->where('tenant_id', $tenantId)
                        ->where('location_id', $request->location_id);
                })
            ],
            'manager_id' => 'required|exists:users,id',
            'location_id' => 'required|exists:locations,id',
        ]);

        // Check maximum departments limit for the tenant
        $currentDepartmentCount = Department::where('tenant_id', $tenantId)->count();
        $maxDepartments = tenant_setting($tenantId, 'max_departments', 3);

        if ($currentDepartmentCount >= $maxDepartments) {
            return response()->json([
                'success' => false,
                'message' => __('auth.maximum_departments_reached', ['max' => $maxDepartments]),
            ]);
        }

        $department = Department::create([
            'name' => $request->name,
            'created_by' => $user->id,
            'manager_id' => $request->manager_id,
            'location_id' => $request->location_id,
            'tenant_id' => $tenantId,
        ]);

        return response()->json([
            'success' => true,
            'reload' => true,
            'componentId' => 'departmentIndexTable',
            'refresh' => false,
            'message' => __('auth._created'),
            'redirect' => route('department.index'),
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
        if (!$user->hasPermissionTo('edit department')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        $department = Department::where('id', $id)
                            ->where('tenant_id', $tenantId)
                            ->first();
        
        // Check if department exists and belongs to tenant
        if (!$department) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ]);
        }

        if ($department->tenant_id !== $tenantId) {
            return response()->json([
                'success' => false,
                'message' => __('auth.unauthorized_access'),
            ]);
        }

        $request->validate([
            'name' => [
                'required',
                'max:255',
                Rule::unique('departments')->where(function ($query) use ($tenantId, $request) {
                    return $query->where('tenant_id', $tenantId)
                        ->where('location_id', $request->location_id);
                })->ignore($department->id),
            ],
            'manager_id' => 'required|exists:users,id',
            'location_id' => 'required|exists:locations,id',
        ]);

        $department->update([
            'name' => $request->name,
            'manager_id' => $request->manager_id,
            'location_id' => $request->location_id,
            'created_by' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'reload' => true,
            'componentId' => 'departmentIndexTable',
            'refresh' => false,
            'message' => __('auth._updated'),
            'redirect' => route('department.index'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        if (!$user->hasPermissionTo('delete department')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        $department = Department::where('id', $id)
                            ->where('tenant_id', $tenantId)
                            ->first();
        
        // Check if department exists and belongs to tenant
        if (!$department) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ]);
        }

        if ($department->tenant_id !== $tenantId) {
            return response()->json([
                'success' => false,
                'message' => __('auth.unauthorized_access'),
            ]);
        }

        if ($department->isActive === 1) {
            return response()->json([
                'success' => false,
                'message' => __('auth.department_still_active'),
            ]);
        }

        // Check if department has any users assigned
        $hasUsers = User::where('department_id', $id)
            ->exists();

        if ($hasUsers) {
            return response()->json([
                'success' => false,
                'message' => __('auth.department_has_users'),
            ]);
        }

        // Check if department is attached to any products in department_product pivot table
        $attachedToProducts = DB::table('department_product')
            ->where('department_id', $id)
            ->exists();

        if ($attachedToProducts) {
            return response()->json([
                'success' => false,
                'message' => __('auth.department_attached_to_products'),
            ]);
        }

        // Check if department is referenced in orders table
        $hasOrders = DB::table('orders')
            ->where('department_id', $id)
            ->where('tenant_id', $tenantId)
            ->exists();

        if ($hasOrders) {
            return response()->json([
                'success' => false,
                'message' => __('auth.department_has_orders'),
            ]);
        }

        // Check if department is referenced in inventory_items table
        $hasInventoryItems = DB::table('inventory_items')
            ->where('department_id', $id)
            ->where('tenant_id', $tenantId)
            ->exists();

        if ($hasInventoryItems) {
            return response()->json([
                'success' => false,
                'message' => __('auth.department_has_inventory_items'),
            ]);
        }

        $department->delete();
        
        return response()->json([
            'success' => true,
            'reload' => true,
            'componentId' => 'departmentIndexTable',
            'refresh' => false,
            'message' => __('auth._deleted'),
            'redirect' => route('department.index'),
        ]);
    }

    public function changeDepartmentStatus(Request $request, $id) 
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        if (!$user->hasPermissionTo('update department')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        // Validate the request data for status
        $validated = $request->validate([
            'status' => 'required|in:1,0',  // Ensures only 'active' or 'inactive' are allowed
        ]);
    
        // Find the user by ID
        $department = Department::where('id', $id)
                            ->where('tenant_id', $tenantId)
                            ->first();
    
        if ($department) {
            $department->isActive = $validated['status']; 
            if ($department->save()) {  // Save the user object
                return response()->json([
                    'success' => true,
                    'reload' => true,
                    'refresh' => false,
                    'componentId' => 'departmentIndexTable',
                    'message' => __('auth._updated'),
                    'redirect' => route('department.index'),
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
