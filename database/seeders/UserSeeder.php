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

        // Define regular permissions
        $permissions = [
            // Product Management
            'create product',
            'view product',
            'edit product',
            'delete product',
            'update product',

            // Product Management
            'create variant',
            'view variant',
            'edit variant',
            'delete variant',
            'update variant',
            
            // Sales Management
            'process sale',
            'view sales report',
            'refund sale',
            'apply discounts',

            // Inventory Management
            'create inventory record',
            'view inventory',
            'edit inventory',
            'delete inventory',
            'update stock levels',

            // Customer Management
            'create customer',
            'view customer',
            'edit customer',
            'delete customer',

            // Admin Permissions
            'configure system',
            'manage roles',
            'manage permissions',

            // Unit of measure Management
            'create uom',
            'view uom',
            'edit uom',
            'delete uom',
            'update uom',
            
            // Department
            'create department',
            'view department',
            'edit department',
            'delete department',
            'update department',

            // Tenant mgt (regular tenant admin level)
            'create tenant',
            'view tenant',
            'edit tenant',
            'delete tenant',
            'update tenant',

            // User Management
            'create user',
            'view user',
            'edit user',
            'delete user',
            'update user',
            
            // Category Management
            'create category',
            'view category',
            'edit category',
            'delete category',
            'update category',
            
            // Location Management
            'create location',
            'view location',
            'edit location',
            'delete location',
            'update location',

            // subcategory Management
            'create subcategory',
            'view subcategory',
            'edit subcategory',
            'delete subcategory',
            'update subcategory',

            // tax Management
            'create tax',
            'view tax',
            'edit tax',
            'delete tax',
            'update tax',

            // employee Management
            'create employee',
            'view employee',
            'edit employee',
            'delete employee',
            'update employee',

            // employee payment Management
            'create employee payment',
            'view employee payment',
            'edit employee payment',
            'delete employee payment',
            'update employee payment',
            'edit completed payment',
            'delete completed payment',

            // tax Management
            'create employee-payment',
            'view employee-payment',
            'edit employee-payment',
            'delete employee-payment',
            'update employee-payment',

            // promotion Management
            'create promotion',
            'view promotion',
            'edit promotion',
            'delete promotion',
            'update promotion',

            // category-expense Management
            'create category-expense',
            'view category-expense',
            'edit category-expense',
            'delete category-expense',
            'update category-expense',

            // expense Management
            'create expense',
            'view expense',
            'edit expense',
            'delete expense',
            'update expense',
            'approve expense',
            'upload expense',

            // supplier Management
            'create supplier',
            'view supplier',
            'edit supplier',
            'delete supplier',
            'update supplier',

            // Typical purchase order permissions
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

            // Payment Method
            'create payment method',
            'view payment method',
            'edit payment method',
            'delete payment method',
            'update payment method',
            'update current balance',

            // Financial Reports
            'view payment-method report',
            'view account-balance report',
            'view transaction-ledger  report',
            'view income-settlement report',
            'view cash-flow report',
            'view Transaction-analysis report',
            'view expense-analysis report',
            'view payment-method-analysis report',
            'view Daily Summary report',
            'view monthly report',
            'view Reconcillation report',

            // Admin Management
            'admin only',
            'create role',
            'edit role',
            'delete role',
            'update role',
            'update permission',
            'update settings',
        ];

        // Create regular permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
                'is_super_admin' => false // 👈 MARK AS REGULAR
            ]);
        }

        // Define SUPER ADMIN ONLY permissions
        $superAdminOnlyPermissions = [
            'tenant_manage_all',
            'tenant_delete_any',
            'tenant_billing_manage',
            'system_settings_manage',
            'user_impersonate',
            'subscription_manage_all',
            'data_export_all',
            'audit_log_view_all',
            'backup_manage',
            'payment_gateway_manage',
            'super_admin_dashboard',
            'manage_all_tenants',
            'override_tenant_limits',
            'system_maintenance'
        ];

        // Create super admin only permissions
        foreach ($superAdminOnlyPermissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
                'is_super_admin' => true // 👈 MARK AS SUPER ADMIN
            ]);
        }

        // Get all permissions (regular + super admin only)
        $allPermissions = Permission::all();
        
        // Get regular permissions only (for tenant admins)
        $regularPermissions = Permission::whereNotIn('name', $superAdminOnlyPermissions)->get();

        // Assign ALL permissions to super_admin (global role)
        $superAdminRole = Role::where('name', 'super_admin')->first();
        $superAdminRole->syncPermissions($allPermissions);

        // Assign regular permissions to admin (tenant-specific role)
        $adminRole = Role::where('name', 'admin')->where('tenant_id', $tenantId)->first();
        $adminRole->syncPermissions($regularPermissions);

        // Assign basic permissions to other roles
        $managerRole = Role::where('name', 'manager')->where('tenant_id', $tenantId)->first();
        $managerPermissions = $regularPermissions->whereIn('name', [
            'view product', 'view sales report', 'view inventory', 'view customer',
            'create product', 'edit product', 'process sale', 'view user'
        ]);
        $managerRole->syncPermissions($managerPermissions);

        $cashierRole = Role::where('name', 'cashier')->where('tenant_id', $tenantId)->first();
        $cashierPermissions = $regularPermissions->whereIn('name', [
            'view product', 'process sale', 'apply discounts', 'view customer',
            'create customer'
        ]);
        $cashierRole->syncPermissions($cashierPermissions);

        $inventoryClerkRole = Role::where('name', 'inventory_clerk')->where('tenant_id', $tenantId)->first();
        $inventoryPermissions = $regularPermissions->whereIn('name', [
            'view product', 'view inventory', 'update stock levels', 'create inventory record',
            'edit inventory', 'view supplier'
        ]);
        $inventoryClerkRole->syncPermissions($inventoryPermissions);

        // Create SUPER ADMIN user (global user, no tenant)
        $superAdminUser = User::factory()->create([
            'name' => 'Super Admin',
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'telephone_number' => '0700000000',
            'job_title' => 'System Super Administrator',
            'department_id' => 2,
            'location_id' => 2,
            'profile_image' => '',
            'role_id' => $superAdminRole->id,
            'status' => 'active',
            'tenant_id' => 2, // Super admin has no tenant
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
            'role_id' => $adminRole->id,
            'status' => 'active',
            'tenant_id' => $tenantId,
            'email' => 'samuelkiiraeluk@gmail.com',
            'password' => bcrypt('1234567890')
        ]);
        $adminUser->assignRole('admin');

        // Create trial user
        $adminUser2 = User::factory()->create([
            'name' => 'Trial User',
            'first_name' => 'Trial',
            'last_name' => 'User',
            'telephone_number' => '0700000002',
            'job_title' => 'System Administrator',
            'department_id' => 1,
            'location_id' => 1,
            'profile_image' => '',
            'role_id' => $adminRole->id,
            'status' => 'active',
            'email' => 'trialuser@gmail.com',
            'tenant_id' => $tenantId,
            'password' => bcrypt('password@123')
        ]);
        $adminUser2->assignRole('admin');
        
        // Create other users
        User::factory()->count(5)->create([
            'tenant_id' => $tenantId,
        ]);
    }
}