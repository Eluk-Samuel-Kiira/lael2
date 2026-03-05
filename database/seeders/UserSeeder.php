<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\{ User, Tenant };
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenantId = 2; // Use consistent tenant ID

        // Define roles
        $roles = [
            'admin',
            'super_admin',
            'manager',
            'cashier',
            'inventory_clerk',
        ];

        // Create roles WITH guard_name
        foreach ($roles as $role) {
            Role::create([
                'name' => $role,
                'guard_name' => 'web',
                'tenant_id' => $tenantId
            ]);
        }

        // Define permissions with categories
        $permissionCategories = [
            'Product Management' => [
                'create product',
                'view product',
                'edit product',
                'delete product',
                'update product',
                'update product tax-promotion',
                'upload product photo',
            ],
            'Variant Management' => [
                'create variant',
                'view variant',
                'edit variant',
                'delete variant',
                'update variant',
                'upload variant image',
                'update variant tax-promotion',
            ],
            'Sales Management' => [
                'process sale',
                'view sales report',
                'refund sale',
                'apply discounts',
            ],
            'Inventory Management' => [
                'create inventory record',
                'view inventory',
                'edit inventory',
                'delete inventory',
                'update stock levels',
                'transfer stock',
            ],
            'Customer Management' => [
                'create customer',
                'view customer',
                'edit customer',
                'delete customer',
            ],
            'Unit of Measure (UOM)' => [
                'create uom',
                'view uom',
                'edit uom',
                'delete uom',
                'update uom',
            ],
            'Department Management' => [
                'create department',
                'view department',
                'edit department',
                'delete department',
                'update department',
            ],
            'Category Management' => [
                'create category',
                'view category',
                'edit category',
                'delete category',
                'update category',
            ],
            'Subcategory Management' => [
                'create subcategory',
                'view subcategory',
                'edit subcategory',
                'delete subcategory',
                'update subcategory',
            ],
            'Location Management' => [
                'create location',
                'view location',
                'edit location',
                'delete location',
                'update location',
            ],
            'Tax Management' => [
                'create tax',
                'view tax',
                'edit tax',
                'delete tax',
                'update tax',
            ],
            'Employee Management' => [
                'create employee',
                'view employee',
                'edit employee',
                'delete employee',
                'update employee',
            ],
            'Employee Payment Management' => [
                'create employee payment',
                'view employee payment',
                'edit employee payment',
                'delete employee payment',
                'update employee payment',
                'edit completed payment',
                'delete completed payment',
            ],
            'Promotion Management' => [
                'create promotion',
                'view promotion',
                'edit promotion',
                'delete promotion',
                'update promotion',
            ],
            'Expense Category Management' => [
                'create category-expense',
                'view category-expense',
                'edit category-expense',
                'delete category-expense',
                'update category-expense',
            ],
            'Expense Management' => [
                'create expense',
                'view expense',
                'edit expense',
                'delete expense',
                'update expense',
                'approve expense',
                'upload expense',
            ],
            'Supplier Management' => [
                'create supplier',
                'view supplier',
                'edit supplier',
                'delete supplier',
                'update supplier',
            ],
            'Order Management' => [
                'create order',
                'view order',
                'edit order',
                'delete order',
                'update order',
                'cancel order',
                'print order',
                'refund order',
                'complete order',
            ],
            'Purchase Order Management' => [
                'view procurement',
                'view purchase_orders',
                'create purchase_orders',
                'edit purchase_orders', 
                'delete purchase_orders',
                'submit purchase_orders',
                'approve purchase_orders',
                'send purchase_orders',
                'update purchase_orders',
                'receive purchase_orders',
                'cancel purchase_orders',
            ],
            'Payment Method Management' => [
                'create payment method',
                'view payment method',
                'edit payment method',
                'delete payment method',
                'update payment method',
                'update current balance',
            ],
            'Reports Module' => [
                'view reports',
                'financial reports',
                'expense reports',
                'order reports',
                'product reports',
                'inventory reports',
                'purchasing reports',
            ],
            'Currency Management' => [
                'create currency',
                'view currency',
                'edit currency',
                'delete currency',
                'update currency',
            ],
            'User Management' => [
                'create user',
                'view user',
                'edit user',
                'delete user',
                'update user',
            ],
            'Role & Permission Management' => [
                'create role',
                'edit role',
                'delete role',
                'update role',
                'update permission',
                'update settings',
                'view settings',
            ],
            'System Configuration' => [
                'configure system',
                'admin only',
                'view financial dashboard',
            ],
        ];

        // Define SUPER ADMIN ONLY permissions with categories
        $superAdminOnlyCategories = [
            'Super Admin - Tenant Management' => [
                'tenant_manage_all',
                'tenant_delete_any',
                'manage_all_tenants',
                'override_tenant_limits',
            ],
            'Super Admin - Billing & Subscription' => [
                'tenant_billing_manage',
                'subscription_manage_all',
                'payment_gateway_manage',
            ],
            'Super Admin - System Settings' => [
                'system_settings_manage',
                'system_maintenance',
                'backup_manage',
            ],
            'Super Admin - User Management' => [
                'user_impersonate',
            ],
            'Super Admin - Data & Audit' => [
                'data_export_all',
                'audit_log_view_all',
            ],
            'Super Admin - Dashboard' => [
                'super_admin_dashboard',
            ],
        ];

        // Create regular permissions with category
        foreach ($permissionCategories as $category => $permissions) {
            foreach ($permissions as $permission) {
                Permission::firstOrCreate([
                    'name' => $permission,
                    'guard_name' => 'web',
                ], [
                    'is_super_admin' => false,
                    'category' => $category
                ]);
            }
        }

        // Create super admin only permissions with category
        foreach ($superAdminOnlyCategories as $category => $permissions) {
            foreach ($permissions as $permission) {
                Permission::firstOrCreate([
                    'name' => $permission,
                    'guard_name' => 'web',
                ], [
                    'is_super_admin' => true,
                    'category' => $category
                ]);
            }
        }

        // Get all permissions
        $allPermissions = Permission::all();
        
        // Get regular permissions only (for tenant admins)
        $regularPermissions = Permission::where('is_super_admin', false)->get();

        // Get super admin role
        $superAdminRole = Role::where('name', 'super_admin')->first();
        
        // Get admin role for tenant
        $adminRole = Role::where('name', 'admin')->where('tenant_id', $tenantId)->first();
        
        // Get other roles
        $managerRole = Role::where('name', 'manager')->where('tenant_id', $tenantId)->first();
        $cashierRole = Role::where('name', 'cashier')->where('tenant_id', $tenantId)->first();
        $inventoryClerkRole = Role::where('name', 'inventory_clerk')->where('tenant_id', $tenantId)->first();

        // Assign ALL permissions to super_admin
        if ($superAdminRole) {
            $superAdminRole->syncPermissions($allPermissions);
        }

        // Assign regular permissions to admin
        if ($adminRole) {
            $adminRole->syncPermissions($regularPermissions);
        }

        // Assign permissions to manager
        if ($managerRole) {
            $managerPermissions = $regularPermissions->filter(function($permission) {
                return in_array($permission->name, [
                    'view product', 'view sales report', 'view inventory', 'view customer',
                    'create product', 'edit product', 'process sale', 'view user',
                    'view expense', 'view supplier', 'view purchase_orders',
                ]);
            });
            $managerRole->syncPermissions($managerPermissions);
        }

        // Assign permissions to cashier
        if ($cashierRole) {
            $cashierPermissions = $regularPermissions->filter(function($permission) {
                return in_array($permission->name, [
                    'view product', 'process sale', 'apply discounts', 'view customer',
                    'create customer'
                ]);
            });
            $cashierRole->syncPermissions($cashierPermissions);
        }

        // Assign permissions to inventory clerk
        if ($inventoryClerkRole) {
            $inventoryPermissions = $regularPermissions->filter(function($permission) {
                return in_array($permission->name, [
                    'view product', 'view inventory', 'update stock levels', 'create inventory record',
                    'edit inventory', 'view supplier', 'create uom', 'view uom'
                ]);
            });
            $inventoryClerkRole->syncPermissions($inventoryPermissions);
        }

        // Create SUPER ADMIN user
        $superAdminUser = User::factory()->create([
            'name' => 'Super Admin',
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'telephone_number' => '0700000000',
            'job_title' => 'System Super Administrator',
            'department_id' => 2,
            'location_id' => 2,
            'profile_image' => '',
            'role_id' => $superAdminRole?->id,
            'status' => 'active',
            'tenant_id' => 2,
            'email' => 'superadmin@system.com',
            'password' => bcrypt('superadmin&#123')
        ]);
        $superAdminUser->assignRole('super_admin');

        // Create tenant admin user
        $adminUser = User::factory()->create([
            'name' => 'Admin User',
            'first_name' => 'Samu',
            'last_name' => 'Edu',
            'telephone_number' => '0700000001',
            'job_title' => 'System Administrator',
            'department_id' => 1,
            'location_id' => 1,
            'profile_image' => '',
            'role_id' => $adminRole?->id,
            'status' => 'active',
            'tenant_id' => $tenantId,
            'email' => 'samuelkiiraeluk@gmail.com',
            'password' => bcrypt('1234567890')
        ]);
        $adminUser->assignRole('admin');

        // Create trial user
        $trialUser = User::factory()->create([
            'name' => 'Trial User',
            'first_name' => 'Trial',
            'last_name' => 'User',
            'telephone_number' => '0700000002',
            'job_title' => 'System Administrator',
            'department_id' => 1,
            'location_id' => 1,
            'profile_image' => '',
            'role_id' => $adminRole?->id,
            'status' => 'active',
            'email' => 'trialuser@gmail.com',
            'tenant_id' => $tenantId,
            'password' => bcrypt('password@123')
        ]);
        $trialUser->assignRole('admin');
        
        // Create other users
        User::factory()->count(5)->create([
            'tenant_id' => $tenantId,
        ]);
    }
}