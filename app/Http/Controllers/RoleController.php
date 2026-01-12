<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class RoleController extends Controller
{
    public function index(Request $request) 
    {
        $roles = Role::where('tenant_id', current_tenant_id())->with('permissions')->latest()->get();
        $permissions = Permission::regular()->get();

        $rolesWithUserCounts = $roles->map(function($role) {
            $role->user_count = User::where('tenant_id', current_tenant_id())->where('role_id', $role->id)->count();
            return $role;
        });

        $bladeToReload = $request->query('bladeFileToReload');
        switch ($bladeToReload) {
            case 'reloadRoleComponent':
                return view('human-resource.role.role-component', [
                    'roles' => $roles,
                    'permissions' => $permissions,
                ]);
            default:
                return view('human-resource.role-index', [
                    'roles' => $rolesWithUserCounts,
                    'permissions' => $permissions,
                ]);
        }

    }

    

    public function permissionIndex(Request $request) 
    {
        $users = User::where('tenant_id', current_tenant_id())->with('permissions')->latest()->get();
        $permissions = Permission::regular()->get();

        $bladeToReload = $request->query('bladeFileToReload');
        switch ($bladeToReload) {
            case 'reloadPermissionComponent':
                return view('human-resource.permissions.permission-component', [
                    'users' => $users,
                    'permissions' => $permissions,
                ]);
            default:
                return view('human-resource.permission-index', [
                    'users' => $users,
                    'permissions' => $permissions,
                ]);
        }

    }

    public function updatePermission(Request $request, $user_id)
    {
        // Validate the input permissions
        $validatedPermission = $request->validate([
            'permissions' => 'nullable|array|max:2225',  
            'permissions.*' => 'exists:permissions,id',  
        ]);

        try {
            $user = User::findOrFail($user_id);

            $permissions = Permission::regular()->whereIn('id', $validatedPermission['permissions'])->pluck('name');

            // Attach the permissions directly to the user (not via roles)
            $user->syncPermissions($permissions);

            return response()->json([
                'success' => true,
                'message' => __('auth._updated'),
                'reload' => true,
                'componentId' => 'reloadPermissionComponent',
                'refresh' => false,
                'redirect' => route('permission.index'),

            ]);
            

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(), 
            ]);
        }
    }


    public function storeRole(Request $request) 
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        $validatedPermission = $request->validate([
            'permissions' => 'required|array|max:2225',  
            'permissions.*' => 'exists:permissions,id',  
            'name' => [
                'required',
                'string',
                'max:25',
                Rule::unique('roles')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId);
                }),
                'regex:/^\S+(\s\S+)?$/'
            ]
        ]);
        
        // Check maximum roles limit
        // $currentRoleCount = Role::where('tenant_id', $tenantId)->count();
        // $maxRoles = tenant_setting($tenantId, 'max_roles'); 

        // if ($currentRoleCount >= $maxRoles) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => __('auth.maximum_roles_reached', ['max' => $maxRoles]),
        //     ]);
        // }

        $userRole = Role::create([
            'name' => $validatedPermission['name'],
            'guard_name' => 'web',
            'tenant_id' => $tenantId
        ]);
        
        $permissionNames = Permission::regular()->whereIn('id', $validatedPermission['permissions'])->pluck('name');
        $userRole->syncPermissions($permissionNames);
        
        return response()->json([
            'success' => true,
            'reload' => true,
            'componentId' => 'reloadRoleComponent',
            'refresh' => false,
            'message' => __('auth._created'),
            'redirect' => route('role.index'),
        ]);
    }

    public function updateRole(Request $request, $id) 
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        $validatedPermission = $request->validate([
            'permissions' => 'required|array|max:2225',  
            'permissions.*' => 'exists:permissions,id',  
            'name' => [
                'required',
                'string',
                'max:25',
                Rule::unique('roles')->where(function ($query) use ($tenantId, $id) {
                    return $query->where('tenant_id', $tenantId)
                            ->where('id', '!=', $id); // Ignore current role
                }),
                'regex:/^\S+(\s\S+)?$/'
            ]
        ]);

        try {
            // Find the user role by ID and ensure it belongs to the tenant
            $userRole = Role::where('id', $id)
                ->where('tenant_id', $tenantId)
                ->whereNotIn('name', ['admin'])
                ->first();

            // Check if role exists and belongs to tenant
            if (!$userRole) {
                return response()->json([
                    'success' => false,
                    'message' => __('auth.role_not_found'),
                ]);
            }

            // Update the role
            $userRole->update([
                'name' => $validatedPermission['name']
            ]);
            
            $permissions = Permission::regular()->whereIn('id', $validatedPermission['permissions'])->pluck('name');
            $userRole->syncPermissions($permissions);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(), 
            ]);
        }

        return response()->json([
            'success' => true,
            'reload' => true,
            'componentId' => 'reloadRoleComponent',
            'refresh' => false,
            'message' => __('auth._updated'),
            'redirect' => route('role.index'),
        ]);
    }

    public function destroy(string $id)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        // Find the role and ensure it belongs to the tenant
        $role = Role::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        // If the role doesn't exist, return an error response
        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => __('auth.role_not_found'),
            ]);
        }

        // Check if any user is currently associated with this role within the same tenant
        $user = User::where('role_id', $role->id)
            ->where('tenant_id', $tenantId)
            ->first();

        // If users are still assigned to this role, return an error response
        if ($user) {
            return response()->json([
                'success' => false,
                'message' => __('auth.users_exist'),
            ]);
        }

        // Check if the role is deletable (i.e., not a special role like 'admin', 'cashier', etc.)
        $deletableRole = Role::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->whereNotIn('name', ['admin', 'cashier', 'manager', 'inventory_clerk'])
            ->first();

        // If the role is deletable, delete it and return a success response
        if ($deletableRole) {
            $deletableRole->delete();

            return response()->json([
                'success' => true,
                'message' => __('auth._deleted'),
                'reload' => true,
                'componentId' => 'reloadRoleComponent',
                'refresh' => false,
                'redirect' => route('role.index'),
            ]);
        }

        // If the role cannot be deleted (i.e., it's one of the special roles), return an error response
        return response()->json([
            'success' => false,
            'message' => __('auth.cant_delete'),
        ]);
    }

}
