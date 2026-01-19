<?php

use App\Http\Controllers\{ ProfileController, UserController, RoleController};
use App\Http\Controllers\Home\{ DashboardController, LocationController, SettingsController,  UnitOfMeasureController, CurrencyController};
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Tenant\{ DepartmentController, EmployeeController, EmployeePaymentController };
use App\Http\Controllers\Catalog\ {CategoryController, InventoryItemController, ProductVariantController, InventoryAdjustmentsController, ProductController, ProductCategoryController};
use App\Http\Controllers\Orders\{ OrderController, POSController};
use App\Http\Controllers\Setting\{ TaxController,PromotionController, PaymentMethodController };
use App\Http\Controllers\Procurement\{ SupplierController, PurchaseOrderController, ExpenseCategoryController, ExpenseController };
use App\Http\Controllers\Accounts\{ AccountingController };
use App\Http\Controllers\Reports\{ ExpenseReportsController, OrderReportsController, ProductsController, InventoryReportsController };


    Route::get('/', function () {
        return view('welcome');
    });

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

        Route::resource('user', EmployeeController::class);
        Route::post('/user-status/{id}', [EmployeeController::class, 'changeUserStatus']);

        Route::resource('payment', EmployeePaymentController::class);
        Route::post('/payment-status/{id}', [EmployeePaymentController::class, 'updatePaymentStatus']);

        Route::resource('paymentmethod', PaymentMethodController::class);
        Route::post('/payment-methods-status/{id}', [PaymentMethodController::class, 'changePaymentMethodStatus'])->name('payment-methods.status');


        // Roles n Permissions
        Route::get('/role-index', [RoleController::class, 'index'])->name('role.index');
        Route::get('/permission-index', [RoleController::class, 'permissionIndex'])->name('permission.index');
        Route::post('/role-new', [RoleController::class, 'storeRole'])->name('role.store');
        Route::put('/role-update/{role}', [RoleController::class, 'updateRole'])->name('role.update');
        Route::delete('/role-destroy/{id}', [RoleController::class, 'destroy'])->name('role.destroy');
        Route::put('/update-permissions/{id}', [RoleController::class, 'updatePermission'])->name('permission.update');
        Route::put('/revoke-permissions/{id}', [RoleController::class, 'revokePermission'])->name('permission.revoke');

        // Inventory - Category
        Route::resource('category', CategoryController::class);
        Route::post('/category-status/{id}', [CategoryController::class, 'changeCategoryStatus'])->name('category.status');


        Route::resource('product-category', ProductCategoryController::class);
        Route::post('/product-category-status/{id}', [ProductCategoryController::class, 'changeProductCategoryStatus'])->name('product.category.status');

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
            

        // Stores and Inventory
        Route::resource('items', InventoryItemController::class);
        Route::resource('stocks', InventoryAdjustmentsController::class);
        Route::put('/transfer-stock/{id}', [InventoryAdjustmentsController::class, 'transferStock'])
            ->name('transfer.stock');

        
        // Location
        Route::resource('locations', LocationController::class);
        Route::post('/location-primary/{id}', [LocationController::class, 'updatePrimaryStatus']);
        Route::post('/location-status/{id}', [LocationController::class, 'updateLocationStatus']);


        
        // Orders and Purchases
        Route::resource('orders', OrderController::class);
        Route::get('/pos-index', [POSController::class, 'index'])->name('pos.index');
        Route::post('/orders/process-payment', [POSController::class, 'processPayment'])
            ->name('orders.process-payment');
        Route::post('/orders/checkout', [POSController::class, 'completePayment'])->name('orders.checkout');

        Route::post('/orders/complete-payment', [POSController::class, 'completePayment'])
            ->name('orders.complete-payment');
        
        Route::post('/pos-cancel/{id}', [POSController::class, 'cancel']);


        // Taxes and Promotions
        Route::resource('tax', TaxController::class);
        Route::post('/tax-status/{id}', [TaxController::class, 'updateTaxStatus']);
        Route::resource('promotion', PromotionController::class);
        Route::post('/promotion-status/{id}', [PromotionController::class, 'updatePromotionStatus']);


        // Suppliers & Purchasing
        Route::resource('suppliers', SupplierController::class);
        Route::post('/supplier-status/{id}', [SupplierController::class, 'updateSupplierStatus']);
        
        
        Route::get('/purchase-order', [PurchaseOrderController::class, 'index'])->name('purchase_order.index');
        Route::post('/purchase-order', [PurchaseOrderController::class, 'store'])->name('purchase-order.store');
        Route::post('/purchase-status/{id}', [PurchaseOrderController::class, 'submitApproval'])->name('po.submit-approval');
        Route::post('/purchase-approve/{id}', [PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
        Route::post('/purchase-send/{id}', [PurchaseOrderController::class, 'sendToSupplier'])->name('purchase-orders.send');
        Route::post('/purchase-orders/{purchaseOrder}/receive-items', [PurchaseOrderController::class, 'receiveItems'])->name('purchase-orders.receive-items');
        Route::post('/purchase-cancel/{id}', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');
        Route::delete('/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'destroy'])->name('purchase-orders.destroy');

        // Expenses
        Route::resource('expense-category', ExpenseCategoryController::class);

        Route::resource('expense', ExpenseController::class);
        Route::post('/expense-status/{id}', [ExpenseController::class, 'updateExpenseStatus'])->name('updateExpenseStatus');
        Route::post('/{id}/approve', [ExpenseController::class, 'approve'])->name('approveExpense');

        Route::put('/expenses/{id}/receipt', [ExpenseController::class, 'updateReceipt'])
            ->name('expenses.update-receipt')
            ->middleware('auth');
        Route::post('/expenses/{id}/approve', [ExpenseController::class, 'approve'])
            ->name('expenses.approve')
            ->middleware('auth');



        // Basic Accounting Routes
        Route::prefix('accounting')->name('accounting.')->group(function () {
            Route::get('/payment-methods', [AccountingController::class, 'paymentMethods'])->name('payment-methods.index');
            Route::get('/account-balances', [AccountingController::class, 'accountBalances'])->name('account-balances');
            Route::get('/transaction-ledger', [AccountingController::class, 'transactionLedger'])->name('transaction-ledger');
            Route::get('/income-statement', [AccountingController::class, 'incomeStatement'])->name('income-statement');
            Route::get('/cash-flow', [AccountingController::class, 'cashFlow'])->name('cash-flow');
            Route::get('/transaction-analysis', [AccountingController::class, 'transactionAnalysis'])->name('transaction-analysis');
            Route::get('/expense-analysis', [AccountingController::class, 'expenseAnalysis'])->name('expense-analysis');
            Route::get('/payment-method-analysis', [AccountingController::class, 'paymentMethodAnalysis'])->name('payment-method-analysis');
            Route::get('/daily-summary', [AccountingController::class, 'dailySummary'])->name('daily-summary');
            Route::get('/monthly-report', [AccountingController::class, 'monthlyReport'])->name('monthly-report');
            Route::get('/reconciliation', [AccountingController::class, 'reconciliation'])->name('reconciliation');
            Route::get('/transaction-ledger/details/{id}', [AccountingController::class, 'getTransactionDetails'])->name('transaction-details');
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
            Route::get('summary', [OrderReportsController::class, 'summary'])->name('summary');
            Route::get('by-customer', [OrderReportsController::class, 'byCustomer'])->name('by-customer');
            Route::get('by-product', [OrderReportsController::class, 'byProduct'])->name('by-product');
            Route::get('by-payment-method', [OrderReportsController::class, 'byPaymentMethod'])->name('by-payment-method');
            Route::get('by-employee', [OrderReportsController::class, 'byEmployee'])->name('by-employee');
            Route::get('time-analysis', [OrderReportsController::class, 'timeAnalysis'])->name('time-analysis');
            Route::get('returns-refunds', [OrderReportsController::class, 'returnsRefunds'])->name('returns-refunds');
            Route::get('discount-analysis', [OrderReportsController::class, 'discountAnalysis'])->name('discount-analysis');
            Route::get('sales-forecast', [OrderReportsController::class, 'salesForecast'])->name('sales-forecast');
            Route::get('inventory-sales', [OrderReportsController::class, 'inventorySales'])->name('inventory-sales');
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

        // Inventory Reports Routes
        Route::prefix('reports/inventory')->name('reports.inventory.')->group(function () {
            Route::get('summary', [InventoryReportsController::class, 'summary'])->name('summary');
            Route::get('turnover', [InventoryReportsController::class, 'turnover'])->name('turnover');
            Route::get('stock-aging', [InventoryReportsController::class, 'stockAging'])->name('stock-aging');
            Route::get('low-stock-alerts', [InventoryReportsController::class, 'lowStockAlerts'])->name('low-stock-alerts');
            Route::get('transactions', [InventoryReportsController::class, 'transactions'])->name('transactions');
            Route::get('adjustments', [InventoryReportsController::class, 'adjustments'])->name('adjustments');
            Route::get('abc-analysis', [InventoryReportsController::class, 'abcAnalysis'])->name('abc-analysis');
            Route::get('movement-analysis', [InventoryReportsController::class, 'movementAnalysis'])->name('movement-analysis');
            
            // Additional possible reports
            Route::get('valuation', [InventoryReportsController::class, 'valuation'])->name('valuation');
            Route::get('dead-stock', [InventoryReportsController::class, 'deadStock'])->name('dead-stock');
            Route::get('turnover-ratio', [InventoryReportsController::class, 'turnoverRatio'])->name('turnover-ratio');
            Route::get('stock-accuracy', [InventoryReportsController::class, 'stockAccuracy'])->name('stock-accuracy');
            Route::get('excess-stock', [InventoryReportsController::class, 'excessStock'])->name('excess-stock');

            Route::get('/movement-logs', [InventoryReportsController::class, 'getMovementLogs'])
                ->name('movement-logs');
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


require __DIR__.'/auth.php';
