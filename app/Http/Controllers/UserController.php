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
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }
        
        // Build the query with tenant relationship for super_admin
        $query = User::with(['userRole', 'userDepartment']);
        
        // Add tenant relationship for super_admin to see which tenant users belong to
        if ($user->hasRole('super_admin')) {
            $query->with('tenant');
        } else {
            $query->where('tenant_id', $user->tenant_id);
        }
        
        $employees = $query->latest()->get();

        $bladeToReload = $request->query('bladeFileToReload');
        switch ($bladeToReload) {
            case 'reloadEmployeeComponent':
                return view('human-resource.partial.user-componenet', [
                    'all_employees' => $employees,
                ]);
            default:
                return view('human-resource.index', [
                    'all_employees' => $employees,
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

        // Check maximum users limit
        $currentUserCount = User::where('tenant_id', $tenantId)->count();
        $maxUsers = tenant_setting($tenantId, 'max_users', 5); // Default to 10 if not set

        if ($currentUserCount >= $maxUsers) {
            return response()->json([
                'success' => false,
                'message' => __('auth.maximum_users_reached', ['max' => $maxUsers]),
            ]);
        }

        $validatedData = $request->validated();

        // Generate username and password
        $username = Str::lower($validatedData['first_name'] . ' ' . $validatedData['last_name']);       
        $randomPassword = Str::random(10);
        $hashedPassword = Hash::make($randomPassword);

        // Merge additional data into the validated array
        $userData = array_merge($validatedData, [
            'name' => $username,
            'password' => $hashedPassword,
            'tenant_id' => $tenantId, // Use dynamic tenant_id
        ]);

        try {
            // Create user
            $user = User::create($userData);

            $role = Role::find($request->role_id);
            
            if ($user) {
                $user->assignRole($role->name);

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
        
        // Get the validated data
        $validatedData = $request->validated();


        // Check if the authenticated user has the required role
        if ($authUser) {
            // Find the user by ID
            $user = User::find($id);

            // If the user is found, update their details
            if ($user) {
                // Update user details
                $user->update($validatedData);

                // Synchronize roles (if role has changed)
                if (isset($validatedData['role_id'])) {
                    $role = Role::find($validatedData['role_id']);
                    $user->syncRoles($role->name);
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

            // If user not found, return failure response
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ]);
        }

        // If authenticated user doesn't have the required role
        return response()->json([
            'success' => false,
            'message' => __('auth.does_not_permision'),
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

        $user = User::where('id', $id)
                            ->where('tenant_id', $tenantId)
                            ->first();

        // Get the authenticated user
        $authUser = auth()->user();

        if ($authUser) { 
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
        
        return response()->json([
            'success' => false,
            'message' => __('auth._undeleletable'),
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

        // Find the user by ID and ensure it belongs to the same tenant
        $userToUpdate = User::where('id', $id)
                        ->where('tenant_id', $tenantId)
                        ->first();

        if (!$userToUpdate) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ]);
        }

        // Check if user has protected roles using Spatie Permission
        if ($userToUpdate->hasAnyRole(['super_admin', 'admin'])) {
            return response()->json([
                'success' => false,
                'message' => __('auth.user_not_updatable'),
            ]);
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

        // Find the employee and ensure it belongs to the same tenant
        $employee = User::where('id', $id)
                    ->where('tenant_id', $tenantId)
                    ->first();

        if (!$employee) {
            session()->flash('toast', [
                'type' => 'error',
                'message' => __('auth._not_found'),
            ]);
            return redirect()->back();
        }

        // Check if employee has protected roles
        // if ($employee->hasAnyRole(['super_admin', 'admin'])) {
        //     session()->flash('toast', [
        //         'type' => 'error',
        //         'message' => __('auth.protected_user_not_updatable'),
        //     ]);
        //     return redirect()->back();
        // }

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
