// In controllers
public function index($tenantId)
{
    if (tenant_is_single_shop($tenantId)) {
        // Single shop logic
    }
    
    $limits = tenant_limits($tenantId);
    $features = tenant_features($tenantId);
    
    return view('dashboard', compact('limits', 'features'));
}

// In blade templates
@if(tenant_is_single_shop($tenantId))
    <div class="alert alert-info">
        Single shop mode
    </div>
@endif

@if(tenant_module_enabled($tenantId, 'inventory'))
    <x-inventory-widget />
@endif

// In validation
public function createShop($tenantId)
{
    $currentShops = Shop::where('tenant_id', $tenantId)->count();
    
    if (!tenant_can_create_shops($tenantId, $currentShops)) {
        abort(403, 'Maximum shop limit reached');
    }
    
    // Create shop...
}

// In services
if (tenant_is_on_plan($tenantId, 'enterprise')) {
    // Enterprise features
}

// Update settings
tenant_setting_set($tenantId, 'max_users', 10, 'integer', 'limits');


Filter the roles and permissions for the tenant and permissions for super admin

// Get only super admin permissions
$superAdminPermissions = Permission::superAdmin()->get();

// Get only regular permissions
$regularPermissions = Permission::regular()->get();

// Check if a permission is super admin only
if ($permission->isSuperAdminOnly()) {
    // Handle super admin permission
}

// In your role management
public function getAvailablePermissions()
{
    if (auth()->user()->hasRole('super_admin')) {
        return Permission::all(); // Super admin sees all
    }
    
    return Permission::regular()->get(); // Others see only regular permissions
}