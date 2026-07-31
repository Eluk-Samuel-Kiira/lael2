<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Facades\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\Setting;
use App\Models\Category;
use App\Models\ProductCategory;
use App\Models\UnitOfMeasure;
use App\Models\ProductVariant;
use App\Models\Product;
use App\Models\Currency;
use App\Models\Location;
use App\Models\Customer;
use App\Models\{ Tax, Employee, PaymentMethod };
use App\Models\{ Promotion, Supplier, GeneralLedger, ChartOfAccount, ExpenseCategory };
use Illuminate\Support\Facades\Auth;
use App\Services\Payment\PaymentTransactionService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('payment-transaction', function ($app) {
            return new PaymentTransactionService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('*', function ($view) {
            // Initialize with empty collections by default
            $data = [
                'users' => collect(),
                'roles' => collect(),
                'permissions' => collect(),
                'departments' => collect(),
                'categories' => collect(),
                'sub_categories' => collect(),
                'uoms' => collect(),
                'variants' => collect(),
                'currencies' => collect(),
                'locations' => collect(),
                'customers' => collect(),
                'taxes' => collect(),
                'globalPaymentMethods' => collect(),
                'promotions' => collect(),
                'suppliers' => collect(),
                'chartOfAccounts' => collect(),
                'expenseCategories' => collect(),
                'active_employees' => collect(),
                'active_payment_methods' => collect(),
            ];

            // Only query if user is authenticated
            if (Auth::check()) {
                $tenantId = Auth::user()->tenant_id;
                
                $data = [
                    'users' => User::where('tenant_id', $tenantId)
                        ->where('status', 'active')
                        ->whereDoesntHave('roles', function ($query) {
                            $query->where('name', 'super_admin');
                        })
                        ->get(),
                    'roles' => Role::where('tenant_id', $tenantId)->whereNot('name', 'super_admin')->with('permissions')->latest()->get(),
                    'permissions' => Permission::regular()->get(),
                    'departments' => Department::where('tenant_id', $tenantId)->where('isActive', 1)->get(),
                    'categories' => Category::where('tenant_id', $tenantId)->where('is_active', 1)->get(),
                    'sub_categories' => ProductCategory::where('tenant_id', $tenantId)->where('is_active', 1)->get(),
                    'uoms' => UnitOfMeasure::where('tenant_id', $tenantId)->where('isActive', 1)->get(),
                    'variants' => ProductVariant::where('tenant_id', $tenantId)->where('is_active', 1)->get(),
                    'products_allocate' => Product::with('variants')->where('tenant_id', $tenantId)->where('is_active', 1)->get(),
                    'currencies' => Currency::where('tenant_id', $tenantId)->where('is_active', 1)->get(),
                    'locations' => Location::where('tenant_id', $tenantId)->where('is_active', 1)->get(),
                    'customers' => Customer::where('tenant_id', $tenantId)->where('is_active', 1)->get(),
                    'taxes' => Tax::where('tenant_id', $tenantId)->where('is_active', 1)->get(),
                    'globalPaymentMethods' => PaymentMethod::where('tenant_id', $tenantId)
                                    ->where('is_active', true)
                                    ->orderBy('type')
                                    ->orderBy('name')
                                    ->get()
                                    ->groupBy('type'),
                    'promotions' => Promotion::where('tenant_id', $tenantId)->where('is_active', 1)->get(),
                    'suppliers' => Supplier::where('tenant_id', $tenantId)->where('is_active', 1)->get(),
                    'expenseCategories' => ExpenseCategory::where('tenant_id', $tenantId)->where('is_active', 1)->orderBy('name')->get(),
                    'active_employees' => Employee::where('tenant_id', $tenantId)->where('is_active', 1)->get(),
                    'active_payment_methods' => PaymentMethod::where('tenant_id', $tenantId)->where('is_active', 1)->get(),
                    'chartOfAccounts' => ChartOfAccount::where('tenant_id', $tenantId)
                                        ->where('is_active', true)
                                        ->orderBy('account_code')
                                        ->get(),
                ];
            }

            $view->with($data);
        });

        // Configure mail settings - only once per request
        $this->configureMailSettings();

        // ── ONLY run sync observers on LOCAL POS machines ─────────────────────
        if (!filter_var(env('IS_LOCAL_POS', false), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        foreach ($this->getSyncModels() as $modelClass) {
            if (!class_exists($modelClass)) {
                \Log::warning("SyncObserver skipped: Model class not found: {$modelClass}");
                continue;
            }
            $modelClass::observe(\App\Observers\SyncObserver::class);
        }
    }

    /**
     * Configure mail settings based on tenant or fallback to environment variables
     */
    private function configureMailSettings(): void
    {
        try {
            // Check if user is authenticated
            if (Auth::check()) {
                $tenantId = Auth::user()->tenant_id;
                
                // Get settings for the specific tenant
                $app_mails = Setting::where('tenant_id', $tenantId)->first();
                
                // Check if we have valid mail settings from tenant
                if ($app_mails && $this->hasValidMailSettings($app_mails)) {
                    // Use tenant mail settings
                    $this->setMailConfig(
                        $app_mails->mail_mailer ?? 'smtp',
                        $app_mails->mail_host,
                        $app_mails->mail_port,
                        $app_mails->mail_username,
                        $app_mails->mail_password,
                        $app_mails->mail_encryption,
                        $app_mails->mail_address,
                        $app_mails->mail_name
                    );
                    
                    // Only log once per request
                    if (!session()->has('mail_configured')) {
                        Log::info('Using tenant mail configuration', [
                            'tenant_id' => $tenantId,
                            'host' => $app_mails->mail_host,
                            'from' => $app_mails->mail_address
                        ]);
                        session()->put('mail_configured', true);
                    }
                    
                    return;
                }
            }

            // Fallback to environment variables - only set if not already configured
            if (!session()->has('mail_configured_fallback')) {
                $this->setMailConfig(
                    env('MAIL_MAILER', 'smtp'),
                    env('STARDENA_POS_MAIL_HOST', env('MAIL_HOST', 'smtp.mailtrap.io')),
                    env('STARDENA_POS_MAIL_PORT', env('MAIL_PORT', 2525)),
                    env('STARDENA_POS_MAIL_USERNAME', env('MAIL_USERNAME')),
                    env('STARDENA_POS_MAIL_PASSWORD', env('MAIL_PASSWORD')),
                    env('STARDENA_POS_MAIL_ENCRYPTION', env('MAIL_ENCRYPTION', 'tls')),
                    env('STARDENA_POS_MAIL_FROM_ADDRESS', env('MAIL_FROM_ADDRESS', 'pos@stardena.org')),
                    env('STARDENA_POS_MAIL_FROM_NAME', env('MAIL_FROM_NAME', 'STARPOSS Website'))
                );
                
                // Log::info('Using fallback mail configuration from environment');
                session()->put('mail_configured_fallback', true);
            }

        } catch (\Exception $e) {
            Log::error('Failed to configure mail settings: ' . $e->getMessage());
            
            // Ultimate fallback - only if not already set
            if (!session()->has('mail_configured_fallback')) {
                $this->setMailConfig(
                    env('MAIL_MAILER', 'smtp'),
                    env('MAIL_HOST', 'smtp.mailtrap.io'),
                    env('MAIL_PORT', 2525),
                    env('MAIL_USERNAME'),
                    env('MAIL_PASSWORD'),
                    env('MAIL_ENCRYPTION', 'tls'),
                    env('MAIL_FROM_ADDRESS', 'hello@example.com'),
                    env('MAIL_FROM_NAME', 'Laravel')
                );
                session()->put('mail_configured_fallback', true);
            }
        }
    }

    /**
     * Check if mail settings are valid
     */
    private function hasValidMailSettings($settings): bool
    {
        return $settings && 
               !empty($settings->mail_host) && 
               !empty($settings->mail_username) && 
               !empty($settings->mail_password) &&
               !empty($settings->mail_address);
    }

    /**
     * Set mail configuration
     */
    private function setMailConfig(
        string $transport,
        string $host,
        int|string $port,
        string $username,
        string $password,
        string $encryption,
        string $fromAddress,
        string $fromName
    ): void {
        // Set mailer configuration
        Config::set('mail.mailers.smtp', [
            'transport' => $transport,
            'host' => $host,
            'port' => (int) $port,
            'encryption' => $encryption,
            'username' => $username,
            'password' => $password,
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN'),
        ]);

        // Set default mailer
        Config::set('mail.default', $transport);

        // Set from address
        Config::set('mail.from', [
            'address' => $fromAddress,
            'name' => $fromName,
        ]);
    }

    private function getSyncModels(): array
    {
        return [
            // Sales & Orders
            \App\Models\Order::class,
            \App\Models\OrderItem::class,
            \App\Models\OrderPayment::class,
            \App\Models\OrderTax::class,
            
            // Purchasing
            \App\Models\PurchaseOrder::class,
            \App\Models\PurchaseOrderItem::class,
            \App\Models\PurchaseReceipt::class,
            \App\Models\PurchaseReceiptItem::class,
            \App\Models\ReceivedProductVariant::class,
            
            // Inventory
            \App\Models\InventoryAdjustments::class,
            \App\Models\InventoryItems::class,
            \App\Models\InventoryTransactions::class,
            \App\Models\SingleShopInventoryLog::class,
            
            // Accounting
            \App\Models\AccountingPeriod::class,
            \App\Models\AccountBalance::class,
            \App\Models\ChartOfAccount::class,
            \App\Models\GeneralLedger::class,
            \App\Models\JournalEntry::class,
            \App\Models\JournalEntryLine::class,
            
            // Expenses
            \App\Models\Expense::class,
            \App\Models\ExpenseCategory::class,
            
            // HR / Employees
            \App\Models\Employee::class,
            \App\Models\EmployeeAdvance::class,
            \App\Models\EmployeeDocument::class,
            \App\Models\EmployeePayment::class,
            \App\Models\Leave::class,
            
            // Customers & Suppliers
            \App\Models\Customer::class,
            \App\Models\CustomerGroup::class,
            \App\Models\Supplier::class,
            \App\Models\SupplierTaxLiability::class,
            
            // Products
            \App\Models\Product::class,
            \App\Models\ProductCategory::class,
            \App\Models\ProductVariant::class,
            \App\Models\VariantTax::class,
            
            // Promotions
            \App\Models\Promotion::class,
            \App\Models\PromotionProduct::class,
            
            // Financial
            \App\Models\Currency::class,
            \App\Models\PaymentMethod::class,
            \App\Models\PaymentTransactionLog::class,
            \App\Models\Tax::class,
            \App\Models\TaxLiability::class,
            
            // Tenant related
            \App\Models\Tenant::class,
            \App\Models\TenantConfiguration::class,
            \App\Models\TenantSetting::class,
            \App\Models\TenantUsageTracking::class,
            
            // Users & Roles
            \App\Models\User::class,
            
            // Billing
            \App\Models\BillingPlan::class,
            
            // Settings
            \App\Models\Setting::class,
            
            // Departments & Locations
            \App\Models\Department::class,
            \App\Models\Location::class,
        ];
    }
}