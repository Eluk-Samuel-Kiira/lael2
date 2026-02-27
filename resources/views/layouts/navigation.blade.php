
<style>
    /* Ensure menu title and menu link have larger fonts */
    .menu-item .menu-link .menu-title {
        font-size: 1.1rem; /* Increased font size for the menu title */
        font-weight: bold; /* Bold the menu title */
        font-family: 'Verdana', sans-serif; /* Set a distinct font for menu title */
    }

    /* Increase font size of the menu link text */
    .menu-item .menu-link {
        font-size: 2rem; /* Increase the size of the link text */
        font-family: 'Arial', sans-serif; /* Font style for the link */
    }

    /* Increase the size of the icon */
    .menu-item .menu-icon i {
        font-size: 2rem; /* Increase the icon size */
        color: #007bff; /* Optional: Change the icon color */
    }

    /* Increase the arrow size */
    .menu-item .menu-arrow {
        font-size: 1.5rem; /* Increase the size of the menu arrow */
    }

</style>
<div class="app-sidebar-menu overflow-hidden flex-column-fluid">
    <div id="kt_app_sidebar_menu_wrapper" class="app-sidebar-wrapper">
        <div id="kt_app_sidebar_menu_scroll" class="scroll-y my-5 mx-3" data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-height="auto" data-kt-scroll-dependencies="#kt_app_sidebar_logo, #kt_app_sidebar_footer" data-kt-scroll-wrappers="#kt_app_sidebar_menu" data-kt-scroll-offset="5px">
            <div class="menu menu-column menu-rounded menu-sub-indention px-3" id="#kt_app_sidebar_menu" data-kt-menu="true" data-kt-menu-expand="false">

                <div data-kt-menu-trigger="click" class="menu-item here menu-accordion">
                    <span class="menu-link">
                        <span class="menu-icon">
                            <i class="bi bi-building-dash fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                                <span class="path4"></span>
                            </i>
                        </span>
                        <span class="menu-title" style="font-family:  {{ getMailOptions('font_family') }}; font-size: {{ getMailOptions('font_size') }}rem">{{__('auth._dashboard')}}</span>
                        <span class="menu-arrow"></span>
                    </span>

                    <div class="menu-sub menu-sub-accordion">
                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('dashboard') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('auth._dashboard') }}</span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('overview') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('auth._overview') }}</span>
                            </a>
                        </div>


                    </div>
                </div>

                <!-- POS -->
                @can('view order')
                <div data-kt-menu-trigger="click" class="menu-item here {{ is_tab_show([]) }} menu-accordion">
                    <span class="menu-link">
                        <span class="menu-icon">
                            <i class="bi bi-shop fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                                <span class="path4"></span>
                            </i>
                        </span>
                        <span class="menu-title" style="font-family:  {{ getMailOptions('font_family') }}; font-size: {{ getMailOptions('font_size') }}rem">{{__('pagination._pos')}}</span>
                        <span class="menu-arrow"></span>
                    </span>

                    <div class="menu-sub menu-sub-accordion">
                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('pos.index') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('pagination.pos_index') }}</span>
                            </a>
                        </div>
                    </div>

                    <div class="menu-sub menu-sub-accordion">
                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('orders.index') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('pagination._orders') }}</span>
                            </a>
                        </div>
                    </div>
                </div>
                @endcan

                <!-- Product Catalog -->
                <div data-kt-menu-trigger="click" class="menu-item here menu-accordion">
                    <span class="menu-link">
                        <span class="menu-icon">
                            <i class="bi bi-basket3-fill fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                                <span class="path4"></span>
                            </i>
                        </span>
                        <span class="menu-title" style="font-family:  {{ getMailOptions('font_family') }}; font-size: {{ getMailOptions('font_size') }}rem">{{__('pagination.product_catalog')}}</span>
                        <span class="menu-arrow"></span>
                    </span>

                    <div class="menu-sub menu-sub-accordion">
                        @can('view category')
                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('category.index') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('pagination._category') }}</span>
                            </a>
                        </div>
                        @endcan
                        @can('view subcategory')
                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('product-category.index') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('pagination.product_category') }}</span>
                            </a>
                        </div>
                        @endcan
                        @can('view product')
                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('products.index') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('pagination._products') }}</span>
                            </a>
                        </div>
                        @endcan

                    </div>
                </div>
                

                @if(!tenant_is_single_shop(auth()->user()->tenant_id))
                <!-- Inventory Items -->
                <div data-kt-menu-trigger="click" class="menu-item here menu-accordion">
                    <span class="menu-link">
                        <span class="menu-icon">
                            <i class="bi bi-luggage fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                                <span class="path4"></span>
                            </i>
                        </span>
                        <span class="menu-title" style="font-family:  {{ getMailOptions('font_family') }}; font-size: {{ getMailOptions('font_size') }}rem">{{__('pagination.store_inventory')}}</span>
                        <span class="menu-arrow"></span>
                    </span>

                    <div class="menu-sub menu-sub-accordion">
                        @can('view inventory')
                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('items.index') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('pagination.inventory_items') }}</span>
                            </a>
                        </div>

                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('stocks.index') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('passwords.stock_adjustments') }}</span>
                            </a>
                        </div>
                        @endcan

                    </div>
                </div>
                @endif


                <!-- Suppliers & Purchasing -->
                <div data-kt-menu-trigger="click" class="menu-item here menu-accordion">
                    <span class="menu-link">
                        <span class="menu-icon">
                            <i class="bi bi-truck fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                                <span class="path4"></span>
                            </i>
                        </span>
                        <span class="menu-title" style="font-family:  {{ getMailOptions('font_family') }}; font-size: {{ getMailOptions('font_size') }}rem">{{__('passwords.suppliers_purchase')}}</span>
                        <span class="menu-arrow"></span>
                    </span>

                    <div class="menu-sub menu-sub-accordion">
                        @can('view supplier')
                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('suppliers.index') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('passwords.suppliers') }}</span>
                            </a>
                        </div>
                        @endcan

                        @can('view purchase_orders')
                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('purchase_order.index') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('passwords.purchase_order') }}</span>
                            </a>
                        </div>
                        @endcan

                        @can('view category-expense')
                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('expense-category.index') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('passwords.expense-category') }}</span>
                            </a>
                        </div>
                        @endcan

                        @can('view expense')
                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('expense.index') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('passwords.expense') }}</span>
                            </a>
                        </div>
                        @endcan

                    </div>
                </div>

                
                <!-- Human Reource -->
                <div data-kt-menu-trigger="click" class="menu-item here menu-accordion">
                    <span class="menu-link">
                        <span class="menu-icon">
                            <i class="bi bi-people-fill fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                                <span class="path4"></span>
                            </i>
                        </span>
                        <span class="menu-title" style="font-family:  {{ getMailOptions('font_family') }}; font-size: {{ getMailOptions('font_size') }}rem">{{__('auth.human_resource')}}</span>
                        <span class="menu-arrow"></span>
                    </span>

                    <div class="menu-sub menu-sub-accordion">
                        @can('view user')
                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('employee.index') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('auth._users_index') }}</span>
                            </a>
                        </div>
                        @endcan

                        @can('view employee')
                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('user.index') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('pagination.employee_index') }}</span>
                            </a>
                        </div>
                        @endcan

                        @can('view employee payment')
                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('payment.index') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('pagination.employee_payment') }}</span>
                            </a>
                        </div>
                        @endcan

                        @can('admin only')
                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('role.index') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('auth._roles') }}</span>
                            </a>
                        </div>
                        @endcan

                        @can('admin only')
                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('permission.index') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('auth._permissions') }}</span>
                            </a>
                        </div>
                        @endcan


                    </div>
                </div>


                <!-- Updated Reports Menu -->
                @can('view reports')
                <div data-kt-menu-trigger="{default:'click', lg: 'hover'}" data-kt-menu-placement="right-start" class="menu-item here menu-lg-down-accordion">
                    <!--begin:Menu link-->
                    <span class="menu-link">
                        <span class="menu-icon">
                            <i class="bi bi-calculator fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                            </i>
                        </span>
                        <span class="menu-title" style="font-family: {{ getMailOptions('font_family') }}; font-size: {{ getMailOptions('font_size') }}rem">
                            {{__('accounting.reports')}}
                        </span>
                        <span class="menu-arrow"></span>
                    </span>
                    <!--end:Menu link-->
                    
                    <!--begin:Menu sub-->
                    <div class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown menu-active-bg px-lg-2 py-lg-4 w-lg-300px">
                        
                        <!-- Financial Reports Section -->
                        @can('financial reports')
                        <div data-kt-menu-trigger="click" class="menu-item menu-accordion menu-sub-indention">
                            <span class="menu-link">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title" style="font-family: {{ getMailOptions('font_family') }}; font-size: {{ getMailOptions('font_size') }}rem">
                                    {{__('accounting.financial_reports')}}
                                </span>
                                <span class="menu-arrow"></span>
                            </span>
                            <div class="menu-sub menu-sub-accordion">
                                <!-- Payment Methods -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('accounting.payment-methods.index') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{ __('accounting.payment_methods') }}</span>
                                    </a>
                                </div>
                                
                                <!-- Account Balances -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('accounting.account-balances') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{ __('accounting.account_balances') }}</span>
                                    </a>
                                </div>
                                
                                <!-- Transaction Ledger -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('accounting.transaction-ledger') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{ __('accounting.transaction_ledger') }}</span>
                                    </a>
                                </div>
                                
                                <!-- Income Statement -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('accounting.income-statement') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{ __('accounting.income_statement') }}</span>
                                    </a>
                                </div>
                                
                                <!-- Cash Flow -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('accounting.cash-flow') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{ __('accounting.cash_flow') }}</span>
                                    </a>
                                </div>
                                
                                <!-- Transaction Analysis -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('accounting.transaction-analysis') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{ __('accounting.transaction_analysis') }}</span>
                                    </a>
                                </div>
                                
                                <!-- Expense Analysis -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('accounting.expense-analysis') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{ __('accounting.expense_analysis') }}</span>
                                    </a>
                                </div>
                                
                                <!-- Payment Method Analysis -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('accounting.payment-method-analysis') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{ __('accounting.payment_method_analysis') }}</span>
                                    </a>
                                </div>
                                
                                <!-- Daily Summary -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('accounting.daily-summary') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{ __('accounting.daily_summary') }}</span>
                                    </a>
                                </div>
                                
                                <!-- Monthly Report -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('accounting.monthly-report') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{ __('accounting.monthly_report') }}</span>
                                    </a>
                                </div>
                                
                                <!-- Reconciliation -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('accounting.reconciliation') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{ __('accounting.reconciliation') }}</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endcan
                        
                        <!-- Expense Reports Section -->
                        @can('expense reports')
                        <div data-kt-menu-trigger="click" class="menu-item menu-accordion menu-sub-indention">
                            <span class="menu-link">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{__('accounting.expense_reports')}}</span>
                                <span class="menu-arrow"></span>
                            </span>
                            <div class="menu-sub menu-sub-accordion">
                                <!-- Expense Summary -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('reports.expenses.summary') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{__('accounting.expense_summary')}}</span>
                                    </a>
                                </div>
                                
                                <!-- Category Analysis -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('reports.expenses.by-category') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{__('accounting.by_category')}}</span>
                                    </a>
                                </div>
                                
                                <!-- Vendor Analysis -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('reports.expenses.by-vendor') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{__('accounting.by_vendor')}}</span>
                                    </a>
                                </div>
                                
                                <!-- Employee Expense -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('reports.expenses.by-employee') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{__('accounting.employee_expense')}}</span>
                                    </a>
                                </div>
                                
                                <!-- Payment Method -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('reports.expenses.by-payment-method') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{__('accounting.by_payment_method')}}</span>
                                    </a>
                                </div>
                                
                                <!-- Recurring Expenses -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('reports.expenses.recurring') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{__('accounting.recurring_expense')}}</span>
                                    </a>
                                </div>
                                
                                <!-- Budget vs Actual -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('reports.expenses.budget-vs-actual') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{__('accounting.budget_vs_actual')}}</span>
                                    </a>
                                </div>
                                
                                <!-- Expense Trends -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('reports.expenses.trends') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{__('accounting.expense_trends')}}</span>
                                    </a>
                                </div>
                                
                                <!-- Tax Report -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('reports.expenses.tax-report') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{__('accounting.expense_tax_report')}}</span>
                                    </a>
                                </div>
                                
                                <!-- Expense Audit -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('reports.expenses.audit') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{__('accounting.expense_audit')}}</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endcan
                        
                        <!-- Order Reports Section -->
                        @can('order reports')
                        <div data-kt-menu-trigger="click" class="menu-item menu-accordion menu-sub-indention">
                            <span class="menu-link">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{__('auth.order_reports')}}</span>
                                <span class="menu-arrow"></span>
                            </span>
                            <div class="menu-sub menu-sub-accordion">
                                <!-- Order Summary -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('reports.orders.summary') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{__('auth.order_summary')}}</span>
                                    </a>
                                </div>
                                
                                <!-- Sales by Customer -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('reports.orders.by-customer') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{__('auth.sales_by_customer')}}</span>
                                    </a>
                                </div>
                                
                                <!-- Sales by Product -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('reports.orders.by-product') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{__('auth.sales_by_product')}}</span>
                                    </a>
                                </div>
                                
                                <!-- Payment Method Analysis -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('reports.orders.by-payment-method') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{__('accounting.payment_method_analysis')}}</span>
                                    </a>
                                </div>
                                
                                <!-- Employee Performance -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('reports.orders.by-employee') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{__('auth.employee_performance')}}</span>
                                    </a>
                                </div>
                                
                                <!-- Time Analysis -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('reports.orders.time-analysis') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{__('auth.time_analysis')}}</span>
                                    </a>
                                </div>
                                
                                <!-- Returns & Refunds -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('reports.orders.returns-refunds') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{__('auth.returns_refunds')}}</span>
                                    </a>
                                </div>
                                
                                <!-- Discount Analysis -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('reports.orders.discount-analysis') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{__('auth.discount_analysis')}}</span>
                                    </a>
                                </div>
                                
                                <!-- Sales Forecast -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('reports.orders.sales-forecast') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{__('auth.sales_forecast')}}</span>
                                    </a>
                                </div>
                                
                                <!-- Inventory Sales (Sold vs Unsold) -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('reports.orders.inventory-sales') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{__('auth.inventory_sales')}}</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endcan

                        <!-- Product Reports Section -->
                        @can('product reports')
                        <div data-kt-menu-trigger="click" class="menu-item menu-accordion menu-sub-indention">
                            <span class="menu-link">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('auth.product_reports') }}</span>
                                <span class="menu-arrow"></span>
                            </span>
                            <div class="menu-sub menu-sub-accordion">
                                <!-- Product Summary -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('reports.products.summary') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{ __('auth.product_summary') }}</span>
                                    </a>
                                </div>
                                
                                <!-- Product Performance -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('reports.products.performance') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{ __('auth.product_performance') }}</span>
                                    </a>
                                </div>
                                
                                <!-- Inventory Valuation -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('reports.products.inventory') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{ __('auth.inventory_valuation') }}</span>
                                    </a>
                                </div>
                                
                                <!-- Stock Movement -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('reports.products.stock-movement') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{ __('auth.stock_movement') }}</span>
                                    </a>
                                </div>
                                
                                <!-- Product Margin -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('reports.products.margin') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{ __('auth.product_margin') }}</span>
                                    </a>
                                </div>
                                
                                <!-- By Category -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('reports.products.by-category') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{ __('auth.by_category') }}</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endcan

                        <!-- Inventory Reports Section -->
                        @can('inventory reports')
                        <div data-kt-menu-trigger="click" class="menu-item menu-accordion menu-sub-indention">
                            <span class="menu-link">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('pagination.inventory_reports') }}</span>
                                <span class="menu-arrow"></span>
                            </span>
                            <div class="menu-sub menu-sub-accordion">
                                <!-- Inventory Summary -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('reports.inventory.summary') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{ __('pagination.inventory_summary') }}</span>
                                    </a>
                                </div>
                                
                                <!-- Inventory Turnover -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('reports.inventory.turnover') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{ __('pagination.inventory_turnover') }}</span>
                                    </a>
                                </div>
                                
                                <!-- Stock Aging -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('reports.inventory.stock-aging') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{ __('pagination.stock_aging') }}</span>
                                    </a>
                                </div>
                                
                                <!-- Low Stock Alerts -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('reports.inventory.low-stock-alerts') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{ __('pagination.low_stock_alerts') }}</span>
                                    </a>
                                </div>
                                
                                <!-- Inventory Transactions -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('reports.inventory.transactions') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{ __('pagination.inventory_transactions') }}</span>
                                    </a>
                                </div>
                                
                                <!-- Inventory Adjustments -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('reports.inventory.adjustments') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{ __('pagination.inventory_adjustments') }}</span>
                                    </a>
                                </div>
                                
                                <!-- ABC Analysis -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('reports.inventory.abc-analysis') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{ __('pagination.abc_analysis') }}</span>
                                    </a>
                                </div>
                                
                                <!-- Fast/Slow Moving Items -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('reports.inventory.movement-analysis') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{ __('pagination.movement_analysis') }}</span>
                                    </a>
                                </div>
                                
                                <!-- Additional Reports (Optional) -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('reports.inventory.valuation') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{ __('pagination.inventory_valuation') }}</span>
                                    </a>
                                </div>
                                
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('reports.inventory.dead-stock') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{ __('pagination.dead_stock') }}</span>
                                    </a>
                                </div>
                                
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('reports.inventory.excess-stock') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{ __('pagination.excess_stock') }}</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endcan
                        
                        <!-- Purchasing Reports Section -->
                        @can('purchasing reports')
                        <div data-kt-menu-trigger="click" class="menu-item menu-accordion menu-sub-indention">
                            <span class="menu-link">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('pagination.purchasing_reports') }}</span>
                                <span class="menu-arrow"></span>
                            </span>
                            <div class="menu-sub menu-sub-accordion">
                                <!-- Purchase Order Summary -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('reports.purchasing.purchase-order-summary') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{ __('pagination.purchase_order_summary') }}</span>
                                    </a>
                                </div>
                                
                                <!-- Supplier Performance -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('reports.purchasing.supplier-performance') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{ __('pagination.supplier_performance') }}</span>
                                    </a>
                                </div>
                                
                                <!-- Purchase Order Status -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('reports.purchasing.purchase-order-status') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{ __('pagination.purchase_order_status') }}</span>
                                    </a>
                                </div>
                                
                                <!-- Purchase Receipts -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('reports.purchasing.purchase-receipts') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{ __('pagination.purchase_receipts') }}</span>
                                    </a>
                                </div>
                                
                                <!-- Supplier Spend Analysis -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('reports.purchasing.supplier-spend-analysis') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{ __('pagination.supplier_spend_analysis') }}</span>
                                    </a>
                                </div>
                                
                                <!-- Purchase Order Items -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('reports.purchasing.purchase-order-items') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{ __('pagination.purchase_order_items') }}</span>
                                    </a>
                                </div>
                                
                                <!-- Payment Status -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('reports.purchasing.payment-status') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{ __('pagination.payment_status') }}</span>
                                    </a>
                                </div>
                                
                                <!-- Received Inventory -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('reports.purchasing.received-inventory') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{ __('pagination.received_inventory') }}</span>
                                    </a>
                                </div>
                                
                                <!-- Supplier Risk Assessment -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('reports.purchasing.supplier-risk-assessment') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{ __('pagination.supplier_risk_assessment') }}</span>
                                    </a>
                                </div>
                                
                                <!-- Purchase Cost Analysis -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('reports.purchasing.purchase-cost-analysis') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{ __('pagination.purchase_cost_analysis') }}</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endcan
                        
                    </div>
                    <!--end:Menu sub-->
                </div>
                @endcan

                <!-- Settings -->
                <div data-kt-menu-trigger="click" class="menu-item here {{ is_tab_show([]) }} menu-accordion">
                    <span class="menu-link">
                        <span class="menu-icon">
                            <i class="bi bi-gear-wide-connected fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                                <span class="path4"></span>
                            </i>
                        </span>
                        <span class="menu-title" style="font-family:  {{ getMailOptions('font_family') }}; font-size: {{ getMailOptions('font_size') }}rem">{{__('auth.app_setting')}}</span>
                        <span class="menu-arrow"></span>
                    </span>

                    <div class="menu-sub menu-sub-accordion">
                        
                        @can('update settings')
                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('settings.index') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('auth.general_setting') }}</span>
                            </a>
                        </div>
                        @endcan

                        @can('view uom')
                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('uom.index') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('auth._uom') }}</span>
                            </a>
                        </div>
                        @endcan
                        
                        @can('view currency')
                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('currency.index') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('auth._currency') }}</span>
                            </a>
                        </div>
                        @endcan
                        
                        @can('view payment method')
                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('paymentmethod.index') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('payments._payment_methods') }}</span>
                            </a>
                        </div>
                        @endcan

                        @can('view department')
                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('department.index') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('auth._department') }}</span>
                            </a>
                        </div>
                        @endcan

                        @can('view location')
                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('locations.index') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('pagination._locations') }}</span>
                            </a>
                        </div>
                        @endcan

                        @can('view tax')
                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('tax.index') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('pagination._taxes') }}</span>
                            </a>
                        </div>
                        @endcan

                        @can('view promotion')
                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('promotion.index') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('pagination._promotion') }}</span>
                            </a>
                        </div>
                        @endcan

                        @role('super_admin')
                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('tenant.index') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('payments.tenants') }}</span>
                            </a>
                        </div>
                        @endrole


                    </div>
                </div>



            </div>
        </div>
    </div>
</div>
