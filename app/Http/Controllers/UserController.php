<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{ User, Department, Location };
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
            abort(403, __('payments.not_authorized'));
        }

        return view('human-resource.index', [
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

        if (!$user->hasPermissionTo('view user')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }

        $perPage = (int) $request->get('per_page', 15);
        if (!in_array($perPage, [15, 25, 50, 100], true)) $perPage = 15;

        $query = User::with(['userRole:id,name', 'userDepartment:id,name', 'userLocation:id,name'])
            ->whereDoesntHave('roles', fn($q) => $q->where('name', 'super_admin'));

        if (!$user->hasRole('super_admin')) {
            $query->where('tenant_id', $tenantId);
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('first_name', 'like', "%{$s}%")
                ->orWhere('last_name', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%")
                ->orWhere('telephone_number', 'like', "%{$s}%")
                ->orWhereHas('userRole', fn($r) => $r->where('name', 'like', "%{$s}%"))
                ->orWhereHas('userDepartment', fn($d) => $d->where('name', 'like', "%{$s}%"));
            });
        }

        if ($request->filled('status') && in_array($request->status, ['active', 'inactive'], true)) {
            $query->where('status', $request->status);
        }

        if ($request->filled('role_id')) {
            $query->where('role_id', (int) $request->role_id);
        }

        // Summary over filtered set
        $all = (clone $query)->get(['id', 'status']);
        $summary = [
            'total'    => $all->count(),
            'active'   => $all->where('status', 'active')->count(),
            'inactive' => $all->where('status', 'inactive')->count(),
        ];

        $paginator = $query->latest()->paginate($perPage);

        $rows = collect($paginator->items())->map(fn(User $u) => [
            'id'               => $u->id,
            'name'             => trim($u->first_name . ' ' . $u->last_name),
            'email'            => $u->email,
            'role_name'        => $u->userRole?->name ? ucwords(str_replace('_', ' ', $u->userRole->name)) : '—',
            'department_name'  => $u->userDepartment?->name ?? '—',
            'location_name'    => $u->userLocation?->name ?? '—',
            'telephone_number' => $u->telephone_number ?? '—',
            'job_title'        => $u->job_title ?? '—',
            'profile_image'    => employeeProfileImage($u->profile_image),
            'status'           => $u->status, // 'active' | 'inactive'
            'created_at'       => optional($u->created_at)->format('d M Y'),
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

        if (!$user->hasPermissionTo('view user')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }

        $employee = User::with([
                'userRole:id,name',
                'userDepartment:id,name',
                'userLocation:id,name',
                'departments:id,name,location_id',
                'locations:id,name',
            ])
            ->whereDoesntHave('roles', fn($q) => $q->where('name', 'super_admin'))
            ->when(!$user->hasRole('super_admin'), fn($q) => $q->where('tenant_id', $tenantId))
            ->find($id);

        if (!$employee) {
            return response()->json(['success' => false, 'message' => __('auth._not_found')], 404);
        }

        $targetRoles   = $employee->roles->pluck('name')->toArray();
        $isProtected   = array_intersect($targetRoles, ['super_admin', 'admin']) !== [];
        $authRoles     = $user->roles->pluck('name')->toArray();
        $isAuthSuper   = in_array('super_admin', $authRoles);
        $isAuthAdmin   = in_array('admin', $authRoles);
        $isSelf        = $user->id === $employee->id;

        $can = [
            'can_edit'        => $user->can('edit user') && !$isProtected,
            'can_delete'      => $user->can('delete user') && !$isProtected && !$isSelf,
            'can_toggle_status' => $user->can('update user') && !$isProtected,
            'can_assign'      => $user->can('update user') && !$isProtected,
            // role-select options, respecting who can assign what
            'assignable_roles' => Role::whereNotIn('name', array_filter([
                    $isAuthSuper ? null : 'super_admin',
                    ($isAuthSuper || $isAuthAdmin) ? null : 'admin',
                ]))->orderBy('name')->get(['id', 'name'])
                ->map(fn($r) => ['id' => $r->id, 'name' => ucwords(str_replace('_', ' ', $r->name))])
                ->values(),
        ];

        $departmentsByLocation = Department::where('tenant_id', $tenantId)
            ->with('location:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'location_id'])
            ->groupBy('location_id')
            ->map(fn($g) => $g->map(fn($d) => ['id' => $d->id, 'name' => $d->name])->values());

        return response()->json([
            'success'  => true,
            'employee' => array_merge([
                'id'              => $employee->id,
                'first_name'      => $employee->first_name,
                'last_name'       => $employee->last_name,
                'name'            => trim($employee->first_name . ' ' . $employee->last_name),
                'email'           => $employee->email,
                'telephone_number'=> $employee->telephone_number,
                'job_title'       => $employee->job_title,
                'profile_image'   => employeeProfileImage($employee->profile_image),
                'status'          => $employee->status,
                'role_id'         => $employee->role_id,
                'role_name'       => $employee->userRole?->name
                                        ? ucwords(str_replace('_', ' ', $employee->userRole->name)) : '—',
                'location_id'     => $employee->location_id,
                'location_name'   => $employee->userLocation?->name ?? '—',
                'department_id'   => $employee->department_id,
                'department_name' => $employee->userDepartment?->name ?? '—',
                'created_at'      => optional($employee->created_at)->format('d M Y, H:i'),
                'updated_at'      => optional($employee->updated_at)->format('d M Y, H:i'),

                'assigned_department_ids' => $employee->departments->pluck('id')->values(),
                'assigned_location_ids'   => $employee->locations->pluck('id')->values(),

                'all_departments_by_location' => $departmentsByLocation,
                'all_locations' => Location::where('tenant_id', $tenantId)
                                        ->orderBy('name')->get(['id', 'name']),
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
                    $user->userDepartment->name ?? 'Check with Admin', 
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

        // =============================================
        // ROLE-BASED ACCESS CONTROL (RBAC)
        // =============================================
        
        // Get the target user's current roles
        $targetRoles = $user->roles->pluck('name')->toArray();
        $isTargetSuperAdmin = in_array('super_admin', $targetRoles);
        $isTargetAdmin = in_array('admin', $targetRoles);
        
        // Get authenticated user's roles
        $authRoles = $authUser->roles->pluck('name')->toArray();
        $isAuthSuperAdmin = in_array('super_admin', $authRoles);
        $isAuthAdmin = in_array('admin', $authRoles);
        
        // RULE 1: ONLY super_admin can update super_admin users
        if ($isTargetSuperAdmin && !$isAuthSuperAdmin) {
            return response()->json([
                'success' => false,
                'message' => __('auth.cannot_update_protected_role'),
            ]);
        }
        
        // RULE 2: ONLY super_admin OR admin can update admin users
        if ($isTargetAdmin && !$isAuthSuperAdmin && !$isAuthAdmin) {
            return response()->json([
                'success' => false,
                'message' => __('auth.cannot_update_protected_role'),
            ]);
        }
        
        // RULE 3: Prevent updating to super_admin or admin role
        // Only super_admin can assign super_admin role
        // Only super_admin or admin can assign admin role
        if ($request->has('role_id') && !is_null($request->role_id)) {
            $newRole = Role::find($request->role_id);
            
            if ($newRole) {
                // Cannot assign super_admin unless you ARE super_admin
                if ($newRole->name === 'super_admin' && !$isAuthSuperAdmin) {
                    return response()->json([
                        'success' => false,
                        'message' => __('auth.cannot_assign_protected_role'),
                    ]);
                }
                
                // Cannot assign admin unless you ARE super_admin or admin
                if ($newRole->name === 'admin' && !$isAuthSuperAdmin && !$isAuthAdmin) {
                    return response()->json([
                        'success' => false,
                        'message' => __('auth.cannot_assign_protected_role'),
                    ]);
                }
            }
        }

        // RULE 4: Prevent self-lockout - users cannot change their own role
        if ($authUser->id == $id) {
            // Allow other updates but prevent role change for self
            if ($request->has('role_id') && !is_null($request->role_id)) {
                return response()->json([
                    'success' => false,
                    'message' => __('auth.cannot_change_own_role'),
                ]);
            }
        }
        
        // Get the validated data
        $validatedData = $request->validated();

        // Remove role_id from validated data if:
        // 1. User is updating themselves, OR
        // 2. User is admin and role_id is null
        if ($authUser->id == $id) {
            unset($validatedData['role_id']);
        } elseif ($isAuthAdmin && isset($validatedData['role_id']) && is_null($validatedData['role_id'])) {
            // Admin wants to keep the existing role (remove role_id from update)
            unset($validatedData['role_id']);
        }

        // Update user details
        $user->update($validatedData);

        // Synchronize roles (only if role_id is provided and not null)
        if (isset($validatedData['role_id']) && !is_null($validatedData['role_id'])) {
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

        $employee = User::find($id);

        if (!$employee) {
            session()->flash('toast', [
                'type' => 'error',
                'message' => __('auth._not_found'),
            ]);
            return redirect()->back();
        }

        if (!$user->hasRole('super_admin')) {
            if ($employee->tenant_id != $tenantId) {
                session()->flash('toast', [
                    'type' => 'error',
                    'message' => __('auth._not_found'),
                ]);
                return redirect()->back();
            }
        }

        // Validate departments[] AND locations[]
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
            'locations'   => ['nullable', 'array'],
            'locations.*' => [
                'exists:locations,id',
                function ($attribute, $value, $fail) use ($tenantId) {
                    $location = Location::where('id', $value)
                                        ->where('tenant_id', $tenantId)
                                        ->first();
                    if (!$location) {
                        $fail('The selected location is invalid.');
                    }
                }
            ],
        ]);

        // Sync both pivots
        $employee->departments()->sync($validated['departments'] ?? []);
        $employee->locations()->sync($validated['locations'] ?? []);

        session()->flash('toast', [
            'type' => 'success',
            'message' => __('auth._updated'),
        ]);

        return redirect()->back();
    }


}