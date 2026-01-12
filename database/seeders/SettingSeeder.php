<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all tenants
        $tenants = Tenant::all();
        
        // Global settings (for super admin/system)
        Setting::updateOrCreate(
            ['tenant_id' => null],
            [
                'app_name' => 'LAEL POS System',
                'favicon' => null,
                'logo' => null,
                'app_email' => 'support@laelpos.com',
                'app_contact' => '+1 (555) 123-4567',
                'meta_keyword' => 'Best POS System, Inventory Management, SaaS',
                'meta_descrip' => 'LAEL POS is a comprehensive Point of Sale system for modern businesses.',
                'mail_status' => 'enabled',
                'mail_mailer' => 'smtp',
                'mail_host' => 'smtp.gmail.com',
                'mail_port' => '465',
                'mail_username' => 'support@laelpos.com',
                'mail_password' => 'securepassword',
                'mail_encryption' => 'tls',
                'mail_address' => 'noreply@laelpos.com',
                'mail_name' => 'LAEL POS Support',
                'menu_nav_color' => '#3498db',
                'font_family' => 'Cursive',
                'font_size' => '1.3',
                'locale' => 'en',
                'currency' => 'USD',
                'public_key' => 'LAEL-SYS-GLOBAL-001',
                'license_type' => 'enterprise',
                'license_expires_at' => null,
                'license_active' => true,
                'max_users' => 1000,
                'max_products' => 10000,
                'max_departments' => 100,
                'max_categories' => 500,
                'max_suppliers' => 1000,
                'enable_inventory' => true,
                'enable_multi_location' => true,
                'enable_reports' => true,
                'enable_api' => true,
                'enable_backup' => true,
                'storage_limit_mb' => 10240,
                'created_by' => 1, // Super admin user ID
            ]
        );
        
        // Create settings for each tenant
        foreach ($tenants as $tenant) {
            $this->createTenantSettings($tenant);
        }
    }
    
    /**
     * Create default settings for a tenant
     */
    private function createTenantSettings(Tenant $tenant)
    {
        $tenantName = $tenant->name;
        $tenantId = $tenant->id;
        
        // Find a user from this tenant to set as created_by
        $user = User::where('tenant_id', $tenantId)->first();
        $createdBy = $user ? $user->id : null;
        
        // Generate license key for tenant
        $publicKey = 'LAEL-' . strtoupper(substr($tenantName, 0, 4)) . '-' . 
                    strtoupper(\Illuminate\Support\Str::random(4)) . '-' . 
                    strtoupper(\Illuminate\Support\Str::random(4));
        
        $settings = [
            'tenant_id' => $tenantId,
            'app_name' => $tenantName . ' POS',
            'favicon' => null,
            'logo' => null,
            'app_email' => 'info@' . strtolower(str_replace(' ', '', $tenantName)) . '.com',
            'app_contact' => '+1 (555) 000-0000',
            'meta_keyword' => $tenantName . ', POS, Inventory',
            'meta_descrip' => $tenantName . ' - Professional Point of Sale System',
            'mail_status' => 'enabled',
            'mail_mailer' => 'smtp',
            'mail_host' => 'smtp.gmail.com',
            'mail_port' => '587',
            'mail_username' => 'info@' . strtolower(str_replace(' ', '', $tenantName)) . '.com',
            'mail_password' => 'tenantpassword123',
            'mail_encryption' => 'tls',
            'mail_address' => 'noreply@' . strtolower(str_replace(' ', '', $tenantName)) . '.com',
            'mail_name' => $tenantName . ' Support',
            'menu_nav_color' => $this->generateRandomColor(),
            'font_family' => 'Cursive',
            'font_size' => '1.3',
            'locale' => 'en',
            'currency' => 'USD',
            'public_key' => $publicKey,
            'private_key' => 'tenant-secret-key-' . $tenantId,
            'license_type' => $tenant->status === 'trial' ? 'trial' : 'premium',
            'license_expires_at' => $tenant->status === 'trial' ? now()->addDays(30) : null,
            'license_active' => $tenant->status !== 'suspended',
            'max_users' => $this->getUserLimit($tenant->status),
            'max_products' => $this->getProductLimit($tenant->status),
            'max_departments' => $this->getDepartmentLimit($tenant->status),
            'max_categories' => $this->getCategoryLimit($tenant->status),
            'max_suppliers' => $this->getSupplierLimit($tenant->status),
            'enable_inventory' => true,
            'enable_multi_location' => $tenant->status !== 'trial',
            'enable_reports' => true,
            'enable_api' => $tenant->status !== 'trial',
            'enable_backup' => $tenant->status !== 'trial',
            'storage_limit_mb' => $this->getStorageLimit($tenant->status),
            'created_by' => $createdBy,
        ];
        
        Setting::updateOrCreate(
            ['tenant_id' => $tenantId],
            $settings
        );
    }
    
    /**
     * Generate a random color for menu navigation
     */
    private function generateRandomColor()
    {
        $colors = [
            '#3498db', // Blue
            '#2ecc71', // Green
            '#e74c3c', // Red
            '#9b59b6', // Purple
            '#1abc9c', // Teal
            '#f39c12', // Orange
            '#34495e', // Dark blue
            '#16a085', // Dark teal
            '#8e44ad', // Dark purple
            '#2c3e50', // Navy
        ];
        
        return $colors[array_rand($colors)];
    }
    
    /**
     * Get user limit based on tenant status
     */
    private function getUserLimit($status)
    {
        return match($status) {
            'trial' => 5,
            'active' => 50,
            'suspended' => 0,
            default => 10,
        };
    }
    
    /**
     * Get product limit based on tenant status
     */
    private function getProductLimit($status)
    {
        return match($status) {
            'trial' => 100,
            'active' => 1000,
            'suspended' => 0,
            default => 500,
        };
    }
    
    /**
     * Get department limit based on tenant status
     */
    private function getDepartmentLimit($status)
    {
        return match($status) {
            'trial' => 3,
            'active' => 20,
            'suspended' => 0,
            default => 10,
        };
    }
    
    /**
     * Get category limit based on tenant status
     */
    private function getCategoryLimit($status)
    {
        return match($status) {
            'trial' => 10,
            'active' => 100,
            'suspended' => 0,
            default => 50,
        };
    }
    
    /**
     * Get supplier limit based on tenant status
     */
    private function getSupplierLimit($status)
    {
        return match($status) {
            'trial' => 20,
            'active' => 200,
            'suspended' => 0,
            default => 100,
        };
    }
    
    /**
     * Get storage limit based on tenant status
     */
    private function getStorageLimit($status)
    {
        return match($status) {
            'trial' => 1024, // 1GB
            'active' => 5120, // 5GB
            'suspended' => 0,
            default => 2048, // 2GB
        };
    }
}