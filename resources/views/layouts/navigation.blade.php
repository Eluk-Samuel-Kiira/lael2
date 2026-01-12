
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
                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('category.index') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('pagination._category') }}</span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('product-category.index') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('pagination.product_category') }}</span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('products.index') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('pagination._products') }}</span>
                            </a>
                        </div>

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
                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('suppliers.index') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('passwords.suppliers') }}</span>
                            </a>
                        </div>

                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('purchase_order.index') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('passwords.purchase_order') }}</span>
                            </a>
                        </div>

                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('expense-category.index') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('passwords.expense-category') }}</span>
                            </a>
                        </div>

                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('expense.index') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('passwords.expense') }}</span>
                            </a>
                        </div>

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
                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('employee.index') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('auth._users_index') }}</span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('user.index') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('pagination.employee_index') }}</span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('payment.index') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('pagination.employee_payment') }}</span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('role.index') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('auth._roles') }}</span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="reloadToApp('{{ route('permission.index') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('auth._permissions') }}</span>
                            </a>
                        </div>


                    </div>
                </div>

                <!-- Financial Reports -->
                {{--
                <div data-kt-menu-trigger="click" class="menu-item here menu-accordion">
                    <span class="menu-link">
                        <span class="menu-icon">
                            <i class="bi bi-calculator fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                            </i>
                        </span>
                        <span class="menu-title" style="font-family: {{ getMailOptions('font_family') }}; font-size: {{ getMailOptions('font_size') }}rem">{{__('accounting.financial_reports')}}</span>
                        <span class="menu-arrow"></span>
                    </span>

                    <div class="menu-sub menu-sub-accordion">
                        <!-- Payment Methods -->
                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('accounting.payment-methods.index') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('accounting.payment_methods') }}</span>
                            </a>
                        </div>
                        
                        <!-- Account Balances -->
                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('accounting.account-balances') }}')">
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
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('accounting.income-statement') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('accounting.income_statement') }}</span>
                            </a>
                        </div>
                        
                        <!-- Cash Flow -->
                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('accounting.cash-flow') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('accounting.cash_flow') }}</span>
                            </a>
                        </div>
                        
                        <!-- Transaction Analysis -->
                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('accounting.transaction-analysis') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('accounting.transaction_analysis') }}</span>
                            </a>
                        </div>
                        
                        <!-- Expense Analysis -->
                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('accounting.expense-analysis') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('accounting.expense_analysis') }}</span>
                            </a>
                        </div>
                        
                        <!-- Payment Method Analysis -->
                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('accounting.payment-method-analysis') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('accounting.payment_method_analysis') }}</span>
                            </a>
                        </div>
                        
                        <!-- Daily Summary -->
                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('accounting.daily-summary') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('accounting.daily_summary') }}</span>
                            </a>
                        </div>
                        
                        <!-- Monthly Report -->
                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('accounting.monthly-report') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('accounting.monthly_report') }}</span>
                            </a>
                        </div>
                        
                        <!-- Reconciliation -->
                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('accounting.reconciliation') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('accounting.reconciliation') }}</span>
                            </a>
                        </div>
                    </div>
                </div> 
                --}}

                <!-- Updated Reports Menu -->
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
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('accounting.payment-methods.index') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{ __('accounting.payment_methods') }}</span>
                                    </a>
                                </div>
                                
                                <!-- Account Balances -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('accounting.account-balances') }}')">
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
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('accounting.income-statement') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{ __('accounting.income_statement') }}</span>
                                    </a>
                                </div>
                                
                                <!-- Cash Flow -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('accounting.cash-flow') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{ __('accounting.cash_flow') }}</span>
                                    </a>
                                </div>
                                
                                <!-- Transaction Analysis -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('accounting.transaction-analysis') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{ __('accounting.transaction_analysis') }}</span>
                                    </a>
                                </div>
                                
                                <!-- Expense Analysis -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('accounting.expense-analysis') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{ __('accounting.expense_analysis') }}</span>
                                    </a>
                                </div>
                                
                                <!-- Payment Method Analysis -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('accounting.payment-method-analysis') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{ __('accounting.payment_method_analysis') }}</span>
                                    </a>
                                </div>
                                
                                <!-- Daily Summary -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('accounting.daily-summary') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{ __('accounting.daily_summary') }}</span>
                                    </a>
                                </div>
                                
                                <!-- Monthly Report -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('accounting.monthly-report') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{ __('accounting.monthly_report') }}</span>
                                    </a>
                                </div>
                                
                                <!-- Reconciliation -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('accounting.reconciliation') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{ __('accounting.reconciliation') }}</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Expense Reports Section -->
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
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('reports.expenses.summary') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{__('accounting.expense_summary')}}</span>
                                    </a>
                                </div>
                                
                                <!-- Category Analysis -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('reports.expenses.by-category') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{__('accounting.by_category')}}</span>
                                    </a>
                                </div>
                                
                                <!-- Vendor Analysis -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('reports.expenses.by-vendor') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{__('accounting.by_vendor')}}</span>
                                    </a>
                                </div>
                                
                                <!-- Employee Expense -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('reports.expenses.by-employee') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{__('accounting.employee_expense')}}</span>
                                    </a>
                                </div>
                                
                                <!-- Payment Method -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('reports.expenses.by-payment-method') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{__('accounting.by_payment_method')}}</span>
                                    </a>
                                </div>
                                
                                <!-- Recurring Expenses -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('reports.expenses.recurring') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{__('accounting.recurring_expense')}}</span>
                                    </a>
                                </div>
                                
                                <!-- Budget vs Actual -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('reports.expenses.budget-vs-actual') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{__('accounting.budget_vs_actual')}}</span>
                                    </a>
                                </div>
                                
                                <!-- Expense Trends -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('reports.expenses.trends') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{__('accounting.expense_trends')}}</span>
                                    </a>
                                </div>
                                
                                <!-- Tax Report -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('reports.expenses.tax-report') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{__('accounting.expense_tax_report')}}</span>
                                    </a>
                                </div>
                                
                                <!-- Expense Audit -->
                                <div class="menu-item">
                                    <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('reports.expenses.audit') }}')">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{__('accounting.expense_audit')}}</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Existing Catalog, Sales, Customers menus remain here -->
                        <!-- ... existing code ... -->
                        
                    </div>
                    <!--end:Menu sub-->
                </div>

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
                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('settings.index') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('auth.general_setting') }}</span>
                            </a>
                        </div>

                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('uom.index') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('auth._uom') }}</span>
                            </a>
                        </div>

                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('currency.index') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('auth._currency') }}</span>
                            </a>
                        </div>

                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('paymentmethod.index') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('payments._payment_methods') }}</span>
                            </a>
                        </div>

                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('department.index') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('auth._department') }}</span>
                            </a>
                        </div>

                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('locations.index') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('pagination._locations') }}</span>
                            </a>
                        </div>

                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('tax.index') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('pagination._taxes') }}</span>
                            </a>
                        </div>

                        <div class="menu-item">
                            <a class="menu-link" data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('promotion.index') }}')">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('pagination._promotion') }}</span>
                            </a>
                        </div>


                    </div>
                </div>



            </div>
        </div>
    </div>
</div>
