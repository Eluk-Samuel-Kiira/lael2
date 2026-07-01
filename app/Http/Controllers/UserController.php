<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{ User, Department };
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\{ Mail, Auth };
use App\Mail\NewUserMail;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;


class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->hasPermissionTo('view user')) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.not_authorized'),
                ]);
            }
            abort(403);
        }
        
        // Get per_page from request, default to 15
        $perPage = $request->input('per_page', 15);
        
        // Validate per_page is in allowed values
        $allowedPerPage = [15, 25, 50, 100];
        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 15;
        }
        
        // Build query
        $query = User::with(['userRole', 'userDepartment', 'userLocation']);
        
        // EXCLUDE super_admin users from being displayed (they are system-level)
        $query->whereDoesntHave('roles', function($q) {
            $q->where('name', 'super_admin');
        });
        
        if (!$user->hasRole('super_admin')) {
            $query->where('tenant_id', $user->tenant_id);
        } else {
            $query->with('tenant');
        }
        
        // Apply search if provided
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('telephone_number', 'like', "%{$search}%")
                ->orWhereHas('userRole', fn($r) => $r->where('name', 'like', "%{$search}%"))
                ->orWhereHas('userDepartment', fn($d) => $d->where('name', 'like', "%{$search}%"));
            });
        }
        
        // Paginate with dynamic per_page
        $employees = $query->latest()->paginate($perPage);
        
        // Preserve per_page in pagination links
        $employees->appends(['per_page' => $perPage]);
        
        $bladeToReload = $request->query('bladeFileToReload');
        
        // For AJAX requests - return just the component HTML
        if ($request->ajax() && $bladeToReload === 'reloadEmployeeComponent') {
            return view('human-resource.partial.user-componenet', [
                'all_employees' => $employees,
            ])->render();
        }
        
        // Regular page load
        return view('human-resource.index', [
            'all_employees' => $employees,
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
    public function store(StoreEmployeeRequest $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('create user')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        // Check if trying to create super_admin or admin
        if ($request->has('role_id')) {
            $role = Role::find($request->role_id);
            if ($role && ($role->name === 'super_admin' || $role->name === 'admin')) {
                return response()->json([
                    'success' => false,
                    'message' => __('auth.cannot_create_protected_role'),
                ]);
            }
        }

        // tenant_clear_settings_cache($tenantId);

        // Get the actual max_users limit from tenant settings
        $maxUsers = tenant_limit('users', 3, $tenantId); // 3 is default if not set

        // Count current users
        $currentUserCount = User::where('tenant_id', $tenantId)->count();

        // Check if limit is reached
        if ($currentUserCount >= $maxUsers) {
            return response()->json([
                'success' => false,
                'message' => __('auth.maximum_users_reached', ['max' => $maxUsers]),
                'current' => $currentUserCount,
                'limit' => $maxUsers
            ]);
        }

        $validatedData = $request->validated();

        // Generate username and password
        $username = Str::lower($validatedData['first_name'] . ' ' . $validatedData['last_name']);       
        // $randomPassword = Str::random(10);
        $randomPassword = "attend1234";
        $hashedPassword = Hash::make($randomPassword);

        // Merge additional data into the validated array
        $userData = array_merge($validatedData, [
            'name' => $username,
            'password' => $hashedPassword,
            'tenant_id' => $tenantId,
        ]);

        try {
            // Create user
            $user = User::create($userData);

            $role = Role::find($request->role_id);
            
            if ($user) {
                // Double-check role is not protected before assigning
                if ($role && !in_array($role->name, ['super_admin', 'admin'])) {
                    $user->assignRole($role->name);
                } else {
                    // If somehow protected role, delete user and return error
                    $user->delete();
                    return response()->json([
                        'success' => false,
                        'message' => __('auth.cannot_create_protected_role'),
                    ]);
                }

                Mail::to($user->email)->send(new NewUserMail(
                    $user->first_name . ' ' . $user->last_name,
                    $user->userRole->name ?? 'No Role Assigned',  
                    $user->departmentName->name ?? 'Restaurant', 
                    $user->email,
                    $randomPassword
                ));
            }

            return response()->json([
                'success' => true,
                'reload' => true,
                'componentId' => 'reloadEmployeeComponent',
                'refresh' => false,
                'message' => __('auth._created'),
                'redirect' => route('employee.index'),
            ]);
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('User creation failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => __('auth.user_exist'),
            ]);
        }
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
    public function update(UpdateEmployeeRequest $request, $id)
    {
        // Get the authenticated user
        $authUser = auth()->user();
        
        if (!$authUser->hasPermissionTo('edit user')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        // Find the user by ID
        $user = User::find($id);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ]);
        }

        // Check if user has protected role (super_admin or admin) - IMMUTABLE
        if ($user->hasRole('super_admin') || $user->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => __('auth.cannot_update_protected_role'),
            ]);
        }

        // Check if trying to update to super_admin or admin role
        if ($request->has('role_id')) {
            $role = Role::find($request->role_id);
            if ($role && in_array($role->name, ['super_admin', 'admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => __('auth.cannot_assign_protected_role'),
                ]);
            }
        }

        // Check if authenticated user is trying to update themselves (prevent self-lockout)
        if ($authUser->id == $id && $request->has('role_id')) {
            // Allow other updates but prevent role change for self
            // Or you can prevent all updates to self except certain fields
        }
        
        // Get the validated data
        $validatedData = $request->validated();

        // Remove role_id from validated data if user is updating themselves
        if ($authUser->id == $id) {
            unset($validatedData['role_id']);
        }

        // Update user details
        $user->update($validatedData);

        // Synchronize roles (if role has changed)
        if (isset($validatedData['role_id'])) {
            $role = Role::find($validatedData['role_id']);
            // Double-check role is not protected
            if ($role && !in_array($role->name, ['super_admin', 'admin'])) {
                $user->syncRoles($role->name);
            }
        }

        // Return success response
        return response()->json([
            'success' => true,
            'reload' => true,
            'componentId' => 'reloadEmployeeComponent',
            'refresh' => false,
            'message' => __('auth._updated'),
            'redirect' => route('employee.index'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        // Get the authenticated user
        $authUser = auth()->user();
        
        if (!$authUser->hasPermissionTo('delete user')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        $user = User::find($id);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ]);
        }

        // Check if user has protected role (super_admin or admin) - IMMUTABLE
        if ($user->hasRole('super_admin') || $user->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => __('auth.cannot_delete_protected_role'),
            ]);
        }

        // Check if trying to delete self
        if ($authUser->id == $id) {
            return response()->json([
                'success' => false,
                'message' => __('auth.cannot_delete_self'),
            ]);
        }

        // Check tenant ownership if not super_admin
        if (!$authUser->hasRole('super_admin')) {
            $tenantId = $authUser->tenant_id;
            if ($user->tenant_id != $tenantId) {
                return response()->json([
                    'success' => false,
                    'message' => __('auth._not_found'),
                ]);
            }
        }

        $user->delete();
        
        return response()->json([
            'success' => true,
            'reload' => true,
            'componentId' => 'reloadEmployeeComponent',
            'refresh' => false,
            'message' => __('auth._deleted'),
            'redirect' => route('employee.index'),
        ]);
    }

    public function changeEmployeeStatus(Request $request, $id) 
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('update user')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        // Validate the request data for status
        $validated = $request->validate([
            'status' => 'required|in:active,inactive',
        ]);

        // Find the user by ID
        $userToUpdate = User::find($id);

        if (!$userToUpdate) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ]);
        }

        // Check if user has protected role (super_admin or admin) - IMMUTABLE
        if ($userToUpdate->hasRole('super_admin') || $userToUpdate->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => __('auth.cannot_update_protected_role'),
            ]);
        }

        // Ensure it belongs to the same tenant (unless super_admin)
        if (!$user->hasRole('super_admin')) {
            if ($userToUpdate->tenant_id != $tenantId) {
                return response()->json([
                    'success' => false,
                    'message' => __('auth._not_found'),
                ]);
            }
        }

        // Update the user status
        $userToUpdate->status = $validated['status'];
        
        if ($userToUpdate->save()) {
            // Return success response
            return response()->json([
                'success' => true,
                'reload' => true,
                'componentId' => 'reloadEmployeeComponent',
                'refresh' => false,
                'message' => __('auth._updated'),
                'redirect' => route('employee.index'),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => __('auth.update_failed'),
        ]);
    }

    public function updateDepartments(Request $request, $id)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('update user')) {
            abort(403, __('payments.not_authorized'));
        }

        // Find the employee
        $employee = User::find($id);

        if (!$employee) {
            session()->flash('toast', [
                'type' => 'error',
                'message' => __('auth._not_found'),
            ]);
            return redirect()->back();
        }

        // Check if employee has protected role (super_admin or admin) - IMMUTABLE
        if ($employee->hasRole('super_admin') || $employee->hasRole('admin')) {
            session()->flash('toast', [
                'type' => 'error',
                'message' => __('auth.cannot_update_protected_role'),
            ]);
            return redirect()->back();
        }

        // Ensure it belongs to the same tenant (unless super_admin)
        if (!$user->hasRole('super_admin')) {
            if ($employee->tenant_id != $tenantId) {
                session()->flash('toast', [
                    'type' => 'error',
                    'message' => __('auth._not_found'),
                ]);
                return redirect()->back();
            }
        }

        // Validate departments[] array with tenant check
        $validated = $request->validate([
            'departments'   => ['nullable', 'array'],
            'departments.*' => [
                'exists:departments,id',
                function ($attribute, $value, $fail) use ($tenantId) {
                    $department = Department::where('id', $value)
                                        ->where('tenant_id', $tenantId)
                                        ->first();
                    if (!$department) {
                        $fail('The selected department is invalid.');
                    }
                }
            ],
        ]);

        // Sync departments (many-to-many relation)
        $employee->departments()->sync($validated['departments'] ?? []);

        session()->flash('toast', [
            'type' => 'success',
            'message' => __('auth._updated'),
        ]);

        return redirect()->back();
    }
}