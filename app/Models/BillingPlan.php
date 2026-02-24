<?php
// app/Models/BillingPlan.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class BillingPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'billing_plans';
    protected $primaryKey = 'plan_id';

    protected $fillable = [
        'plan_code',
        'plan_name',
        'description',
        'monthly_price',
        'annual_price',
        'onetime_fee',
        'setup_fee',
        'currency',
        'default_shops',
        'default_payment_methods',
        'default_locations',
        'default_departments',
        'default_users',
        'default_products',
        'default_customers',
        'default_suppliers',
        'default_storage_gb',
        
        // Retail/POS Modules (Current)
        'includes_inventory',
        'includes_expenses',
        'includes_accounting',
        'includes_hr_payroll',
        'includes_multicurrency',
        'includes_advanced_reports',
        'includes_financial_reports',
        'includes_api_access',
        'includes_ecommerce',
        'includes_pos',
        'includes_crm',
        'includes_support_priority',
        'includes_custom_branding',
        
        // Front Desk / Reception Modules (Future Hotel)
        'includes_front_desk',
        'includes_housekeeping',
        'includes_room_management',
        'includes_reservations',
        'includes_guest_management',
        'includes_group_booking',
        'includes_travel_agent',
        'includes_night_audit',
        'includes_guest_history',
        'includes_lost_found',
        'includes_guest_services',
        
        // Events & Banqueting (Future Hotel)
        'includes_events_banqueting',
        'includes_event_booking',
        'includes_function_sheets',
        'includes_outside_catering',
        'includes_venue_management',
        'includes_equipment_management',
        
        // Maintenance & Assets (Future Hotel)
        'includes_asset_management',
        'includes_asset_register',
        'includes_maintenance_management',
        'includes_repair_jobs',
        'includes_maintenance_schedules',
        
        // Additional Hotel Features
        'includes_laundry_management',
        'includes_restaurant_management',
        'includes_hotel_management',
        'includes_booking_engine',
        'includes_channel_manager',
        'includes_phone_book',
        'includes_guest_preferences',
        
        // Basic Settings
        'trial_days',
        'billing_cycle_days',
        'is_public',
        'is_active',
        'sort_order',
        'features_list',
        'limitations',
    ];

    protected $casts = [
        'monthly_price' => 'decimal:2',
        'annual_price' => 'decimal:2',
        'onetime_fee' => 'decimal:2',
        'setup_fee' => 'decimal:2',
        
        // Retail/POS Modules
        'includes_inventory' => 'boolean',
        'includes_expenses' => 'boolean',
        'includes_accounting' => 'boolean',
        'includes_hr_payroll' => 'boolean',
        'includes_multicurrency' => 'boolean',
        'includes_advanced_reports' => 'boolean',
        'includes_financial_reports' => 'boolean',
        'includes_api_access' => 'boolean',
        'includes_ecommerce' => 'boolean',
        'includes_pos' => 'boolean',
        'includes_crm' => 'boolean',
        'includes_support_priority' => 'boolean',
        'includes_custom_branding' => 'boolean',
        
        // Front Desk Modules
        'includes_front_desk' => 'boolean',
        'includes_housekeeping' => 'boolean',
        'includes_room_management' => 'boolean',
        'includes_reservations' => 'boolean',
        'includes_guest_management' => 'boolean',
        'includes_group_booking' => 'boolean',
        'includes_travel_agent' => 'boolean',
        'includes_night_audit' => 'boolean',
        'includes_guest_history' => 'boolean',
        'includes_lost_found' => 'boolean',
        'includes_guest_services' => 'boolean',
        
        // Events Modules
        'includes_events_banqueting' => 'boolean',
        'includes_event_booking' => 'boolean',
        'includes_function_sheets' => 'boolean',
        'includes_outside_catering' => 'boolean',
        'includes_venue_management' => 'boolean',
        'includes_equipment_management' => 'boolean',
        
        // Maintenance Modules
        'includes_asset_management' => 'boolean',
        'includes_asset_register' => 'boolean',
        'includes_maintenance_management' => 'boolean',
        'includes_repair_jobs' => 'boolean',
        'includes_maintenance_schedules' => 'boolean',
        
        // Additional Modules
        'includes_laundry_management' => 'boolean',
        'includes_restaurant_management' => 'boolean',
        'includes_hotel_management' => 'boolean',
        'includes_booking_engine' => 'boolean',
        'includes_channel_manager' => 'boolean',
        'includes_phone_book' => 'boolean',
        'includes_guest_preferences' => 'boolean',
        
        'is_public' => 'boolean',
        'is_active' => 'boolean',
        'features_list' => 'array',
        'limitations' => 'array',
    ];

    /**
     * Apply this plan to a tenant (fills tenant_settings)
     */
    public function applyToTenant(int $tenantId, int $updatedBy = null): void
    {
        DB::transaction(function () use ($tenantId, $updatedBy) {
            // Clear existing plan-specific settings first
            TenantSetting::where('tenant_id', $tenantId)
                ->whereIn('category', ['limits', 'features', 'billing', 'hotel_features'])
                ->delete();

            $settings = $this->generateTenantSettings($updatedBy);
            
            foreach ($settings as $setting) {
                $setting['tenant_id'] = $tenantId;
                TenantSetting::create($setting);
            }
            
            // Cache clear is handled by TenantSetting booted method
        });
    }

    // ==================== SCOPES ====================

    /**
     * Scope for active plans
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for public plans
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    // ==================== METHODS ====================

    /**
     * Calculate yearly savings percentage
     */
    public function getYearlySavingsPercentage(): float
    {
        $monthlyTotal = $this->monthly_price * 12;
        $annualPrice = $this->annual_price;
        
        if ($annualPrice <= 0 || $monthlyTotal <= 0) {
            return 0;
        }
        
        return (($monthlyTotal - $annualPrice) / $monthlyTotal) * 100;
    }

    /**
     * Generate tenant settings array from this plan
     */
    public function generateTenantSettings(int $updatedBy = null): array
    {
        $settings = [];

        // ==================== LIMITS SETTINGS ====================
        $limits = [
            'max_shops' => $this->default_shops,
            'max_locations' => $this->default_locations,
            'max_payment_methods' => $this->default_payment_methods,
            'max_departments' => $this->default_departments,
            'max_users' => $this->default_users,
            'max_products' => $this->default_products,
            'max_customers' => $this->default_customers,
            'max_suppliers' => $this->default_suppliers,
            'max_storage_gb' => $this->default_storage_gb,
            'max_terminals_per_shop' => $this->determineTerminalLimit(),
            'max_api_keys' => $this->determineApiKeyLimit(),
            'max_webhooks' => $this->determineWebhookLimit(),
            'max_integrations' => $this->determineIntegrationLimit(),
            'data_retention_months' => $this->determineDataRetention(),
            'max_employees' => $this->default_users * 2,
            'max_rooms' => $this->determineRoomLimit(),
            'max_events' => $this->determineEventLimit(),
        ];

        foreach ($limits as $key => $value) {
            $settings[] = [
                'setting_key' => $key,
                'setting_value' => (string) $value,
                'data_type' => 'integer',
                'category' => 'limits',
                'updated_by' => $updatedBy,
            ];
        }

        // ==================== RETAIL/POS FEATURE MODULES ====================
        $retailModules = [
            'module_inventory' => $this->includes_inventory,
            'module_expenses' => $this->includes_expenses,
            'module_accounting' => $this->includes_accounting,
            'module_hr_payroll' => $this->includes_hr_payroll,
            'module_multicurrency' => $this->includes_multicurrency,
            'module_advanced_reports' => $this->includes_advanced_reports,
            'module_financial_reports' => $this->includes_financial_reports,
            'module_api_access' => $this->includes_api_access,
            'module_ecommerce' => $this->includes_ecommerce,
            'module_pos' => $this->includes_pos,
            'module_crm' => $this->includes_crm,
            'module_loyalty' => $this->determineLoyaltyModule(),
        ];

        foreach ($retailModules as $key => $value) {
            $settings[] = [
                'setting_key' => $key,
                'setting_value' => $value ? '1' : '0',
                'data_type' => 'boolean',
                'category' => 'features',
                'updated_by' => $updatedBy,
            ];
        }

        // ==================== HOTEL FRONT DESK MODULES ====================
        $hotelFrontDeskModules = [
            'module_front_desk' => $this->includes_front_desk ?? false,
            'module_housekeeping' => $this->includes_housekeeping ?? false,
            'module_room_management' => $this->includes_room_management ?? false,
            'module_reservations' => $this->includes_reservations ?? false,
            'module_guest_management' => $this->includes_guest_management ?? false,
            'module_group_booking' => $this->includes_group_booking ?? false,
            'module_travel_agent' => $this->includes_travel_agent ?? false,
            'module_night_audit' => $this->includes_night_audit ?? false,
            'module_guest_history' => $this->includes_guest_history ?? false,
            'module_lost_found' => $this->includes_lost_found ?? false,
            'module_guest_services' => $this->includes_guest_services ?? false,
            'module_phone_book' => $this->includes_phone_book ?? false,
            'module_guest_preferences' => $this->includes_guest_preferences ?? false,
        ];

        foreach ($hotelFrontDeskModules as $key => $value) {
            $settings[] = [
                'setting_key' => $key,
                'setting_value' => $value ? '1' : '0',
                'data_type' => 'boolean',
                'category' => 'hotel_features',
                'updated_by' => $updatedBy,
            ];
        }

        // ==================== EVENTS & BANQUETING MODULES ====================
        $eventsModules = [
            'module_events_banqueting' => $this->includes_events_banqueting ?? false,
            'module_event_booking' => $this->includes_event_booking ?? false,
            'module_function_sheets' => $this->includes_function_sheets ?? false,
            'module_outside_catering' => $this->includes_outside_catering ?? false,
            'module_venue_management' => $this->includes_venue_management ?? false,
            'module_equipment_management' => $this->includes_equipment_management ?? false,
        ];

        foreach ($eventsModules as $key => $value) {
            $settings[] = [
                'setting_key' => $key,
                'setting_value' => $value ? '1' : '0',
                'data_type' => 'boolean',
                'category' => 'hotel_features',
                'updated_by' => $updatedBy,
            ];
        }

        // ==================== MAINTENANCE & ASSETS MODULES ====================
        $maintenanceModules = [
            'module_asset_management' => $this->includes_asset_management ?? false,
            'module_asset_register' => $this->includes_asset_register ?? false,
            'module_maintenance_management' => $this->includes_maintenance_management ?? false,
            'module_repair_jobs' => $this->includes_repair_jobs ?? false,
            'module_maintenance_schedules' => $this->includes_maintenance_schedules ?? false,
        ];

        foreach ($maintenanceModules as $key => $value) {
            $settings[] = [
                'setting_key' => $key,
                'setting_value' => $value ? '1' : '0',
                'data_type' => 'boolean',
                'category' => 'hotel_features',
                'updated_by' => $updatedBy,
            ];
        }

        // ==================== ADDITIONAL HOTEL MODULES ====================
        $additionalHotelModules = [
            'module_laundry_management' => $this->includes_laundry_management ?? false,
            'module_restaurant_management' => $this->includes_restaurant_management ?? false,
            'module_hotel_management' => $this->includes_hotel_management ?? false,
            'module_booking_engine' => $this->includes_booking_engine ?? false,
            'module_channel_manager' => $this->includes_channel_manager ?? false,
        ];

        foreach ($additionalHotelModules as $key => $value) {
            $settings[] = [
                'setting_key' => $key,
                'setting_value' => $value ? '1' : '0',
                'data_type' => 'boolean',
                'category' => 'hotel_features',
                'updated_by' => $updatedBy,
            ];
        }

        // ==================== GENERAL FEATURES ====================
        $generalFeatures = [
            'allow_data_export' => $this->determineDataExport(),
            'auto_backup_enabled' => $this->determineAutoBackup(),
        ];

        foreach ($generalFeatures as $key => $value) {
            $settings[] = [
                'setting_key' => $key,
                'setting_value' => $value ? '1' : '0',
                'data_type' => 'boolean',
                'category' => 'features',
                'updated_by' => $updatedBy,
            ];
        }

        // ==================== BILLING SETTINGS ====================
        $billingSettings = [
            'billing_plan' => $this->plan_code,
            'subscription_status' => 'active',
            'trial_ends_at' => $this->trial_days > 0 ? now()->addDays($this->trial_days)->toDateTimeString() : null,
            'billing_cycle' => $this->billing_cycle_days . '_days',
            'plan_monthly_price' => $this->monthly_price,
            'plan_annual_price' => $this->annual_price,
            'plan_currency' => $this->currency,
        ];

        foreach ($billingSettings as $key => $value) {
            $dataType = in_array($key, ['plan_monthly_price', 'plan_annual_price']) ? 'decimal' : 
                       (in_array($key, ['trial_ends_at']) ? 'datetime' : 'string');
            
            $settings[] = [
                'setting_key' => $key,
                'setting_value' => $value !== null ? (string) $value : '',
                'data_type' => $dataType,
                'category' => 'billing',
                'updated_by' => $updatedBy,
            ];
        }

        return $settings;
    }

    /**
     * Determine terminal limit based on plan
     */
    private function determineTerminalLimit(): int
    {
        return match($this->plan_code) {
            'free', 'starter' => 1,
            'business' => 3,
            'enterprise', 'onetime_lifetime' => 10,
            default => 1,
        };
    }

    /**
     * Determine API key limit
     */
    private function determineApiKeyLimit(): int
    {
        return match($this->plan_code) {
            'free', 'starter' => 1,
            'business' => 3,
            'enterprise', 'onetime_lifetime' => 10,
            default => 1,
        };
    }

    /**
     * Determine webhook limit
     */
    private function determineWebhookLimit(): int
    {
        return match($this->plan_code) {
            'free' => 2,
            'starter' => 3,
            'business' => 5,
            'enterprise', 'onetime_lifetime' => 20,
            default => 2,
        };
    }

    /**
     * Determine integration limit
     */
    private function determineIntegrationLimit(): int
    {
        return match($this->plan_code) {
            'free' => 1,
            'starter' => 2,
            'business' => 5,
            'enterprise', 'onetime_lifetime' => 20,
            default => 1,
        };
    }

    /**
     * Determine data retention in months
     */
    private function determineDataRetention(): int
    {
        return match($this->plan_code) {
            'free' => 6,
            'starter' => 12,
            'business' => 24,
            'enterprise', 'onetime_lifetime' => 60,
            default => 12,
        };
    }

    /**
     * Determine room limit for hotel
     */
    private function determineRoomLimit(): int
    {
        return match($this->plan_code) {
            'free', 'starter' => 10,
            'business' => 50,
            'enterprise', 'onetime_lifetime' => 999999,
            default => 0,
        };
    }

    /**
     * Determine event limit
     */
    private function determineEventLimit(): int
    {
        return match($this->plan_code) {
            'free', 'starter' => 5,
            'business' => 20,
            'enterprise', 'onetime_lifetime' => 999999,
            default => 0,
        };
    }

    /**
     * Determine if loyalty module is included
     */
    private function determineLoyaltyModule(): bool
    {
        return in_array($this->plan_code, ['business', 'enterprise', 'onetime_lifetime']);
    }

    /**
     * Determine if data export is allowed
     */
    private function determineDataExport(): bool
    {
        return !in_array($this->plan_code, ['free']);
    }

    /**
     * Determine if auto backup is enabled
     */
    private function determineAutoBackup(): bool
    {
        return in_array($this->plan_code, ['business', 'enterprise', 'onetime_lifetime']);
    }

    /**
     * Get all features as array for display
     */
    public function getFeatures(): array
    {
        $features = [];
        
        $featureMap = [
            // Retail/POS Features
            'includes_inventory' => 'Inventory Management',
            'includes_expenses' => 'Expense Management',
            'includes_accounting' => 'Complete Accounting',
            'includes_hr_payroll' => 'HR & Payroll',
            'includes_multicurrency' => 'Multi-currency Support',
            'includes_advanced_reports' => 'Advanced Reports',
            'includes_financial_reports' => 'Financial Reports',
            'includes_api_access' => 'API Access',
            'includes_ecommerce' => 'E-commerce Integration',
            'includes_pos' => 'Point of Sale (POS)',
            'includes_crm' => 'Customer Relationship Management',
            
            // Front Desk Features
            'includes_front_desk' => 'Front Desk Management',
            'includes_housekeeping' => 'Housekeeping Management',
            'includes_room_management' => 'Room Inventory Management',
            'includes_reservations' => 'Reservations & Walk-ins',
            'includes_guest_management' => 'Guest Database & History',
            'includes_group_booking' => 'Groups Management',
            'includes_travel_agent' => 'Travel Agent Management',
            'includes_night_audit' => 'Automated Night Audit',
            'includes_lost_found' => 'Lost & Found Tracking',
            'includes_guest_services' => 'Guest Services',
            'includes_phone_book' => 'Phone Book',
            'includes_guest_preferences' => 'Guest Preferences',
            
            // Events Features
            'includes_events_banqueting' => 'Events & Banqueting',
            'includes_event_booking' => 'Event Bookings',
            'includes_function_sheets' => 'Function Sheets',
            'includes_outside_catering' => 'Outside Catering',
            'includes_venue_management' => 'Venue Management',
            'includes_equipment_management' => 'Equipment Management',
            
            // Maintenance Features
            'includes_asset_management' => 'Asset Management',
            'includes_asset_register' => 'Assets Register',
            'includes_maintenance_management' => 'Maintenance Management',
            'includes_repair_jobs' => 'Repair Jobs Tracking',
            'includes_maintenance_schedules' => 'Maintenance Schedules',
            
            // Additional Features
            'includes_laundry_management' => 'Laundry Management',
            'includes_restaurant_management' => 'Restaurant Management',
            'includes_hotel_management' => 'Hotel Management Suite',
            'includes_booking_engine' => 'Booking Engine',
            'includes_channel_manager' => 'Channel Manager',
            'includes_support_priority' => 'Priority Support',
            'includes_custom_branding' => 'Custom Branding',
        ];
        
        foreach ($featureMap as $field => $label) {
            if ($this->{$field} ?? false) {
                $features[] = $label;
            }
        }
        
        return $features;
    }
}