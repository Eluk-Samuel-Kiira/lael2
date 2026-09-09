<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{ Tenant, TenantConfiguration, TenantSetting, TenantUsageTracking, Setting, BillingPlan };
use Illuminate\Support\Facades\{ Artisan, Hash, Mail, Auth, Log, DB };
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;
use App\Mail\NewUserMail;
use Illuminate\Support\Facades\Validator;


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
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.not_authorized'),
                ]);
            }
            abort(403, __('payments.not_authorized'));
        }

        // Get per_page from request, default to 15
        $perPage = $request->input('per_page', 15);
        
        // Validate per_page is in allowed values
        $allowedPerPage = [15, 25, 50, 100];
        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 15;
        }

        // Build the query with relationships
        $query = Tenant::with([
            'configuration', 
            'adminUsers',
            'usageTracking' => function($query) {
                $query->latest('tracking_date')->limit(5);
            }, 
            'settings' => function($query) {
                $query->orderBy('category')->orderBy('setting_key');
            }, 
            'appSettings',
            'latestUsage'
        ]);
        
        // Apply search if provided
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('subdomain', 'like', "%{$search}%")
                ->orWhere('status', 'like', "%{$search}%");
            });
        }
        
        // Paginate with dynamic per_page
        $tenants = $query->latest()->paginate($perPage);
        
        // Preserve per_page and search in pagination links
        $tenants->appends(['per_page' => $perPage, 'search' => $request->search]);

        $bladeToReload = $request->query('bladeFileToReload');
        
        // For AJAX requests - return just the component HTML
        if ($request->ajax() && $bladeToReload === 'reloadtenantComponent') {
            return view('tenant.partials.component', [
                'tenants' => $tenants,
            ])->render();
        }
        
        // Regular page load
        return view('tenant.tenant-index', [
            'tenants' => $tenants,
        ]);
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
            abort(403, __('payments.not_authorized'));
        }
        $plans = BillingPlan::active()->public()->orderBy('sort_order')->get();
        return view('tenant.partials.create', compact('plans'));
    }


    /**
     * Store a newly created tenant with plan selection
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasRole('super_admin')) {
            abort(403, __('payments.not_authorized'));
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'subdomain' => 'required|string|unique:tenants,subdomain|max:255|regex:/^[a-z0-9-.]+$/',
            'status' => 'required|in:active,suspended,trial',
            'plan_id' => 'required|exists:billing_plans,plan_id',
            'currency_code' => 'required|string|size:3',
            'timezone' => 'required|string|timezone',
            'locale' => 'required|string|max:10',
            'fiscal_year_start' => 'required|date',
            'tax_calculation_method' => 'required|in:exclusive,inclusive',
            'trial_ends_at' => 'nullable|date|after:today',
        ]);

        try {
            DB::beginTransaction();
            
            // Create tenant
            $tenant = Tenant::create([
                'name' => $request->name,
                'subdomain' => $request->subdomain,
                'status' => $request->status,
            ]);
            
            // Create tenant configuration
            $configuration = $tenant->configuration()->create([
                'currency_code' => $request->currency_code,
                'timezone' => $request->timezone,
                'locale' => $request->locale,
                'fiscal_year_start' => $request->fiscal_year_start,
                'tax_calculation_method' => $request->tax_calculation_method,
            ]);

            // Save configuration as tenant settings
            $tenant->settings()->createMany([
                [
                    'setting_key' => 'currency_code',
                    'setting_value' => $request->currency_code,
                    'data_type' => 'string',
                    'category' => 'general',
                    'updated_by' => $user->id,
                ],
                [
                    'setting_key' => 'timezone',
                    'setting_value' => $request->timezone,
                    'data_type' => 'string',
                    'category' => 'general',
                    'updated_by' => $user->id,
                ],
                [
                    'setting_key' => 'locale',
                    'setting_value' => $request->locale,
                    'data_type' => 'string',
                    'category' => 'general',
                    'updated_by' => $user->id,
                ],
                [
                    'setting_key' => 'fiscal_year_start',
                    'setting_value' => $request->fiscal_year_start,
                    'data_type' => 'date',
                    'category' => 'general',
                    'updated_by' => $user->id,
                ],
                [
                    'setting_key' => 'tax_calculation_method',
                    'setting_value' => $request->tax_calculation_method,
                    'data_type' => 'string',
                    'category' => 'general',
                    'updated_by' => $user->id,
                ],
            ]);
            
            // Get currency details from config
            $currencies = config('currencies.currencies');
            $currencyDetails = $currencies[$request->currency_code] ?? [
                'name' => $request->currency_code,
                'symbol' => '$',
                'symbol_position' => 'before',
                'decimal_places' => 2
            ];
            
            // Create base currency
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
            
            // Get selected plan and apply settings
            $plan = BillingPlan::findOrFail($request->plan_id);
            $plan->applyToTenant($tenant->id, $user->id);
            
            // Update or create trial_ends_at if provided
            if ($request->filled('trial_ends_at')) {
                $tenant->settings()->updateOrCreate(
                    ['setting_key' => 'trial_ends_at'],
                    [
                        'setting_value' => $request->trial_ends_at,
                        'data_type' => 'datetime',
                        'category' => 'billing',
                        'updated_by' => $user->id,
                    ]
                );
            }
            
            // ✅ Add next billing date calculation
            $this->calculateAndSetNextBillingDate($tenant->id);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Tenant created successfully with ' . $plan->plan_name,
                'redirect' => route('tenant.index')
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
     * Calculate and set the next billing date for a tenant
     */
    private function calculateAndSetNextBillingDate(int $tenantId)
    {
        // Get billing settings
        $settings = TenantSetting::where('tenant_id', $tenantId)
            ->where('category', 'billing')
            ->get()
            ->keyBy('setting_key');
        
        // Check if lifetime license
        $isLifetime = isset($settings['is_lifetime']) && (bool)$settings['is_lifetime']->setting_value;
        
        if ($isLifetime) {
            // For lifetime licenses, set next billing date to null or far future
            TenantSetting::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'setting_key' => 'next_billing_date',
                ],
                [
                    'setting_value' => null,
                    'data_type' => 'datetime',
                    'category' => 'billing',
                ]
            );
            return;
        }
        
        // Get trial ends at
        $trialEndsAtSetting = $settings['trial_ends_at'] ?? null;
        $trialEndsAt = $trialEndsAtSetting && $trialEndsAtSetting->setting_value 
            ? \Carbon\Carbon::parse($trialEndsAtSetting->setting_value) 
            : null;
        
        // Get billing cycle days
        $cycleDaysSetting = $settings['billing_cycle_days'] ?? null;
        $cycleDays = $cycleDaysSetting ? (int)$cycleDaysSetting->setting_value : 30;
        
        // Get billing plan
        $billingPlan = $settings['billing_plan'] ?? null;
        $planCode = $billingPlan ? $billingPlan->setting_value : 'free';
        
        $now = \Carbon\Carbon::now();
        $nextBillingDate = null;
        
        if ($trialEndsAt && $now->lessThan($trialEndsAt)) {
            // If still in trial, next billing is after trial ends
            $nextBillingDate = $trialEndsAt->copy()->addDays($cycleDays);
        } elseif ($trialEndsAt && $now->greaterThan($trialEndsAt)) {
            // If trial ended, next billing is from now + cycle
            $nextBillingDate = $now->copy()->addDays($cycleDays);
        } else {
            // No trial, start from now
            $nextBillingDate = $now->copy()->addDays($cycleDays);
        }
        
        // Set next billing date
        TenantSetting::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'setting_key' => 'next_billing_date',
            ],
            [
                'setting_value' => $nextBillingDate->toDateTimeString(),
                'data_type' => 'datetime',
                'category' => 'billing',
            ]
        );
        
        return $nextBillingDate;
    }



    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified tenant.
     */
    public function edit(string $id)
    {
        $user = Auth::user();
        if (!$user->hasRole('super_admin')) {
            abort(403, __('payments.not_authorized'));
        }

        $tenant = Tenant::with(['configuration', 'settings'])->findOrFail($id);
        
        // Get current plan from settings
        $currentPlanSetting = $tenant->settings()->where('setting_key', 'billing_plan')->first();
        $currentPlanCode = $currentPlanSetting ? $currentPlanSetting->setting_value : 'free';
        
        // Get all active plans for possible upgrade/downgrade
        $plans = BillingPlan::active()->public()->orderBy('sort_order')->get();
        
        // Get current plan details
        $currentPlan = BillingPlan::where('plan_code', $currentPlanCode)->first();
        
        return view('tenant.partials.edit', compact('tenant', 'plans', 'currentPlan'));
    }

    /**
     * Update the specified tenant.
     */
    public function update(Request $request, string $id)
    {
        $user = Auth::user();
        if (!$user->hasRole('super_admin')) {
            abort(403, __('payments.not_authorized'));
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|in:active,suspended,trial',
            'plan_id' => 'sometimes|exists:billing_plans,plan_id',
            'trial_ends_at' => 'nullable|date|after:today',
        ]);

        try {
            DB::beginTransaction();
            
            $tenant = Tenant::findOrFail($id);
            
            // Update basic info
            $tenant->update([
                'name' => $request->name,
                'status' => $request->status,
            ]);
            
            // Check if plan is being changed
            if ($request->has('plan_id') && !empty($request->plan_id)) {
                $newPlan = BillingPlan::findOrFail($request->plan_id);
                
                // Check if plan actually changed
                $currentPlan = TenantSetting::where('tenant_id', $tenant->id)
                    ->where('setting_key', 'billing_plan')
                    ->first();
                
                if (!$currentPlan || $currentPlan->setting_value != $newPlan->plan_code) {
                    // Plan changed - apply new plan settings
                    $newPlan->applyToTenant($tenant->id, $user->id);
                }
            }
            
            // Handle trial_ends_at separately
            if ($request->has('trial_ends_at')) {
                if ($request->filled('trial_ends_at')) {
                    TenantSetting::updateOrCreate(
                        [
                            'tenant_id' => $tenant->id,
                            'setting_key' => 'trial_ends_at'
                        ],
                        [
                            'setting_value' => $request->trial_ends_at,
                            'data_type' => 'datetime',
                            'category' => 'billing',
                            'updated_by' => $user->id,
                        ]
                    );
                } else {
                    TenantSetting::where('tenant_id', $tenant->id)
                        ->where('setting_key', 'trial_ends_at')
                        ->delete();
                }
            }
            
            DB::commit();
            
            // Clear cache
            tenant_clear_settings_cache($tenant->id);

            session()->flash('toast', [
                'type' => 'success',
                'message' => __('payments.tenant_updated'),
            ]);
            
            return redirect()->route('tenant.index');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error updating tenant: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = Auth::user();
        if (!$user->hasRole('super_admin')) {
            abort(403, __('payments.not_authorized'));
        }

        try {
            $tenant = Tenant::findOrFail($id);
            $tenant->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Tenant deleted successfully',
                'reload' => true
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting tenant: ' . $e->getMessage()
            ], 500);
        }
    }


    public function addAdminUser(Request $request, $tenantId)
    {
        $user = Auth::user();
        if (!$user->hasRole('super_admin')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'telephone_number' => 'required|string|max:20',
            'job_title' => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors occurred.',
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

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
                
                // ✅ Try to send email but don't fail if it doesn't work
                try {
                    Mail::to($adminUser->email)->send(new NewUserMail(
                        $adminUser->first_name . ' ' . $adminUser->last_name,
                        $adminUser->userRole->name ?? 'No Role Assigned',  
                        $adminUser->departmentName->name ?? 'Sales', 
                        $adminUser->email,
                        $randomPassword
                    ));
                } catch (\Exception $mailException) {
                    // ✅ Log the email error but don't rollback the transaction
                    \Log::error('Failed to send admin user email: ' . $mailException->getMessage(), [
                        'user_id' => $adminUser->id,
                        'email' => $adminUser->email,
                        'tenant_id' => $tenantId,
                    ]);
                    
                    // ✅ Store the error in a variable to include in response
                    $emailError = $mailException->getMessage();
                }
            }

            DB::commit();

            // ✅ Build response with warning if email failed
            $response = [
                'success' => true,
                'message' => 'Admin user created successfully.',
                'user' => [
                    'id' => $adminUser->id,
                    'name' => $adminUser->name,
                    'email' => $adminUser->email,
                ]
            ];

            // ✅ If email failed, add warning to response
            if (isset($emailError)) {
                $response['message'] = 'Admin user created successfully but email could not be sent.';
                $response['warning'] = 'Password could not be emailed to user. Please reset password manually.';
                $response['email_error'] = $emailError;
            } else {
                $response['message'] = 'Admin user created successfully. Password has been sent to their email.';
            }

            return response()->json($response);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error creating admin user: ' . $e->getMessage(),
            ], 500);
        }
    }

    
    /**
     * Refresh billing plans by updating existing or creating new ones
     */
    public function refreshPlans(Request $request)
    {
        $user = Auth::user();
        
        // Check if user is super_admin
        if (!$user->hasRole('super_admin')) {
            abort(403, __('payments.not_authorized'));
        }

        try {
            DB::beginTransaction();
            
            // Get all plan data from the seeder logic
            $planDefinitions = $this->getPlanDefinitions();
            $updatedCount = 0;
            $createdCount = 0;
            
            foreach ($planDefinitions as $planData) {
                // Check if plan already exists by plan_code
                $existingPlan = BillingPlan::where('plan_code', $planData['code'])->first();
                
                // Get factory data
                $factoryMethod = $planData['method'];
                $factoryData = BillingPlan::factory()->{$factoryMethod}()->make()->toArray();
                
                if ($existingPlan) {
                    // Update existing plan (preserve plan_id)
                    $existingPlan->update($factoryData);
                    $updatedCount++;
                } else {
                    // Create new plan (will get new plan_id)
                    BillingPlan::factory()->{$factoryMethod}()->create([
                        'plan_code' => $planData['code'],
                        'plan_name' => $planData['name'],
                    ]);
                    $createdCount++;
                }
            }
            
            // Optionally, deactivate plans that no longer exist in the seeder
            $existingCodes = array_column($planDefinitions, 'code');
            BillingPlan::whereNotIn('plan_code', $existingCodes)
                ->update(['is_active' => false]);
            
            $deactivatedCount = BillingPlan::whereNotIn('plan_code', $existingCodes)
                ->where('is_active', false)
                ->count();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => "Billing plans refreshed: {$updatedCount} updated, {$createdCount} created, {$deactivatedCount} deactivated",
                'stats' => [
                    'updated' => $updatedCount,
                    'created' => $createdCount,
                    'deactivated' => $deactivatedCount
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error refreshing plans: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get plan definitions from seeder
     */
    private function getPlanDefinitions()
    {
        return [
            [
                'method' => 'free',
                'code' => 'free',
                'name' => 'Free Trial',
            ],
            [
                'method' => 'starter',
                'code' => 'starter',
                'name' => 'Starter Plan',
            ],
            [
                'method' => 'business',
                'code' => 'business',
                'name' => 'Business Plan',
            ],
            [
                'method' => 'enterprise',
                'code' => 'enterprise',
                'name' => 'Enterprise Plan',
            ],
            [
                'method' => 'lifetime',
                'code' => 'onetime_lifetime',
                'name' => 'Lifetime License',
            ],
        ];
    }


    /**
     * Update tenant settings (all settings except billing)
     */
    public function updateSettings(Request $request, string $id)
    {
        $user = Auth::user();
        if (!$user->hasRole('super_admin')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ], 403);
        }

        $tenant = Tenant::findOrFail($id);

        // Get all existing settings except billing
        $existingSettings = TenantSetting::where('tenant_id', $tenant->id)
            ->where('category', '!=', 'billing')
            ->get();

        // Build validation rules dynamically based on data_type
        $rules = [];
        $settingValues = [];
        
        foreach ($existingSettings as $setting) {
            $key = $setting->setting_key;
            $dataType = $setting->data_type;
            
            // Skip billing settings
            if ($setting->category === 'billing') {
                continue;
            }
            
            // Build validation rules based on data type
            switch ($dataType) {
                case 'boolean':
                    $rules[$key] = 'sometimes|boolean';
                    // Store current value for comparison
                    $settingValues[$key] = $setting->setting_value;
                    break;
                case 'integer':
                    $rules[$key] = 'sometimes|integer|min:0';
                    $settingValues[$key] = (int)$setting->setting_value;
                    break;
                case 'decimal':
                    $rules[$key] = 'sometimes|numeric|min:0';
                    $settingValues[$key] = (float)$setting->setting_value;
                    break;
                case 'string':
                    $rules[$key] = 'sometimes|string|max:255';
                    $settingValues[$key] = $setting->setting_value;
                    break;
                case 'date':
                    $rules[$key] = 'sometimes|date';
                    $settingValues[$key] = $setting->setting_value;
                    break;
                case 'datetime':
                    $rules[$key] = 'sometimes|date';
                    $settingValues[$key] = $setting->setting_value;
                    break;
                case 'json':
                    $rules[$key] = 'sometimes|json';
                    $settingValues[$key] = $setting->setting_value;
                    break;
                default:
                    $rules[$key] = 'sometimes|string';
                    $settingValues[$key] = $setting->setting_value;
                    break;
            }
        }

        // Validate the request
        $validated = $request->validate($rules);

        // If no validated data, return error
        if (empty($validated)) {
            return response()->json([
                'success' => false,
                'message' => 'No settings to update.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $updatedCount = 0;
            $updatedSettings = [];

            foreach ($validated as $key => $value) {
                // Find the existing setting
                $setting = TenantSetting::where('tenant_id', $tenant->id)
                    ->where('setting_key', $key)
                    ->first();

                if (!$setting) {
                    continue;
                }

                $dataType = $setting->data_type;
                
                // Convert value based on data type
                $convertedValue = $this->convertValueForDatabase($value, $dataType);

                // Skip if value hasn't changed
                if ($setting->setting_value == $convertedValue) {
                    continue;
                }

                // Update the setting
                $setting->update([
                    'setting_value' => $convertedValue,
                    'updated_by' => $user->id,
                ]);

                $updatedCount++;
                $updatedSettings[] = [
                    'key' => $key,
                    'old_value' => $setting->getOriginal('setting_value'),
                    'new_value' => $convertedValue,
                    'data_type' => $dataType,
                    'category' => $setting->category,
                ];
            }

            // Clear cache
            tenant_clear_settings_cache($tenant->id);

            DB::commit();

            $message = $updatedCount > 0 
                ? "Settings updated successfully. {$updatedCount} settings updated."
                : "No changes were made to any settings.";

            session()->flash('toast', [
                'type' => $updatedCount > 0 ? 'success' : 'info',
                'message' => $message,
            ]);

            return response()->json([
                'success' => true,
                'message' => $message,
                'reload' => true,
                'updated_count' => $updatedCount,
                'updated_settings' => $updatedSettings,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            session()->flash('toast', [
                'type' => 'error',
                'message' => 'Error updating settings: ' . $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error updating settings: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Convert value to appropriate database format based on data type
     */
    private function convertValueForDatabase($value, string $dataType): string
    {
        switch ($dataType) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
            case 'integer':
                return (string)(int)$value;
            case 'decimal':
                return number_format((float)$value, 2, '.', '');
            case 'date':
            case 'datetime':
                if (empty($value)) {
                    return '';
                }
                return $value;
            case 'json':
                return is_array($value) ? json_encode($value) : $value;
            default:
                return (string)$value;
        }
    }
    

    /**
     * Update billing settings for a tenant
     */
    public function updateBilling(Request $request, string $id)
    {
        $user = Auth::user();
        if (!$user->hasRole('super_admin')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ], 403);
        }

        $tenant = Tenant::findOrFail($id);

        $validated = $request->validate([
            'plan_name' => 'nullable|string|max:255',
            'subscription_status' => 'required|in:active,inactive,trial,expired,suspended',
            'plan_currency' => 'nullable|string|size:3',
            'billing_cycle' => 'required|in:30_days,90_days,365_days,one-time',
            'billing_cycle_days' => 'required|integer|min:0',
            'trial_days' => 'required|integer|min:0',
            'is_lifetime' => 'sometimes|boolean',
            'trial_ends_at' => 'nullable|date',
            'next_billing_date' => 'nullable|date',
            'lifetime_purchase_date' => 'nullable|date',
            'plan_monthly_price' => 'nullable|numeric|min:0',
            'plan_annual_price' => 'nullable|numeric|min:0',
            'onetime_fee' => 'nullable|numeric|min:0',
            'setup_fee' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $updatedCount = 0;
            $billingKeys = [
                'plan_name', 'subscription_status', 'plan_currency', 'billing_cycle',
                'billing_cycle_days', 'trial_days', 'is_lifetime', 'trial_ends_at',
                'next_billing_date', 'lifetime_purchase_date', 'plan_monthly_price',
                'plan_annual_price', 'onetime_fee', 'setup_fee'
            ];

            foreach ($billingKeys as $key) {
                if ($request->has($key)) {
                    $value = $request->input($key);
                    
                    // Handle boolean specially
                    if ($key === 'is_lifetime') {
                        $value = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
                        $dataType = 'boolean';
                    } elseif (in_array($key, ['plan_monthly_price', 'plan_annual_price', 'onetime_fee', 'setup_fee'])) {
                        $value = number_format((float)$value, 2, '.', '');
                        $dataType = 'decimal';
                    } elseif (in_array($key, ['billing_cycle_days', 'trial_days'])) {
                        $value = (string)(int)$value;
                        $dataType = 'integer';
                    } elseif (in_array($key, ['trial_ends_at', 'next_billing_date', 'lifetime_purchase_date'])) {
                        $value = $value ? date('Y-m-d H:i:s', strtotime($value)) : '';
                        $dataType = 'datetime';
                    } else {
                        $dataType = 'string';
                    }

                    TenantSetting::updateOrCreate(
                        [
                            'tenant_id' => $tenant->id,
                            'setting_key' => $key,
                        ],
                        [
                            'setting_value' => $value,
                            'data_type' => $dataType,
                            'category' => 'billing',
                            'updated_by' => $user->id,
                        ]
                    );
                    $updatedCount++;
                }
            }

            // If subscription_status is set to inactive or expired, also update tenant status
            $status = $request->input('subscription_status');
            if (in_array($status, ['inactive', 'expired', 'suspended'])) {
                $tenant->status = $status === 'suspended' ? 'suspended' : 'inactive';
                $tenant->save();
            } elseif ($status === 'active') {
                $tenant->status = 'active';
                $tenant->save();
            }

            // Clear cache
            tenant_clear_settings_cache($tenant->id);

            DB::commit();

            session()->flash('toast', [
                'type' => 'success',
                'message' => "Billing settings updated successfully. {$updatedCount} settings updated.",
            ]);

            return response()->json([
                'success' => true,
                'message' => "Billing settings updated successfully. {$updatedCount} settings updated.",
                'reload' => true,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            session()->flash('toast', [
                'type' => 'error',
                'message' => 'Error updating billing settings: ' . $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error updating billing settings: ' . $e->getMessage(),
            ], 500);
        }
    }



}

