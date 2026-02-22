<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{ Tenant, TenantConfiguration, TenantSetting, TenantUsageTracking, Setting };
use Illuminate\Support\Facades\{ Hash, Mail, Auth, Log, DB };
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;
use App\Mail\NewUserMail;

class TenantController extends Controller
{
    public function __construct()
    {
        //
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        
        $user = Auth::user();
        if (!$user->hasRole('super_admin')) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ]);
        }

        $tenants = Tenant::with([
            'configuration', 
            'adminUsers',
            'usageTracking' => function($query) {
                $query->latest('tracking_date')->limit(5); // Get last 5 usage records
            }, 
            'settings' => function($query) {
                $query->orderBy('category')->orderBy('setting_key');
            }, 
            'appSettings',
            'latestUsage'
        ])->latest()->get();

        $bladeToReload = $request->query('bladeFileToReload');
        switch ($bladeToReload) {
            case 'tenantIndexTable':
                return view('tenant.partials.component', [
                    'tenants' => $tenants,
                ]);
            default:
                return view('tenant.tenant-index', [
                    'tenants' => $tenants,
                ]);
        }
    }

    /**
     * Get tenant details for modal (AJAX endpoint)
     */
    public function getTenantDetails($id)
    {
        $tenant = Tenant::with([
            'configuration', 
            'usageTracking' => function($query) {
                $query->latest('tracking_date')->limit(10);
            },
            'settings',
            'appSettings'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'tenant' => $tenant
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = Auth::user();
        if (!$user->hasRole('super_admin')) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ]);
        }
        return view('tenant.partials.create');
    }

    /**
     * Store Step 1 - Basic Information
     */
    public function storeStep1(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasRole('super_admin')) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ]);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'subdomain' => 'required|string|unique:tenants,subdomain|max:255|regex:/^[a-z0-9-.]+$/',
            'status' => 'required|in:active,suspended,trial',
        ]);

        try {
            DB::beginTransaction();
            
            // Create tenant
            $tenant = Tenant::create([
                'name' => $request->name,
                'subdomain' => $request->subdomain,
                'status' => $request->status,
            ]);
            
            DB::commit();
            
            // Store tenant ID in session for next steps
            session(['creating_tenant_id' => $tenant->id]);
            
            return response()->json([
                'success' => true,
                'message' => 'Step 1 completed successfully',
                'tenant_id' => $tenant->id
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error creating tenant: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store Step 2 - Configuration
     */
    public function storeStep2(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasRole('super_admin')) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ]);
        }

        $tenantId = session('creating_tenant_id');
        
        if (!$tenantId) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant session expired. Please start over.'
            ], 400);
        }

        $request->validate([
            'currency_code' => 'required|string|size:3',
            'timezone' => 'required|string|timezone',
            'locale' => 'required|string|max:10',
            'fiscal_year_start' => 'required|date',
            'tax_calculation_method' => 'required|in:exclusive,inclusive',
        ]);

        try {
            DB::beginTransaction();
            
            $tenant = Tenant::findOrFail($tenantId);
            
            // Create or update configuration
            $configuration = $tenant->configuration()->updateOrCreate(
                ['tenant_id' => $tenantId],
                [
                    'currency_code' => $request->currency_code,
                    'timezone' => $request->timezone,
                    'locale' => $request->locale,
                    'fiscal_year_start' => $request->fiscal_year_start,
                    'tax_calculation_method' => $request->tax_calculation_method,
                ]
            );
            
            // Get currency details from config
            $currencies = config('currencies.currencies');
            $currencyDetails = $currencies[$request->currency_code] ?? [
                'name' => $request->currency_code,
                'symbol' => '$',
                'symbol_position' => 'before',
                'decimal_places' => 2
            ];
            
            // Create base currency for this tenant
            $currency = $tenant->currencies()->create([
                'code' => $request->currency_code,
                'name' => $currencyDetails['name'],
                'symbol' => $currencyDetails['symbol'],
                'symbol_position' => $currencyDetails['symbol_position'],
                'decimal_places' => $currencyDetails['decimal_places'],
                'exchange_rate' => 1.00000000,
                'created_by' => $user->id,
                'is_active' => 1,
                'is_base_currency' => 1,
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Step 2 completed successfully',
                'configuration' => $configuration,
                'currency' => $currency
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error saving configuration: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store Step 3 - Settings
     */
    public function storeStep3(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasRole('super_admin')) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ]);
        }

        $tenantId = session('creating_tenant_id');
        
        if (!$tenantId) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant session expired. Please start over.'
            ], 400);
        }

        $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'required',
            'settings.*.data_type' => 'required|in:string,integer,boolean,json',
            'settings.*.category' => 'required|string',
        ]);

        try {
            DB::beginTransaction();
            
            $tenant = Tenant::findOrFail($tenantId);
            
            // Delete existing settings (if any)
            $tenant->settings()->delete();
            
            // Create new settings
            foreach ($request->settings as $setting) {
                $tenant->settings()->create([
                    'setting_key' => $setting['key'],
                    'setting_value' => $setting['value'],
                    'data_type' => $setting['data_type'],
                    'category' => $setting['category'],
                ]);
            }
            
            DB::commit();
            
            // Clear session after completion
            session()->forget('creating_tenant_id');
            
            return response()->json([
                'success' => true,
                'message' => 'Tenant created successfully!',
                'redirect' => route('tenant.index')
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error saving settings: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Get current step based on session
     */
    public function getCurrentStep()
    {
        $tenantId = session('creating_tenant_id');
        
        if (!$tenantId) {
            return response()->json([
                'has_session' => false,
                'step' => 1
            ]);
        }
        
        try {
            $tenant = Tenant::with(['configuration', 'settings'])->find($tenantId);
            
            if (!$tenant) {
                session()->forget('creating_tenant_id');
                return response()->json([
                    'has_session' => false,
                    'step' => 1
                ]);
            }
            
            // Determine current step based on what data exists
            $step = 1;
            $data = [];
            
            if ($tenant) {
                $step = 2; // Tenant exists, step 2
                $data['tenant'] = [
                    'name' => $tenant->name,
                    'subdomain' => $tenant->subdomain,
                    'status' => $tenant->status
                ];
            }
            
            if ($tenant->configuration) {
                $step = 3; // Configuration exists, step 3
                $data['configuration'] = $tenant->configuration;
            }
            
            if ($tenant->settings && $tenant->settings->count() > 0) {
                $step = 4; // Settings exist, step 4 (completed)
            }
            
            return response()->json([
                'has_session' => true,
                'step' => $step,
                'tenant_id' => $tenantId,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'has_session' => false,
                'step' => 1,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Reset tenant creation session
     */
    public function resetStep()
    {
        session()->forget('creating_tenant_id');
        
        return response()->json([
            'success' => true,
            'message' => 'Session reset successfully'
        ]);
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
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }


    public function addAdminUser(Request $request, $tenantId)
    {
        $user = Auth::user();
        if (!$user->hasRole('super_admin')) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ]);
        }

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'telephone_number' => 'required|string|max:20',
            'job_title' => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        try {
            DB::beginTransaction();

            // Find or create admin role
            $adminRole = Role::firstOrCreate(['name' => 'admin']);

            $randomPassword = Str::random(10);
            $hashedPassword = Hash::make($randomPassword);

            // Create the admin user
            $adminUser = User::create([
                'name' => $request->first_name . ' ' . $request->last_name,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'telephone_number' => $request->telephone_number,
                'job_title' => $request->job_title,
                'password' => $hashedPassword,
                'department_id' => 1,
                'location_id' => 1,
                'status' => $request->status,
                'tenant_id' => $tenantId,
                'role_id' => $adminRole->id,
                'profile_image' => null,
                'email_verified_at' => now(),
            ]);

            // Assign role using Spatie
            if ($adminUser) {
                $adminUser->assignRole('admin');

                Mail::to($adminUser->email)->send(new NewUserMail(
                    $adminUser->first_name . ' ' . $adminUser->last_name,
                    $adminUser->userRole->name ?? 'No Role Assigned',  
                    $adminUser->departmentName->name ?? 'Sales', 
                    $adminUser->email,
                    $randomPassword
                ));
            }

            DB::commit();

            // SUCCESS REDIRECT - with success message
            return redirect()
                ->route('tenant.index') // Use 'tenant.index' not 'tenant.tenant-index'
                ->with('success', 'Admin user created successfully. Password has been sent to their email.');

        } catch (\Exception $e) {
            DB::rollBack();
            
            // ERROR REDIRECT - back to previous page with error message
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error creating admin user: ' . $e->getMessage());
        }
    }

}
