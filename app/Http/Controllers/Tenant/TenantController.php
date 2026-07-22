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




    
}

