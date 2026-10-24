<?php

use App\Http\Controllers\{ ArtisanCommandController, ProfileController, UserController, RoleController, HomeController};
use App\Http\Controllers\Home\{ DashboardController, LocationController, SettingsController,  UnitOfMeasureController, CurrencyController};
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Tenant\{ EmployeeDocumentController, DepartmentController, EmployeeController, 
    EmployeePaymentController, TenantController, EmployeeAdvanceController, LeaveController };
use App\Http\Controllers\Catalog\ { SerialController, RecipeController, CategoryController, InventoryItemController, ProductVariantController, BatchController,
    InventoryAdjustmentsController, ProductController, ProductCategoryController, ProductImportController};
use App\Http\Controllers\Orders\{ OrderController, POSController};
use App\Http\Controllers\Setting\{ TaxController,PromotionController, PaymentMethodController, TaxLiabilityController };
use App\Http\Controllers\Procurement\{ ExpenseTemplateController, SupplierController, PurchaseOrderController, ExpenseCategoryController, ExpenseController };
use App\Http\Controllers\Accounts\{ AccountingController };
use App\Http\Controllers\Reports\{ ExpenseReportsController, OrderReportsController, ProductsController, InventoryReportsController,
    PurchasingReportsController, InventoryStrategyReportController, ProductionOrderReportController, InvoiceReportsController };

    // Route::get('/test-currency', function() {
    //     return debug_currency_conversion(4, 'AUD');
    // });



    // Route::get('/test-aud-detailed', function() {
    //     $aud = \App\Models\Currency::where('code', 'AUD')->first();
    //     $usd = \App\Models\Currency::where('code', 'USD')->first();
        
    //     $amount = 4;
        
    //     // Test 1: Direct calculation
    //     $directStored = round(($amount / $aud->exchange_rate) * 100);
    //     $directBack = round(($directStored / 100) * $aud->exchange_rate, 2);
        
    //     // Test 2: Your helper
    //     $helperStored = to_base_currency($amount, $aud);
    //     $helperBack = from_base_currency($helperStored, $aud);
        
    //     // Test 3: Different calculation methods
    //     $method1 = round(($amount * 100) / $aud->exchange_rate); // (400 cents) / 1.52
    //     $method2 = round(($amount / $aud->exchange_rate) * 100); // (4 / 1.52) * 100
    //     $method3 = (int) round($amount * 100 / $aud->exchange_rate); // Same as method1
        
    //     return [
    //         'currency_info' => [
    //             'AUD' => [
    //                 'code' => $aud->code,
    //                 'rate' => $aud->exchange_rate,
    //                 'multiplier' => $aud->getMultiplier(),
    //                 'decimal_places' => $aud->decimal_places,
    //             ],
    //             'USD' => [
    //                 'code' => $usd->code,
    //                 'rate' => $usd->exchange_rate,
    //                 'multiplier' => $usd->getMultiplier(),
    //                 'decimal_places' => $usd->decimal_places,
    //             ]
    //         ],
    //         'test_amount' => $amount . ' AUD',
    //         'direct_calculation' => [
    //             'stored' => $directStored . ' cents',
    //             'back' => $directBack . ' AUD',
    //             'difference' => ($amount - $directBack) . ' AUD',
    //         ],
    //         'helper_calculation' => [
    //             'stored' => $helperStored . ' cents',
    //             'back' => $helperBack . ' AUD',
    //             'difference' => ($amount - $helperBack) . ' AUD',
    //         ],
    //         'calculation_methods' => [
    //             'method1: (amount * 100) / rate = ' . round(($amount * 100) / $aud->exchange_rate, 2) . ' cents',
    //             'method2: (amount / rate) * 100 = ' . round(($amount / $aud->exchange_rate) * 100, 2) . ' cents',
    //             'method3: round(amount * 100 / rate) = ' . round($amount * 100 / $aud->exchange_rate) . ' cents',
    //         ],
    //         'manual_step_by_step' => [
    //             'step1: 4 AUD ÷ 1.52 = ' . (4/1.52) . ' USD',
    //             'step2: × 100 = ' . (4/1.52 * 100) . ' cents (before rounding)',
    //             'step3: rounded = ' . round(4/1.52 * 100) . ' cents',
    //             'step4: ÷ 100 = ' . (round(4/1.52 * 100) / 100) . ' USD',
    //             'step5: × 1.52 = ' . (round(4/1.52 * 100) / 100 * 1.52) . ' AUD',
    //             'step6: rounded to 2 decimals = ' . round(round(4/1.52 * 100) / 100 * 1.52, 2) . ' AUD',
    //         ],
    //         'expected_result' => '~4.00 AUD (may be 3.99 or 4.01 due to rounding)',
    //     ];
    // });

    // Protect with auth middleware — never expose this publicly!
    Route::middleware(['auth'])->prefix('admin')->group(function () {
        Route::get('/artisan',       [ArtisanCommandController::class, 'index'])->name('artisan.index');
        Route::post('/artisan/run',  [ArtisanCommandController::class, 'run'])->name('artisan.run');
    });


    Route::get('/', function () {
        return view('welcome');
    });
    Route::post('/send-inquiry', [HomeController::class, 'send'])
    ->name('inquiry.send');

    // Core marketing pages (redirect to home)
    Route::redirect('/features', '/', 301);
    Route::redirect('/pricing', '/', 301);
    Route::redirect('/demo', '/', 301);
    Route::redirect('/contact', '/', 301);
    Route::redirect('/about', '/', 301);

    // Industry landing pages (redirect to home)
    Route::redirect('/pos-for-retail', '/', 301);
    Route::redirect('/pos-for-restaurants', '/', 301);
    Route::redirect('/pos-for-hotels', '/', 301);
    Route::redirect('/pos-for-pharmacies', '/', 301);
    Route::redirect('/pos-for-supermarkets', '/', 301);

    // Location-based SEO pages (redirect to home)
    Route::redirect('/pos-system-uganda', '/', 301);
    Route::redirect('/pos-system-kenya', '/', 301);
    Route::redirect('/pos-system-tanzania', '/', 301);
    Route::redirect('/pos-system-rwanda', '/', 301);
    Route::redirect('/pos-system-east-africa', '/', 301);
    Route::redirect('/pos-system-africa', '/', 301);

    // ============================================
    // CONTENT PAGES (THEIR OWN CONTENT)
    // These 5 pages have real content
    // ============================================
    Route::get('/help', [HomeController::class, 'help'])->name('pages.help');
    Route::get('/docs', [HomeController::class, 'docs'])->name('pages.docs');
    Route::get('/blog', [HomeController::class, 'blog'])->name('pages.blog');
    Route::get('/privacy-policy', [HomeController::class, 'privacyPolicy'])->name('pages.privacy');
    Route::get('/terms-of-service', [HomeController::class, 'termsOfService'])->name('pages.terms');



    // Error Pages
    Route::get('/error-404', function () {
        return response()->view('layouts.error-404', [], 404); 
    });

    Route::get('/error-500', function () {
        return response()->view('layouts.error-500', [], 500); 
    });

    Route::get('/dashboard', [DashboardController::class, 'index'] )->middleware(['auth', 'verified'])->name('dashboard');
    Route::get('/overview', [DashboardController::class, 'overview'] )->middleware(['auth', 'verified'])->name('overview');

    Route::middleware('auth')->group(function () {

        // Profile
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
        Route::post('/profile/upload-image', [ProfileController::class, 'uploadImage'])->name('profile.upload_image');


        // Settings
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::get('/change-locale/{locale}', [SettingsController::class, 'switchLocale'])->name('switch.locale');
        Route::put('/settings-update', [SettingsController::class, 'updateSettings'])->name('setting.update');
        Route::post('/logo-upload', [SettingsController::class, 'uploadLogo'])->name('logo.upload');
        Route::post('/favicon-upload', [SettingsController::class, 'uploadFavicon'])->name('favicon.upload');

        // Units of measure
        Route::resource('uom', UnitOfMeasureController::class);
        Route::post('/uom-status/{id}', [UnitOfMeasureController::class, 'changeUOMStatus'])->name('uom.status');

        // Currency
        Route::resource('currency', CurrencyController::class);
        Route::post('/currency-status/{id}', [CurrencyController::class, 'changeCurrencyStatus'])->name('currency.status');

        // Department
        Route::resource('department', DepartmentController::class);
        Route::post('/department-status/{id}', [DepartmentController::class, 'changeDepartmentStatus'])->name('currency.status');


        // Users/Employees
        Route::resource('employee', UserController::class);
        Route::post('/employee-status/{id}', [UserController::class, 'changeEmployeeStatus'])->name('employee.status');
        Route::put('/employees/{id}/departments', [UserController::class, 'updateDepartments'])
            ->name('employees.updateDepartments');

            
        Route::post('/employee/documents/upload', [EmployeeDocumentController::class, 'upload'])->name('employee.documents.upload');
        Route::post('/employee/documents/delete', [EmployeeDocumentController::class, 'delete'])->name('employee.documents.delete');
        Route::get('/employee/{employeeId}/documents', [EmployeeDocumentController::class, 'index'])->name('employee.documents.index');

        // New: stream instead of relying on the public symlink
        Route::get('/employee/{employeeId}/documents/{index}/view', [EmployeeDocumentController::class, 'view'])->name('employee.documents.view');
        Route::get('/employee/{employeeId}/documents/{index}/download', [EmployeeDocumentController::class, 'download'])->name('employee.documents.download');



        Route::resource('employee-advance', EmployeeAdvanceController::class);
        // Get active advances for payment form
        Route::get('/employee-advances/{employeeId}/active', [EmployeeAdvanceController::class, 'getActiveAdvances'])
            ->name('employee.advances.active');
        
        // Approve advance
        Route::post('/employee-advances/{id}/approve', [EmployeeAdvanceController::class, 'approve'])
            ->name('employee.advances.approve');
        
        // Reject advance
        Route::post('/employee-advances/{id}/reject', [EmployeeAdvanceController::class, 'reject'])
            ->name('employee.advances.reject');
        
        // Cancel advance
        Route::post('/employee-advances/{id}/cancel', [EmployeeAdvanceController::class, 'cancel'])
            ->name('employee.advances.cancel');
        
        // Get advance statistics
        Route::get('/employee-advances/statistics', [EmployeeAdvanceController::class, 'getStatistics'])
            ->name('employee.advances.statistics');
        
        // Export advances
        Route::get('/employee-advances/export', [EmployeeAdvanceController::class, 'export'])
            ->name('employee.advances.export');


        Route::resource('leave', LeaveController::class);
        Route::get('/leave/calendar/events', [LeaveController::class, 'getCalendarEvents'])->name('leave.calendar.events');
        Route::post('/leave/{id}/approve', [LeaveController::class, 'approve'])->name('leave.approve');
        Route::post('/leave/{id}/reject', [LeaveController::class, 'reject'])->name('leave.reject');
        Route::post('/leave/{id}/cancel', [LeaveController::class, 'cancel'])->name('leave.cancel');


        Route::resource('user', EmployeeController::class);
        Route::post('/user-status/{id}', [EmployeeController::class, 'changeUserStatus']);
        Route::post('/sync-users-to-employees', [EmployeeController::class, 'syncUsersToEmployees'])
            ->name('sync.users.to.employees');

        Route::resource('payment', EmployeePaymentController::class);
        Route::post('/payment-status/{id}', [EmployeePaymentController::class, 'updatePaymentStatus']);

        Route::resource('paymentmethod', PaymentMethodController::class);
        Route::post('/payment-methods-status/{id}', [PaymentMethodController::class, 'changePaymentMethodStatus'])->name('payment-methods.status');
        Route::prefix('payment-methods')->middleware(['auth'])->group(function () {

            Route::post('/transfer/preview', [PaymentMethodController::class, 'transferPreview'])
                ->name('paymentmethod.transfer.preview');

            Route::post('/transfer', [PaymentMethodController::class, 'transfer'])
                ->name('paymentmethod.transfer');
        });

        Route::post('payments/calculate-tax-preview', [EmployeePaymentController::class, 'calculateTaxPreview'])
            ->name('payment.calculate-tax-preview');

        // Roles n Permissions
        Route::get('/role-index', [RoleController::class, 'index'])->name('role.index');
        Route::get('/permission-index', [RoleController::class, 'permissionIndex'])->name('permission.index');
        Route::post('/role-new', [RoleController::class, 'storeRole'])->name('role.store');
        Route::put('/role-update/{role}', [RoleController::class, 'updateRole'])->name('role.update');
        Route::delete('/role-destroy/{id}', [RoleController::class, 'destroy'])->name('role.destroy');
        Route::put('/update-permissions/{id}', [RoleController::class, 'updatePermission'])->name('permission.update');
        Route::put('/revoke-permissions/{id}', [RoleController::class, 'revokePermission'])->name('permission.revoke');

        Route::get('category/data', [CategoryController::class, 'data'])->name('category.data');
        Route::resource('category', CategoryController::class)
            ->parameters(['category' => 'category']);          // ← key fix
        Route::post('/category-status/{id}', [CategoryController::class, 'changeCategoryStatus'])
            ->name('category.status');

        Route::get('product-category/data', [ProductCategoryController::class, 'data'])->name('product-category.data');
        // Resource routes using the parameter name 'productCategory' to match Laravel's default
        Route::resource('product-category', ProductCategoryController::class);
        Route::post('/product-category-status/{id}', [ProductCategoryController::class, 'changeStatus'])->name('product-category.status');
        // Products
        
        Route::resource('products', ProductController::class);
        Route::post('/product-status/{id}', [ProductController::class, 'changeProductStatus'])->name('product.status');
        Route::post('/product-tax-status/{id}', [ProductController::class, 'changeProductTaxStatus'])->name('product.taxable');
        Route::resource('variants', ProductVariantController::class);
        Route::post('/variant-status/{id}', [ProductVariantController::class, 'changeVariantStatus'])->name('variant.status');
        Route::post('/variant-tax-status/{id}', [ProductVariantController::class, 'changeProductVariantTaxStatus'])->name('product.taxable');
        Route::post('/product/upload-image', [ProductController::class, 'uploadProductImage'])->name('product.upload_image');
        Route::post('/variant/upload-image', [ProductVariantController::class, 'uploadVariantImage'])->name('variant.upload_image');
        Route::put('/products/{product}/update-assignment', [ProductController::class, 'updateProductAssignments'])
            ->name('assign.product');
        Route::put('/assignment/{product}', [ProductVariantController::class, 'updateVariantAssignments'])
            ->name('assign.variant');
        
        // In routes/web.php

        // Recipe routes
        Route::prefix('recipe')->name('recipe.')->group(function () {
            Route::get('/ingredients/{variantId}', [ProductVariantController::class, 'getRecipeIngredients'])
                ->name('ingredients.get');
            Route::post('/ingredients/store', [ProductVariantController::class, 'storeRecipeIngredients'])
                ->name('ingredients.store');
            Route::delete('/ingredient/{ingredientId}', [ProductVariantController::class, 'deleteRecipeIngredient'])
                ->name('ingredient.delete');
        });
            


        Route::get('/catalog/import/template', [ProductImportController::class, 'downloadTemplate'])->name('catalog.import.template');
        Route::post('/catalog/import',          [ProductImportController::class, 'store'])->name('catalog.import.store');
        Route::post('/products/bulk-allocate', [ProductController::class, 'bulkAllocate'])
            ->name('products.bulk-allocate');
        
        // Stores and Inventory
        Route::get('/items/data', [InventoryItemController::class, 'data'])->name('items.data');
        Route::get('/items/{id}/details', [InventoryItemController::class, 'details'])->name('items.details');

        Route::resource('items', InventoryItemController::class);
        Route::post('items/batch', [InventoryItemController::class, 'storeBatch'])
            ->name('items.batchStore');
        Route::get ('items/{id}/pricing', [InventoryItemController::class, 'getPricing'])
            ->name('items.pricing.get');
        
        Route::get('/recipes', [RecipeController::class, 'index'])->name('recipes.index');
        Route::get('/serials', [SerialController::class, 'index'])->name('serials.index');

        Route::put ('items/{id}/pricing', [InventoryItemController::class, 'updatePricing'])
            ->name('items.pricing.update');

        
        Route::get('stocks/data',    [InventoryAdjustmentsController::class, 'data'])->name('stocks.data');
        Route::get('stocks/{id}/details', [InventoryAdjustmentsController::class, 'details'])->name('stocks.details');
        Route::get('/get-departments', [InventoryItemController::class, 'getDepartmentsByLocation'])->name('get.departments');
        Route::resource('stocks', InventoryAdjustmentsController::class);
        Route::put('/transfer-stock/{id}', [InventoryAdjustmentsController::class, 'transferStock'])
            ->name('stocks.transfer');

        Route::get('/get-departments-by-location/{locationId}', [DepartmentController::class, 'getByLocation'])->name('get.departments.by.location');


        // Batch routes
        Route::prefix('batches')->name('batches.')->group(function () {
            Route::get('/', [BatchController::class, 'index'])->name('index');
            Route::get('/data', [BatchController::class, 'data'])->name('data');
            Route::post('/assign', [BatchController::class, 'assign'])->name('assign');
            Route::post('/unassign', [BatchController::class, 'unassign'])->name('unassign');
            Route::post('/details', [BatchController::class, 'details'])->name('details');
            Route::post('/split', [BatchController::class, 'split'])->name('split');
        });
        Route::get('departments/by-location/{locationId}', [BatchController::class, 'departmentsByLocation'])
            ->name('get.departments.by.location');


        Route::prefix('serials')->name('serials.')->group(function () {
            Route::get   ('/variant/{variantId}',  [ProductVariantController::class, 'getSerials'])->name('get');
            Route::post  ('/generate',             [ProductVariantController::class, 'generateSerials'])->name('generate');
            Route::post  ('/import',               [ProductVariantController::class, 'importSerials'])->name('import');
            Route::post  ('/assign-selected',      [ProductVariantController::class, 'assignSelectedSerials'])->name('assign-selected');
            Route::put   ('/{serialId}/status',    [ProductVariantController::class, 'updateSerialStatus'])->name('update-status');
            Route::put   ('/{serialId}/pricing',   [ProductVariantController::class, 'updateSerialPricing'])->name('update-pricing'); // ★ NEW
            Route::delete('/{serialId}',           [ProductVariantController::class, 'deleteSerial'])->name('delete');
        });

        // Location
        Route::resource('locations', LocationController::class);
        Route::post('/location-primary/{id}', [LocationController::class, 'updatePrimaryStatus']);
        Route::post('/location-status/{id}', [LocationController::class, 'updateLocationStatus']);


        
        // Orders and Purchases
        Route::get('/pos/search', [POSController::class, 'search'])->name('pos.search');
        Route::get('/pos-index', [POSController::class, 'index'])->name('pos.index');

        Route::post('/orders/process-payment', [POSController::class, 'processPayment'])
            ->name('orders.process-payment');
        Route::post('/orders/generate-invoice', [POSController::class, 'generateInvoice'])
            ->name('orders.generate-invoice');
        Route::post('/orders/checkout', [POSController::class, 'completePayment'])->name('orders.checkout');

        Route::post('/orders/complete-payment', [POSController::class, 'completePayment'])
            ->name('orders.complete-payment');
        Route::post('/orders/process-split-payment', [POSController::class, 'processSplitPayment'])
            ->name('orders.process-split-payment');
        
        Route::post('/pos-cancel/{id}', [POSController::class, 'cancel']);

        Route::get('orders/paused', [OrderController::class, 'getPausedOrders'])
            ->name('orders.paused');
            
        Route::get('/orders/data', [OrderController::class, 'data'])->name('orders.data');
        Route::get('/orders/{id}/details', [OrderController::class, 'details'])->name('orders.details');
        Route::post('/orders/{id}/refund', [OrderController::class, 'refund'])->name('orders.refund');

        Route::resource('orders', OrderController::class);
        Route::resource('orders', OrderController::class);



        // Taxes and Promotions
        Route::resource('tax', TaxController::class);
        Route::post('/tax-status/{id}', [TaxController::class, 'updateTaxStatus']);

        Route::prefix('tax')->name('tax.')->group(function () {
            Route::get('/liabilities', [TaxLiabilityController::class, 'index'])->name('liabilities.index');
            Route::post('/liabilities/remit', [TaxLiabilityController::class, 'remitTaxes'])->name('liabilities.remit');
            Route::get('/liabilities/summary', [TaxLiabilityController::class, 'summary'])->name('liabilities.summary');
        });


        Route::resource('promotion', PromotionController::class);
        Route::post('/promotion-status/{id}', [PromotionController::class, 'updatePromotionStatus']);


        // Suppliers & Purchasing
        Route::resource('suppliers', SupplierController::class);
        Route::post('/supplier-status/{id}', [SupplierController::class, 'updateSupplierStatus']);
        
        
        Route::get('/purchase-order', [PurchaseOrderController::class, 'index'])->name('purchase_order.index');
        Route::post('/purchase-order', [PurchaseOrderController::class, 'store'])->name('purchase-order.store');
        Route::get('purchase-orders/{id}/modals', [PurchaseOrderController::class, 'getModals'])->name('purchase_orders.modals');
        Route::get('purchase-orders/data', [PurchaseOrderController::class, 'data'])->name('purchase_orders.data');
        Route::get('purchase-orders/{id}/details', [PurchaseOrderController::class, 'details'])->name('purchase_orders.details');
        Route::post('/purchase-status/{id}', [PurchaseOrderController::class, 'submitApproval'])->name('po.submit-approval');
        Route::post('/purchase-approve/{id}', [PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
        Route::post('/purchase-send/{id}', [PurchaseOrderController::class, 'sendToSupplier'])->name('purchase-orders.send');
        Route::post('/purchase-orders/{purchaseOrder}/receive-items', [PurchaseOrderController::class, 'receiveItems'])->name('purchase-orders.receive-items');
        Route::post('/purchase-cancel/{id}', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');
        Route::delete('/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'destroy'])->name('purchase-orders.destroy');
        Route::post('/purchase-orders/{id}/send-document', [PurchaseOrderController::class, 'sendDocument'])->name('purchase_order.send_document');
        Route::post('/purchase-orders/calculate-tax-preview', [PurchaseOrderController::class, 'calculateTaxPreview'])
            ->name('purchase_order.calculate_tax_preview');
        Route::post('/orders/{id}/send', [OrderController::class, 'send'])->name('orders.send');

        // Expenses
        Route::resource('expense-category', ExpenseCategoryController::class);

        
        // ── Specific routes FIRST (before the resource) ──
        Route::get('/expense/data',              [ExpenseController::class, 'data'])->name('expense.data');
        Route::post('/expense-status/{id}',      [ExpenseController::class, 'updateExpenseStatus'])->name('updateExpenseStatus');
        Route::post('/expense/{id}/approve',     [ExpenseController::class, 'approve'])->name('expense.approve');
        Route::put('/expenses/{id}/receipt',     [ExpenseController::class, 'updateReceipt'])->name('expenses.update-receipt');
        Route::post('/expenses/{id}/approve',    [ExpenseController::class, 'approve'])->name('expenses.approve');

        // ── Resource LAST ──
        Route::resource('expense', ExpenseController::class);


        Route::prefix('expense-templates')->name('expense-templates.')->group(function () {
            Route::get('/',                    [ExpenseTemplateController::class, 'index'])->name('index');
            Route::get('/bulk-create',         [ExpenseTemplateController::class, 'create'])->name('create');
            Route::post('/bulk-store',         [ExpenseTemplateController::class, 'bulkStore'])->name('bulk-store');

            // ★ These two only
            Route::get('/available-templates', [ExpenseTemplateController::class, 'availableTemplates'])->name('available-templates');
            Route::post('/bulk-log',           [ExpenseTemplateController::class, 'bulkLog'])->name('bulk-log');

            // existing wildcard routes at the bottom
            Route::get('/{template}/log-form', [ExpenseTemplateController::class, 'logForm'])->name('log-form');
            Route::post('/{template}/log',     [ExpenseTemplateController::class, 'logOccurrence'])->name('log');
        });

        

        Route::resource('tenant', TenantController::class);
        Route::prefix('tenant')->name('tenant.')->group(function () {
            Route::post('/', [TenantController::class, 'store'])->name('store');
            Route::delete('/{tenant}', [TenantController::class, 'destroy'])->name('destroy');
            Route::post('/{tenant}/add-admin', [TenantController::class, 'addAdminUser'])->name('add-admin');
            Route::put('/{tenant}/settings', [TenantController::class, 'updateSettings'])->name('update-settings');
            Route::put('/{tenant}/billing', [TenantController::class, 'updateBilling'])->name('update-billing'); // ✅ Fixed: removed 'tenant.'
        });
        Route::post('/billing/refresh-plans', [TenantController::class, 'refreshPlans'])->name('billing.refresh-plans');


        // Basic Accounting Routes
        Route::prefix('accounting')->name('accounting.')->group(function () {
            Route::get('/payment-methods', [AccountingController::class, 'paymentMethods'])->name('payment-methods.index');
            Route::get('/payment-methods/data', [AccountingController::class, 'paymentMethodsData'])->name('payment-methods.data');
            Route::get('/account-balances', [AccountingController::class, 'accountBalances'])->name('account-balances');
            Route::get('/account-balances/data', [AccountingController::class, 'accountBalancesData'])->name('account-balances.data');
            Route::get('/transaction-ledger', [AccountingController::class, 'transactionLedger'])->name('transaction-ledger');
            Route::get('/transaction-ledger/data', [AccountingController::class, 'transactionLedgerData'])->name('transaction-ledger.data');
            Route::get('/transaction-ledger/details/{id}', [AccountingController::class, 'getTransactionDetails'])->name('transaction-details');
            Route::get('/income-statement', [AccountingController::class, 'incomeStatement'])->name('income-statement');
            Route::get('/income-statement/data', [AccountingController::class, 'incomeStatementData'])->name('income-statement.data');
            Route::get('/cash-flow', [AccountingController::class, 'cashFlow'])->name('cash-flow');
            Route::get('/cash-flow/data', [AccountingController::class, 'cashFlowData'])->name('cash-flow.data');
            Route::get('/transaction-analysis', [AccountingController::class, 'transactionAnalysis'])->name('transaction-analysis');
            Route::get('/transaction-analysis/data', [AccountingController::class, 'transactionAnalysisData'])->name('transaction-analysis.data');
            Route::get('/expense-analysis', [AccountingController::class, 'expenseAnalysis'])->name('expense-analysis');
            Route::get('/expense-analysis/data', [AccountingController::class, 'expenseAnalysisData'])->name('expense-analysis.data');
            Route::get('/payment-method-analysis', [AccountingController::class, 'paymentMethodAnalysis'])->name('payment-method-analysis');
            Route::get('/payment-method-analysis/data', [AccountingController::class, 'paymentMethodAnalysisData'])->name('payment-method-analysis.data');
            Route::get('/daily-summary', [AccountingController::class, 'dailySummary'])->name('daily-summary');
            Route::get('/daily-summary/data', [AccountingController::class, 'dailySummaryData'])->name('daily-summary.data');
            Route::get('/monthly-report', [AccountingController::class, 'monthlyReport'])->name('monthly-report');
            Route::get('/monthly-report/data', [AccountingController::class, 'monthlyReportData'])->name('monthly-report.data');
            Route::get('/reconciliation', [AccountingController::class, 'reconciliation'])->name('reconciliation');
            Route::get('/reconciliation/data', [AccountingController::class, 'reconciliationData'])->name('reconciliation.data');
            Route::get('/weekly-report', [AccountingController::class, 'weeklyReport'])->name('weekly-report');
            Route::get('/weekly-report/data', [AccountingController::class, 'weeklyReportData'])->name('weekly-report.data');
            Route::get('/user-performance-report', [AccountingController::class, 'userPerformanceReport'])->name('user-performance-report');
            Route::get('/user-performance-report/data', [AccountingController::class, 'userPerformanceReportData'])->name('user-performance-report.data');
        });


        Route::prefix('reports/expenses')->name('reports.expenses.')->group(function () {
            Route::get('summary', [ExpenseReportsController::class, 'summary'])->name('summary');
            Route::get('by-category', [ExpenseReportsController::class, 'byCategory'])->name('by-category');
            Route::get('by-vendor', [ExpenseReportsController::class, 'byVendor'])->name('by-vendor');
            Route::get('by-employee', [ExpenseReportsController::class, 'byEmployee'])->name('by-employee');
            Route::get('by-payment-method', [ExpenseReportsController::class, 'byPaymentMethod'])->name('by-payment-method');
            Route::get('recurring', [ExpenseReportsController::class, 'recurring'])->name('recurring');
            Route::get('budget-vs-actual', [ExpenseReportsController::class, 'budgetVsActual'])->name('budget-vs-actual');
            Route::get('trends', [ExpenseReportsController::class, 'trends'])->name('trends');
            Route::get('tax-report', [ExpenseReportsController::class, 'taxReport'])->name('tax-report');
            Route::get('audit', [ExpenseReportsController::class, 'audit'])->name('audit');
            
            // Export routes
            Route::get('export/summary', [ExpenseReportsController::class, 'exportSummary'])->name('export.summary');
            Route::get('export/by-category', [ExpenseReportsController::class, 'exportByCategory'])->name('export.by-category');
            // Add other export routes...

        });



        // Order Reports Routes
        Route::prefix('reports/orders')->name('reports.orders.')->group(function () {
            Route::get('summary',      [OrderReportsController::class, 'summary'])->name('summary');
            Route::get('summary/data', [OrderReportsController::class, 'summaryData'])->name('summary-data');

            Route::get('profit-analysis',      [OrderReportsController::class, 'profitAnalysis'])->name('profit-analysis');
            Route::get('profit-analysis/data', [OrderReportsController::class, 'profitAnalysisData'])->name('profit-analysis-data');

            Route::get('by-customer',                              [OrderReportsController::class, 'byCustomer'])->name('by-customer');
            Route::get('by-customer/data',                         [OrderReportsController::class, 'byCustomerData'])->name('by-customer-data');
            Route::get('by-customer/{customerKey}/orders',         [OrderReportsController::class, 'byCustomerOrders'])->name('by-customer-orders');

            Route::get('by-product',      [OrderReportsController::class, 'byProduct'])->name('by-product');
            Route::get('by-product/data', [OrderReportsController::class, 'byProductData'])->name('by-product-data');

            Route::get('by-payment-method',                                  [OrderReportsController::class, 'byPaymentMethod'])->name('by-payment-method');
            Route::get('by-payment-method/data',                             [OrderReportsController::class, 'byPaymentMethodData'])->name('by-payment-method-data');
            Route::get('by-payment-method/{methodId}/transactions',           [OrderReportsController::class, 'byPaymentMethodTransactions'])->name('by-payment-method-transactions');
            Route::get('by-payment-method/{methodId}/failed-transactions',    [OrderReportsController::class, 'byPaymentMethodFailedTransactions'])->name('by-payment-method-failed');

            Route::get('by-employee',                              [OrderReportsController::class, 'byEmployee'])->name('by-employee');
            Route::get('by-employee/data',                         [OrderReportsController::class, 'byEmployeeData'])->name('by-employee-data');
            Route::get('by-employee/{employeeId}/orders',          [OrderReportsController::class, 'byEmployeeOrders'])->name('by-employee-orders');

            Route::get('time-analysis',      [OrderReportsController::class, 'timeAnalysis'])->name('time-analysis');
            Route::get('time-analysis/data', [OrderReportsController::class, 'timeAnalysisData'])->name('time-analysis-data');

            Route::get('returns-refunds',                               [OrderReportsController::class, 'returnsRefunds'])->name('returns-refunds');
            Route::get('returns-refunds/data',                          [OrderReportsController::class, 'returnsRefundsData'])->name('returns-refunds-data');
            Route::get('returns-refunds/order/{orderId}',               [OrderReportsController::class, 'returnsRefundsOrderDetail'])->name('returns-refunds-order-detail');
            Route::get('returns-refunds/payment/{paymentId}',           [OrderReportsController::class, 'returnsRefundsPaymentDetail'])->name('returns-refunds-payment-detail');

            Route::get('discount-analysis',                       [OrderReportsController::class, 'discountAnalysis'])->name('discount-analysis');
            Route::get('discount-analysis/data',                  [OrderReportsController::class, 'discountAnalysisData'])->name('discount-analysis-data');
            Route::get('discount-analysis/order/{orderId}',       [OrderReportsController::class, 'discountAnalysisOrder'])->name('discount-analysis-order');

            Route::get('sales-forecast',      [OrderReportsController::class, 'salesForecast'])->name('sales-forecast');
            Route::get('sales-forecast/data', [OrderReportsController::class, 'salesForecastData'])->name('sales-forecast-data');

            Route::get('inventory-sales',                       [OrderReportsController::class, 'inventorySales'])->name('inventory-sales');
            Route::get('inventory-sales/data',                  [OrderReportsController::class, 'inventorySalesData'])->name('inventory-sales-data');
            Route::get('inventory-sales/product/{variantId}',   [OrderReportsController::class, 'inventorySalesProduct'])->name('inventory-sales-product');

        });
        Route::get('/recipes/{productId}/ingredients', [OrderReportsController::class, 'getIngredients'])
            ->name('recipes.ingredients');
        Route::get('/variants/{variantId}/serials', [OrderReportsController::class, 'getVariantSerials'])
            ->name('variants.serials');



        Route::prefix('reports/invoices')->name('reports.invoices.')->group(function () {
            Route::get('summary',      [InvoiceReportsController::class, 'summary'])->name('summary');
            Route::get('summary/data', [InvoiceReportsController::class, 'summaryData'])->name('summary-data');
            Route::get('summary/{id}', [InvoiceReportsController::class, 'summaryDetail'])->name('summary-detail');

            Route::get('outstanding',      [InvoiceReportsController::class, 'outstanding'])->name('outstanding');
            Route::get('outstanding/data', [InvoiceReportsController::class, 'outstandingData'])->name('outstanding-data');
            Route::get('outstanding/{id}', [InvoiceReportsController::class, 'outstandingDetail'])->name('outstanding-detail');

            Route::get('payments',      [InvoiceReportsController::class, 'payments'])->name('payments');
            Route::get('payments/data', [InvoiceReportsController::class, 'paymentsData'])->name('payments-data');
            Route::get('payments/{id}', [InvoiceReportsController::class, 'paymentsDetail'])->name('payments-detail');

            Route::get('delivery',      [InvoiceReportsController::class, 'delivery'])->name('delivery');
            Route::get('delivery/data', [InvoiceReportsController::class, 'deliveryData'])->name('delivery-data');
            Route::get('delivery/{id}', [InvoiceReportsController::class, 'deliveryDetail'])->name('delivery-detail');

            Route::get('status-trends',      [InvoiceReportsController::class, 'statusTrends'])->name('status-trends');
            Route::get('status-trends/data', [InvoiceReportsController::class, 'statusTrendsData'])->name('status-trends-data');
            Route::get('status-trends/{id}', [InvoiceReportsController::class, 'statusTrendsDetail'])->name('status-trends-detail');

        });


        // Product Reports Routes
        Route::prefix('reports/products')->name('reports.products.')->group(function () {
            Route::get('summary', [ProductsController::class, 'summary'])->name('summary');
            Route::get('performance', [ProductsController::class, 'performance'])->name('performance');
            Route::get('inventory', [ProductsController::class, 'inventory'])->name('inventory');
            Route::get('stock-movement', [ProductsController::class, 'stockMovement'])->name('stock-movement');
            Route::get('margin', [ProductsController::class, 'margin'])->name('margin');
            Route::get('by-category', [ProductsController::class, 'byCategory'])->name('by-category');
        });

        Route::prefix('reports/products')->name('reports.products.')->group(function () {
            Route::get('/strategy', [InventoryStrategyReportController::class, 'index'])->name('strategy');
            Route::get('/strategy/detail/{productId}', [InventoryStrategyReportController::class, 'detail'])->name('strategy.detail');
            Route::get('/strategy/recipe-detail/{productId}', [InventoryStrategyReportController::class, 'recipeDetail'])->name('strategy.recipe-detail');
        });

        Route::prefix('reports/production')->name('reports.production.')->group(function () {
            Route::get('/',        [ProductionOrderReportController::class, 'index'])->name('index');
            Route::get('/data',    [ProductionOrderReportController::class, 'data'])->name('data');

            Route::get('/summary',      [ProductionOrderReportController::class, 'summary'])->name('summary');
            Route::get('/summary/data', [ProductionOrderReportController::class, 'summaryData'])->name('summary-data');

            Route::get('/detail/{orderId}',     [ProductionOrderReportController::class, 'detail'])->name('detail');
            
            Route::get('/cost-analysis',      [ProductionOrderReportController::class, 'costAnalysis'])->name('cost-analysis');
            Route::get('/cost-analysis/data', [ProductionOrderReportController::class, 'costAnalysisData'])->name('cost-analysis-data');

            Route::get('/efficiency',      [ProductionOrderReportController::class, 'efficiency'])->name('efficiency');
            Route::get('/efficiency/data', [ProductionOrderReportController::class, 'efficiencyData'])->name('efficiency-data');

            Route::get('/inventory-impact',      [ProductionOrderReportController::class, 'inventoryImpact'])->name('inventory-impact');
            Route::get('/inventory-impact/data', [ProductionOrderReportController::class, 'inventoryImpactData'])->name('inventory-impact-data');

            Route::get('/quality-analysis',      [ProductionOrderReportController::class, 'qualityAnalysis'])->name('quality-analysis');
            Route::get('/quality-analysis/data', [ProductionOrderReportController::class, 'qualityAnalysisData'])->name('quality-analysis-data');

            Route::get('/input-output',      [ProductionOrderReportController::class, 'inputOutput'])->name('input-output');
            Route::get('/input-output/data', [ProductionOrderReportController::class, 'inputOutputData'])->name('input-output-data');

            Route::get('/waste',      [ProductionOrderReportController::class, 'waste'])->name('waste');
            Route::get('/waste/data', [ProductionOrderReportController::class, 'wasteData'])->name('waste-data');

            Route::get('batch-tracking',             [ProductionOrderReportController::class, 'batchTracking'])->name('batch-tracking');
            Route::get('batch-tracking/data',        [ProductionOrderReportController::class, 'batchTrackingData'])->name('batch-tracking-data');
            Route::get('batch-tracking/detail/{id}', [ProductionOrderReportController::class, 'batchTrackingDetail'])->name('batch-tracking-detail');

            Route::get('/export',               [ProductionOrderReportController::class, 'export'])->name('export');
        });

        // Inventory Reports Routes
        Route::prefix('reports/inventory')->name('reports.inventory.')->group(function () {
            Route::get('summary',      [InventoryReportsController::class, 'summary'])->name('summary');
            Route::get('summary/data', [InventoryReportsController::class, 'summaryData'])->name('summary-data');

            Route::get('turnover',      [InventoryReportsController::class, 'turnover'])->name('turnover');
            Route::get('turnover/data', [InventoryReportsController::class, 'turnoverData'])->name('turnover-data');

            Route::get('stock-aging',      [InventoryReportsController::class, 'stockAging'])->name('stock-aging');
            Route::get('stock-aging/data', [InventoryReportsController::class, 'stockAgingData'])->name('stock-aging-data');

            Route::get('low-stock-alerts',      [InventoryReportsController::class, 'lowStockAlerts'])->name('low-stock-alerts');
            Route::get('low-stock-alerts/data', [InventoryReportsController::class, 'lowStockAlertsData'])->name('low-stock-alerts-data');

            Route::get('adjustments',              [InventoryReportsController::class, 'adjustments'])->name('adjustments');
            Route::get('adjustments/data',         [InventoryReportsController::class, 'adjustmentsData'])->name('adjustments-data');
            Route::get('adjustments/detail/{id}',  [InventoryReportsController::class, 'adjustmentDetail'])->name('adjustments-detail');

            Route::get('transactions',             [InventoryReportsController::class, 'transactions'])->name('transactions');
            Route::get('transactions/data',        [InventoryReportsController::class, 'transactionsData'])->name('transactions-data');
            Route::get('transactions/detail/{id}', [InventoryReportsController::class, 'transactionDetail'])->name('transactions-detail');

            Route::get('abc-analysis',      [InventoryReportsController::class, 'abcAnalysis'])->name('abc-analysis');
            Route::get('abc-analysis/data', [InventoryReportsController::class, 'abcAnalysisData'])->name('abc-analysis-data');

            Route::get('movement-analysis',      [InventoryReportsController::class, 'movementAnalysis'])->name('movement-analysis');
            Route::get('movement-analysis/data', [InventoryReportsController::class, 'movementAnalysisData'])->name('movement-analysis-data');

            
            // Additional possible reports
            Route::get('valuation',      [InventoryReportsController::class, 'valuation'])->name('valuation');
            Route::get('valuation/data', [InventoryReportsController::class, 'valuationData'])->name('valuation-data');

            Route::get('dead-stock',      [InventoryReportsController::class, 'deadStock'])->name('dead-stock');
            Route::get('dead-stock/data', [InventoryReportsController::class, 'deadStockData'])->name('dead-stock-data');

            Route::get('excess-stock',      [InventoryReportsController::class, 'excessStock'])->name('excess-stock');
            Route::get('excess-stock/data', [InventoryReportsController::class, 'excessStockData'])->name('excess-stock-data');

            Route::get('excess-stock',      [InventoryReportsController::class, 'excessStock'])->name('excess-stock');
            Route::get('excess-stock/data', [InventoryReportsController::class, 'excessStockData'])->name('excess-stock-data');


            Route::get('turnover-ratio', [InventoryReportsController::class, 'turnoverRatio'])->name('turnover-ratio');
            Route::get('stock-accuracy', [InventoryReportsController::class, 'stockAccuracy'])->name('stock-accuracy');

            Route::get('/movement-logs', [InventoryReportsController::class, 'getMovementLogs'])
                ->name('movement-logs');
        });

        // Purchasing Reports Routes
        Route::prefix('reports/purchasing')->name('reports.purchasing.')->group(function () {
            Route::get('purchase-order-summary',      [PurchasingReportsController::class, 'purchaseOrderSummary'])->name('purchase-order-summary');
            Route::get('purchase-order-summary/data', [PurchasingReportsController::class, 'purchaseOrderSummaryData'])->name('purchase-order-summary-data');
            Route::get('purchase-order-summary/detail/{id}', [PurchasingReportsController::class, 'purchaseOrderSummaryDetail'])->name('purchase-order-summary-detail');

            Route::get('supplier-performance',      [PurchasingReportsController::class, 'supplierPerformance'])->name('supplier-performance');
            Route::get('supplier-performance/data', [PurchasingReportsController::class, 'supplierPerformanceData'])->name('supplier-performance-data');

            Route::get('purchase-order-status',             [PurchasingReportsController::class, 'purchaseOrderStatus'])->name('purchase-order-status');
            Route::get('purchase-order-status/data',        [PurchasingReportsController::class, 'purchaseOrderStatusData'])->name('purchase-order-status-data');
            Route::get('purchase-order-status/detail/{id}', [PurchasingReportsController::class, 'purchaseOrderStatusDetail'])->name('purchase-order-status-detail');

            Route::get('purchase-receipts',             [PurchasingReportsController::class, 'purchaseReceipts'])->name('purchase-receipts');
            Route::get('purchase-receipts/data',        [PurchasingReportsController::class, 'purchaseReceiptsData'])->name('purchase-receipts-data');
            Route::get('purchase-receipts/detail/{id}', [PurchasingReportsController::class, 'purchaseReceiptsDetail'])->name('purchase-receipts-detail');

            Route::get('supplier-spend-analysis',             [PurchasingReportsController::class, 'supplierSpendAnalysis'])->name('supplier-spend-analysis');
            Route::get('supplier-spend-analysis/data',        [PurchasingReportsController::class, 'supplierSpendAnalysisData'])->name('supplier-spend-analysis-data');
            Route::get('supplier-spend-analysis/detail/{id}', [PurchasingReportsController::class, 'supplierSpendAnalysisDetail'])->name('supplier-spend-analysis-detail');

            Route::get('purchase-order-items',             [PurchasingReportsController::class, 'purchaseOrderItems'])->name('purchase-order-items');
            Route::get('purchase-order-items/data',        [PurchasingReportsController::class, 'purchaseOrderItemsData'])->name('purchase-order-items-data');
            Route::get('purchase-order-items/detail/{id}', [PurchasingReportsController::class, 'purchaseOrderItemsDetail'])->name('purchase-order-items-detail');

            Route::get('payment-status',             [PurchasingReportsController::class, 'paymentStatus'])->name('payment-status');
            Route::get('payment-status/data',        [PurchasingReportsController::class, 'paymentStatusData'])->name('payment-status-data');
            Route::get('payment-status/detail/{id}', [PurchasingReportsController::class, 'paymentStatusDetail'])->name('payment-status-detail');

            Route::get('received-inventory', [PurchasingReportsController::class, 'receivedInventory'])->name('received-inventory');
            Route::get('received-inventory/data', [PurchasingReportsController::class, 'receivedInventoryData'])->name('received-inventory-data');
            
            Route::get('supplier-risk-assessment',      [PurchasingReportsController::class, 'supplierRiskAssessment'])->name('supplier-risk-assessment');
            Route::get('supplier-risk-assessment/data', [PurchasingReportsController::class, 'supplierRiskAssessmentData'])->name('supplier-risk-assessment-data');

            Route::get('purchase-cost-analysis',      [PurchasingReportsController::class, 'purchaseCostAnalysis'])->name('purchase-cost-analysis');
            Route::get('purchase-cost-analysis/data', [PurchasingReportsController::class, 'purchaseCostAnalysisData'])->name('purchase-cost-analysis-data');

            
            Route::get('export', [PurchasingReportsController::class, 'export'])->name('export');
        });
        Route::get('/api/suppliers/{id}/spend-details', [PurchasingReportsController::class, 'getSupplierSpendDetails'])
            ->name('api.supplier.spend-details');

        
        Route::get('/api/purchase-orders/{id}/details', function ($id) {
            $tenantId = auth()->user()->tenant_id;
            
            $order = \App\Models\PurchaseOrder::with([
                'supplier',
                'location',
                'items.productVariant.product',
                'items' => function ($query) {
                    $query->select([
                        'id',
                        'purchase_order_id',
                        'product_variant_id',
                        'product_name',
                        'sku',
                        'quantity',
                        'unit_cost',
                        'total_cost',
                        'received_quantity'
                    ]);
                }
            ])
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->first();
            
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Purchase order not found'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => $order
            ]);
        });

        Route::get('/api/purchase-receipts/{id}/details', function ($id) {
            $tenantId = auth()->user()->tenant_id;
            
            $receipt = \App\Models\PurchaseReceipt::with([
                'purchaseOrder.supplier',
                'purchaseOrder.location',
                'receiver',
                'items.purchaseOrderItem.productVariant.product'
            ])
            ->whereHas('purchaseOrder', function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            })
            ->where('id', $id)
            ->first();
            
            if (!$receipt) {
                return response()->json([
                    'success' => false,
                    'message' => 'Purchase receipt not found'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => $receipt
            ]);
        });
        


        // Advance Accounts
        Route::middleware(['auth:sanctum', 'tenant'])->prefix('accounting')->group(function () {
            // Chart of Accounts
            Route::apiResource('chart-of-accounts', ChartOfAccountController::class);
            Route::get('chart-of-accounts/{account}/balance', [ChartOfAccountController::class, 'balance']);
            
            // Journal Entries
            Route::apiResource('journal-entries', JournalEntryController::class);
            Route::post('journal-entries/{journal}/post', [JournalEntryController::class, 'post']);
            Route::post('journal-entries/{journal}/void', [JournalEntryController::class, 'void']);
            
            // Accounting Periods
            Route::apiResource('accounting-periods', AccountingPeriodController::class);
            Route::post('accounting-periods/{period}/close', [AccountingPeriodController::class, 'close']);
            
            // Reports
            Route::get('reports/trial-balance', [ReportController::class, 'trialBalance']);
            Route::get('reports/profit-loss', [ReportController::class, 'profitLoss']);
            Route::get('reports/balance-sheet', [ReportController::class, 'balanceSheet']);
            Route::get('reports/general-ledger', [ReportController::class, 'generalLedger']);
        });

        



    });

    // Fallback route - must be the LAST route
    Route::fallback(function () {
        // Log the 404 error for debugging
        Log::warning('404 Not Found', [
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => auth()->id(),
        ]);
        
        // Check if it's an API request
        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.route_not_found'),
                'error_code' => 'ROUTE_NOT_FOUND'
            ], 404);
        }
        
        // For web requests, show custom 404 page
        return response()->view('errors.404', [
            'message' => __('payments.page_not_found'),
            'previous_url' => url()->previous() !== url()->current() ? url()->previous() : null
        ], 404);
    });

    


require __DIR__.'/auth.php';
require __DIR__.'/remote.php';
