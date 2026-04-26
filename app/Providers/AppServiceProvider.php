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
use App\Models\Currency;
use App\Models\Location;
use App\Models\Customer;
use App\Models\{ Tax, Employee, PaymentMethod };
use App\Models\{ Promotion, Supplier, GeneralLedger, ChartOfAccount, ExpenseCategory };
use Illuminate\Support\Facades\Auth;
use App\Services\Payment\PaymentTransactionService;


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
                                        // ->where('account_type', 'like', '%expense%') // Filter for expense accounts
                                        ->orderBy('account_code')
                                        ->get(),
                    ];
            }

            $view->with($data);
        });


        // After the view composer, add the mail configuration with tenant-specific settings
        if (Auth::check()) {
            $tenantId = Auth::user()->tenant_id;
            
            // Get settings for the specific tenant
            $app_mails = Setting::where('tenant_id', $tenantId)->first();
            
            if ($app_mails) {
                $data =  [
                    'transport' => $app_mails->mail_mailer,
                    'host' => $app_mails->mail_host,
                    'port' => $app_mails->mail_port,
                    'username' => $app_mails->mail_username,
                    'password' => $app_mails->mail_password,
                    'encryption' => $app_mails->mail_encryption,
                    'timeout' => null, 
                    'local_domain' => env('MAIL_EHLO_DOMAIN'),
                    'from' => [
                        'address' => $app_mails->mail_address,
                        'name' => $app_mails->mail_name,
                    ],
                ];
                \Config::set('mail.mailers.smtp', $data);
                \Config::set('mail.default', $data['transport']);
                \Config::set('mail.from', $data['from']); 
                
                \Config::set('app.name', $app_mails->app_name); 
            }
        }


           // ── ONLY run sync observers on LOCAL POS machines ─────────────────────
        if (!filter_var(env('IS_LOCAL_POS', false), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        foreach ($this->getSyncModels() as $modelClass) {
            // Skip if model class doesn't exist (prevents errors during deploy)
            if (!class_exists($modelClass)) {
                \Log::warning("SyncObserver skipped: Model class not found: {$modelClass}");
                continue;
            }
            
            // Register the observer — period.
            $modelClass::observe(\App\Observers\SyncObserver::class);
        }
        

    }

    private function getSyncModels(): array
    {
        // ============================================================
        // TRANSACTIONAL TABLES (Need Sync - Push to Remote)
        // These tables contain business transactions that must sync
        // ============================================================
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
            
            // Products (transactional variants only — master data is pull-only)
            \App\Models\Product::class,          // Only if local edits allowed
            \App\Models\ProductCategory::class,  // Only if local edits allowed
            \App\Models\ProductVariant::class,   // Only if local edits allowed
            \App\Models\VariantTax::class,
            
            // Promotions (local overrides only)
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
            
            // Departments & Locations (if locally editable)
            \App\Models\Department::class,
            \App\Models\Location::class,
        ];
    }



}
