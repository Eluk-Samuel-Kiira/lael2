<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

class AddNewPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:add-new
                            {--role= : Optionally assign new permissions to a specific role}
                            {--skip-admin : Skip assigning permissions to admin role}
                            {--skip-super-admin : Skip assigning permissions to super_admin role}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add new permissions that don\'t already exist in the database';

    /**
     * New permissions to add - only these will be processed
     */
    protected $newPermissions = [
        'Dashboard Management' => [
            'view dashboard sales',
            'view dashboard orders',
            'view dashboard profit',
            'view dashboard active users',
            'view dashboard weekly sales',
            'view dashboard best selling',
            'view dashboard top category',
            'view dashboard inventory alerts',
            'view dashboard recent orders',
            'view dashboard overview',
        ],
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting permission sync...');
        $this->newLine();

        $specificRole = $this->option('role');
        $skipAdmin = $this->option('skip-admin');
        $skipSuperAdmin = $this->option('skip-super-admin');

        $addedCount = 0;
        $skippedCount = 0;
        $allNewPermissions = [];

        // Collect all new permissions with their categories
        foreach ($this->newPermissions as $category => $permissions) {
            foreach ($permissions as $permissionName) {
                $allNewPermissions[] = [
                    'name' => $permissionName,
                    'category' => $category
                ];
            }
        }

        // Check which permissions already exist
        $existingPermissionNames = Permission::whereIn('name', array_column($allNewPermissions, 'name'))
            ->pluck('name')
            ->toArray();

        $permissionsToAdd = array_filter($allNewPermissions, function($perm) use ($existingPermissionNames) {
            return !in_array($perm['name'], $existingPermissionNames);
        });

        // Display summary
        $this->table(
            ['Status', 'Count'],
            [
                ['Total Permissions Checked', count($allNewPermissions)],
                ['Already Existing', count($allNewPermissions) - count($permissionsToAdd)],
                ['New Permissions to Add', count($permissionsToAdd)],
            ]
        );
        $this->newLine();

        if (empty($permissionsToAdd)) {
            $this->info('✅ No new permissions to add. All permissions are already in the database.');
            return 0;
        }

        // Display permissions that will be added
        $this->info('📋 New permissions to be added:');
        $permissionTableData = [];
        foreach ($permissionsToAdd as $perm) {
            $permissionTableData[] = [
                $perm['name'],
                $perm['category'],
                'New'
            ];
        }
        $this->table(['Permission Name', 'Category', 'Status'], $permissionTableData);
        $this->newLine();

        // Begin transaction
        DB::beginTransaction();

        try {
            // Add new permissions
            $this->info('📝 Adding new permissions...');
            foreach ($permissionsToAdd as $permData) {
                $permission = Permission::firstOrCreate([
                    'name' => $permData['name'],
                    'guard_name' => 'web',
                ], [
                    'is_super_admin' => false,
                    'category' => $permData['category']
                ]);

                if ($permission->wasRecentlyCreated) {
                    $addedCount++;
                    $this->line("  ✅ Added: {$permData['name']} ({$permData['category']})");
                } else {
                    $skippedCount++;
                    $this->line("  ⏭️  Skipped (already exists): {$permData['name']}");
                }
            }
            $this->newLine();

            // Get all new permissions
            $newPermissionNames = array_column($permissionsToAdd, 'name');
            $newPermissions = Permission::whereIn('name', $newPermissionNames)->get();

            // Assign to super_admin role (unless skipped)
            if (!$skipSuperAdmin) {
                $superAdminRole = Role::where('name', 'super_admin')->first();
                if ($superAdminRole) {
                    $superAdminRole->givePermissionTo($newPermissions);
                    $this->info("✅ Assigned " . count($newPermissions) . " new permissions to super_admin role");
                } else {
                    $this->warn("⚠️  super_admin role not found. Permissions not assigned.");
                }
            } else {
                $this->line("⏭️  Skipped assigning to super_admin role");
            }

            // Assign to admin role (unless skipped)
            if (!$skipAdmin) {
                $adminRole = Role::where('name', 'admin')->first();
                if ($adminRole) {
                    $adminRole->givePermissionTo($newPermissions);
                    $this->info("✅ Assigned " . count($newPermissions) . " new permissions to admin role");
                } else {
                    $this->warn("⚠️  admin role not found. Permissions not assigned.");
                }
            } else {
                $this->line("⏭️  Skipped assigning to admin role");
            }

            // Assign to specific role if provided
            if ($specificRole) {
                $role = Role::where('name', $specificRole)->first();
                if ($role) {
                    $role->givePermissionTo($newPermissions);
                    $this->info("✅ Assigned " . count($newPermissions) . " new permissions to {$specificRole} role");
                } else {
                    $this->warn("⚠️  Role '{$specificRole}' not found. Permissions not assigned.");
                }
            }

            DB::commit();

            $this->newLine();
            $this->info('✅ Permission sync completed successfully!');
            $this->info("📊 Summary: Added {$addedCount} new permissions, Skipped {$skippedCount} existing");
            
            // Show which roles got the permissions
            $this->newLine();
            $this->info('🎯 Permissions assigned to:');
            $rolesWithNewPermissions = [];
            
            if (!$skipSuperAdmin && Role::where('name', 'super_admin')->exists()) {
                $rolesWithNewPermissions[] = 'super_admin';
            }
            if (!$skipAdmin && Role::where('name', 'admin')->exists()) {
                $rolesWithNewPermissions[] = 'admin';
            }
            if ($specificRole && Role::where('name', $specificRole)->exists()) {
                $rolesWithNewPermissions[] = $specificRole;
            }
            
            $this->line('  - ' . implode("\n  - ", $rolesWithNewPermissions));

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Error occurred: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }

        return 0;
    }
}