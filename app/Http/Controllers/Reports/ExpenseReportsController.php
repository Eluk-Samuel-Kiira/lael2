<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PaymentMethod;
use App\Models\Employee;
use Illuminate\Support\Facades\{ DB };
use Carbon\Carbon;

class ExpenseReportsController extends Controller
{

    // Expense Summary Report
    public function summary(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('expense reports')) {
            abort(403, __('payments.not_authorized'));
        }
        
        if (!$tenantId) {
            return redirect()->back()->with('error', __('accounting.invalid_tenant'));
        }
        
        // Get filter parameters
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->endOfMonth()->format('Y-m-d'));
        $categoryId = $request->get('category_id');
        $vendorName = $request->get('vendor_name');
        $paymentMethodId = $request->get('payment_method_id');
        $paymentStatus = $request->get('payment_status');
        $employeeId = $request->get('employee_id');
        $requiresReceipt = $request->get('requires_receipt');
        $isRecurring = $request->get('is_recurring');
        
        // Validate dates - ensure end date includes full day
        try {
            $startDateTime = Carbon::parse($startDate)->startOfDay();
            $endDateTime = Carbon::parse($endDate)->endOfDay();
            
            if ($startDateTime > $endDateTime) {
                [$startDateTime, $endDateTime] = [$endDateTime, $startDateTime];
            }
            
            $startDate = $startDateTime->format('Y-m-d');
            $endDate = $endDateTime->format('Y-m-d');
        } catch (\Exception $e) {
            $startDateTime = now()->startOfMonth();
            $endDateTime = now()->endOfMonth();
            $startDate = $startDateTime->format('Y-m-d');
            $endDate = $endDateTime->format('Y-m-d');
        }
        
        // Build main query with proper date range - Using Eloquent for automatic conversion
        $query = Expense::with(['category', 'paymentMethod', 'employee', 'supplier'])
            ->where('tenant_id', $tenantId)
            ->whereBetween('date', [$startDateTime->format('Y-m-d'), $endDateTime->format('Y-m-d')]);
        
        // Apply filters
        if ($categoryId && is_numeric($categoryId)) {
            $query->where('category_id', (int)$categoryId);
        }
        
        if ($vendorName && strlen(trim($vendorName)) >= 2) {
            $query->where(function($q) use ($vendorName) {
                $q->where('vendor_name', 'like', '%' . trim($vendorName) . '%')
                    ->orWhereHas('supplier', function($sq) use ($vendorName) {
                        $sq->where('name', 'like', '%' . trim($vendorName) . '%');
                    });
            });
        }
        
        if ($paymentMethodId && is_numeric($paymentMethodId)) {
            $query->where('payment_method_id', (int)$paymentMethodId);
        }
        
        if ($paymentStatus && in_array($paymentStatus, ['pending', 'paid', 'reimbursed'])) {
            $query->where('payment_status', $paymentStatus);
        }
        
        if ($employeeId && is_numeric($employeeId)) {
            $query->where('employee_id', (int)$employeeId);
        }
        
        if ($requiresReceipt && in_array($requiresReceipt, ['0', '1'])) {
            $query->whereHas('category', function($q) use ($requiresReceipt) {
                $q->where('requires_receipt', (bool)$requiresReceipt);
            });
        }
        
        if ($isRecurring && in_array($isRecurring, ['0', '1'])) {
            $query->where('is_recurring', (bool)$isRecurring);
        }
        
        // Get all expenses for summary - Use Eloquent (automatically converted)
        $allExpenses = clone $query;
        $expensesCollection = $allExpenses->get();
        
        // Get summary statistics from ALL expenses (values already converted by accessors)
        $summary = [
            'total_expenses' => $expensesCollection->count(),
            'total_amount' => $expensesCollection->sum('total_amount'),
            'total_tax' => $expensesCollection->sum('tax_amount'),
            'avg_expense' => $expensesCollection->count() > 0 ? $expensesCollection->avg('total_amount') : 0,
            'max_expense' => $expensesCollection->max('total_amount') ?? 0,
            'min_expense' => $expensesCollection->min('total_amount') ?? 0,
        ];
        
        // Get daily breakdown using Eloquent (not raw DB) for proper conversion
        $dailyBreakdown = Expense::where('tenant_id', $tenantId)
            ->whereBetween('date', [$startDateTime->format('Y-m-d'), $endDateTime->format('Y-m-d')])
            ->when($categoryId, function($q) use ($categoryId) {
                return $q->where('category_id', $categoryId);
            })
            ->when($vendorName, function($q) use ($vendorName) {
                return $q->where(function($sq) use ($vendorName) {
                    $sq->where('vendor_name', 'like', '%' . trim($vendorName) . '%')
                        ->orWhereHas('supplier', function($ssq) use ($vendorName) {
                            $ssq->where('name', 'like', '%' . trim($vendorName) . '%');
                        });
                });
            })
            ->when($paymentMethodId, function($q) use ($paymentMethodId) {
                return $q->where('payment_method_id', $paymentMethodId);
            })
            ->when($paymentStatus, function($q) use ($paymentStatus) {
                return $q->where('payment_status', $paymentStatus);
            })
            ->when($employeeId, function($q) use ($employeeId) {
                return $q->where('employee_id', $employeeId);
            })
            ->when($isRecurring, function($q) use ($isRecurring) {
                return $q->where('is_recurring', $isRecurring);
            })
            ->get()
            ->groupBy(function($expense) {
                return $expense->date->format('Y-m-d');
            })
            ->map(function($expenses, $date) {
                $total = $expenses->sum('total_amount');
                $tax = $expenses->sum('tax_amount');
                $count = $expenses->count();
                
                return (object)[
                    'date' => $date,
                    'count' => $count,
                    'total' => $total,
                    'tax' => $tax,
                    'average' => $count > 0 ? $total / $count : 0,
                ];
            })
            ->sortByDesc('date')
            ->values();
        
        // Get top expenses - using Eloquent (automatically converted)
        $topExpenses = $query->orderBy('total_amount', 'desc')
            ->take(10)
            ->get();
        
        // Get filter options
        $categories = ExpenseCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'requires_receipt']);
        
        $paymentMethods = PaymentMethod::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'is_default', 'type']);
        
        $employees = Employee::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);
        
        // For display in the form
        $displayStartDate = $startDate;
        $displayEndDate = $endDate;
        
        return view('reports.expenses.summary', [
            'summary' => $summary,
            'dailyBreakdown' => $dailyBreakdown,
            'topExpenses' => $topExpenses,
            'categories' => $categories,
            'paymentMethods' => $paymentMethods,
            'employees' => $employees,
            'startDate' => $displayStartDate,
            'endDate' => $displayEndDate,
            'categoryId' => $categoryId,
            'vendorName' => $vendorName,
            'paymentMethodId' => $paymentMethodId,
            'paymentStatus' => $paymentStatus,
            'employeeId' => $employeeId,
            'requiresReceipt' => $requiresReceipt,
            'isRecurring' => $isRecurring,
        ]);
    }
    
    // Expenses by Category
    public function byCategory(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('expense reports')) {
            abort(403, __('payments.not_authorized'));
        }

        $startDate = $request->get('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $categoryId = $request->get('category_id');
        
        // Build query
        $query = Expense::with('category')
            ->where('tenant_id', $tenantId)
            ->whereBetween('date', [$startDate, $endDate]);
        
        // Apply category filter
        if ($categoryId && is_numeric($categoryId)) {
            $query->where('category_id', (int)$categoryId);
        }
        
        // Get expenses
        $expenses = $query->get();
        
        // Group by category for breakdown
        $categoryBreakdown = $expenses->groupBy('category_id')
            ->map(function($group) {
                $category = $group->first()->category;
                $totalAmount = $group->sum('total_amount');
                $totalTax = $group->sum('tax_amount');
                $grandTotal = $group->sum('total_amount');
                $count = $group->count();
                
                return (object)[
                    'category_name' => $category->name ?? 'Uncategorized',
                    'category_code' => $category->code ?? 'N/A',
                    'expense_count' => $count,
                    'total_amount' => $totalAmount,
                    'total_tax' => $totalTax,
                    'grand_total' => $grandTotal,
                    'average_amount' => $count > 0 ? $grandTotal / $count : 0,
                    'max_amount' => $group->max('total_amount'),
                    'min_amount' => $group->min('total_amount'),
                ];
            })
            ->sortByDesc('grand_total')
            ->values();
        
        $totalExpenses = $categoryBreakdown->sum('grand_total');
        
        // Monthly trend by category - Using Eloquent (FIXED)
        $monthlyData = Expense::with('category')
            ->where('tenant_id', $tenantId)
            ->whereBetween('date', [$startDate, $endDate])
            ->when($categoryId, function($q) use ($categoryId) {
                return $q->where('category_id', $categoryId);
            })
            ->get()
            ->groupBy(function($expense) {
                return $expense->date->format('Y-m');
            });
        
        $monthlyTrend = collect();
        foreach ($monthlyData as $month => $expensesInMonth) {
            $byCategory = $expensesInMonth->groupBy('category_id');
            foreach ($byCategory as $catId => $categoryExpenses) {
                $category = $categoryExpenses->first()->category;
                $monthlyTrend->push((object)[
                    'year' => (int)date('Y', strtotime($month)),
                    'month' => (int)date('n', strtotime($month)),
                    'category_name' => $category->name ?? 'Uncategorized',
                    'monthly_total' => $categoryExpenses->sum('total_amount'),
                ]);
            }
        }
        
        // Group by category name for the view
        $monthlyTrendGrouped = $monthlyTrend->groupBy('category_name');
        
        // Get unique months for chart
        $uniqueMonths = $monthlyTrend->groupBy(function($item) {
            return $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT);
        })->keys()->sort()->map(function($key) {
            return (object)[
                'year' => (int)substr($key, 0, 4),
                'month' => (int)substr($key, 5, 2),
                'label' => date('M Y', strtotime($key . '-01'))
            ];
        })->values();
        
        $categories = ExpenseCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        // For display in the form
        $displayStartDate = $startDate;
        $displayEndDate = $endDate;

        $monthlyDataMatrix = [];
        foreach ($monthlyTrendGrouped as $categoryName => $categoryData) {
            foreach ($categoryData as $item) {
                $monthKey = $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT);
                $monthlyDataMatrix[$monthKey][$categoryName] = $item->monthly_total;
            }
        }

        // Also prepare category totals
        $categoryTotals = [];
        foreach ($categoryBreakdown as $category) {
            $categoryTotals[$category->category_name] = $category->grand_total;
        }
        
        return view('reports.expenses.by-category', compact(
            'categoryBreakdown',
            'totalExpenses',
            'monthlyTrendGrouped',
            'uniqueMonths',
            'categories',
            'startDate',
            'endDate',
            'displayStartDate',
            'displayEndDate',
            'categoryId',
            'monthlyDataMatrix', 
            'categoryTotals'
        ));
    }
    
    // Expenses by Vendor
    public function byVendor(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('expense reports')) {
            abort(403, __('payments.not_authorized'));
        }
        
        if (!$tenantId) {
            return redirect()->back()->with('error', __('accounting.invalid_tenant'));
        }
        
        // Get filter parameters
        $startDate = $request->get('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $vendorName = $request->get('vendor_name');
        $categoryId = $request->get('category_id');
        $paymentMethodId = $request->get('payment_method_id');
        $minAmount = $request->get('min_amount');
        $maxAmount = $request->get('max_amount');
        
        // Validate dates
        try {
            $startDate = Carbon::parse($startDate)->format('Y-m-d');
            $endDate = Carbon::parse($endDate)->format('Y-m-d');
            
            if ($startDate > $endDate) {
                [$startDate, $endDate] = [$endDate, $startDate];
            }
        } catch (\Exception $e) {
            $startDate = Carbon::now()->startOfYear()->format('Y-m-d');
            $endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        }
        
        // Build query using Eloquent (automatic conversion via accessors)
        $query = Expense::with(['category', 'paymentMethod'])
            ->where('tenant_id', $tenantId)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereNotNull('vendor_name')
            ->where('vendor_name', '!=', '');
        
        // Apply filters
        if ($vendorName && strlen(trim($vendorName)) >= 2) {
            $query->where('vendor_name', 'like', '%' . trim($vendorName) . '%');
        }
        
        if ($categoryId && is_numeric($categoryId)) {
            $query->where('category_id', (int)$categoryId);
        }
        
        if ($paymentMethodId && is_numeric($paymentMethodId)) {
            $query->where('payment_method_id', (int)$paymentMethodId);
        }
        
        if ($minAmount && is_numeric($minAmount)) {
            $query->where('total_amount', '>=', (float)$minAmount);
        }
        
        if ($maxAmount && is_numeric($maxAmount)) {
            $query->where('total_amount', '<=', (float)$maxAmount);
        }
        
        // Get all expenses (values already converted by accessors)
        $allExpenses = $query->get();
        
        // Group by vendor and calculate statistics using collections
        $vendorBreakdown = $allExpenses->groupBy('vendor_name')
            ->map(function($expenses, $vendorName) {
                return (object)[
                    'vendor_name' => $vendorName,
                    'transaction_count' => $expenses->count(),
                    'total_amount' => $expenses->sum('total_amount'),
                    'total_tax' => $expenses->sum('tax_amount'),
                    'grand_total' => $expenses->sum('total_amount'),
                    'average_transaction' => $expenses->avg('total_amount'),
                    'largest_transaction' => $expenses->max('total_amount'),
                    'smallest_transaction' => $expenses->min('total_amount'),
                    'categories_used' => $expenses->pluck('category_id')->unique()->count(),
                ];
            })
            ->sortByDesc('grand_total')
            ->values();
        
        // Summary statistics
        $summary = [
            'total_vendors' => $vendorBreakdown->count(),
            'total_transactions' => $allExpenses->count(),
            'total_amount' => $allExpenses->sum('total_amount'),
            'total_tax' => $allExpenses->sum('tax_amount'),
            'avg_transaction' => $allExpenses->avg('total_amount'),
            'largest_single' => $allExpenses->max('total_amount'),
            'unique_categories' => ExpenseCategory::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->count(),
        ];
        
        // Vendor payment methods breakdown using collections
        $vendorPaymentMethods = $allExpenses->groupBy('vendor_name')
            ->map(function($expenses, $vendorName) {
                return $expenses->groupBy(function($expense) {
                    return $expense->paymentMethod->name ?? 'Unknown';
                })->map(function($groupedExpenses, $methodName) use ($vendorName) {
                    return (object)[
                        'vendor_name' => $vendorName,
                        'payment_method' => $methodName,
                        'payment_type' => $groupedExpenses->first()->paymentMethod->type ?? 'unknown',
                        'count' => $groupedExpenses->count(),
                        'total' => $groupedExpenses->sum('total_amount'),
                    ];
                })->values();
            })
            ->filter()
            ->flatMap(function($item) {
                return $item;
            });
        
        // Monthly vendor activity using collections
        $monthlyVendorActivity = $allExpenses->groupBy(function($expense) {
            return $expense->date->format('Y-m');
        })->flatMap(function($expensesInMonth, $monthKey) {
            list($year, $month) = explode('-', $monthKey);
            
            return $expensesInMonth->groupBy('vendor_name')
                ->map(function($vendorExpenses, $vendorName) use ($year, $month) {
                    return (object)[
                        'year' => (int)$year,
                        'month' => (int)$month,
                        'vendor_name' => $vendorName,
                        'transaction_count' => $vendorExpenses->count(),
                        'monthly_total' => $vendorExpenses->sum('total_amount'),
                        'monthly_average' => $vendorExpenses->avg('total_amount'),
                    ];
                })->values();
        })->sortByDesc(function($item) {
            return $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT);
        })->values();
        
        // Get filter options
        $uniqueVendors = $allExpenses->pluck('vendor_name')->unique()->sort()->values();
        
        $categories = ExpenseCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        
        $paymentMethods = PaymentMethod::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'is_default']);
        
        // For display in the form
        $displayStartDate = $startDate;
        $displayEndDate = $endDate;
        
        return view('reports.expenses.by-vendor', [
            'vendorBreakdown' => $vendorBreakdown,
            'vendorPaymentMethods' => $vendorPaymentMethods,
            'monthlyVendorActivity' => $monthlyVendorActivity,
            'summary' => $summary,
            'uniqueVendors' => $uniqueVendors,
            'categories' => $categories,
            'paymentMethods' => $paymentMethods,
            'startDate' => $displayStartDate,
            'endDate' => $displayEndDate,
            'vendorName' => $vendorName,
            'categoryId' => $categoryId,
            'paymentMethodId' => $paymentMethodId,
            'minAmount' => $minAmount,
            'maxAmount' => $maxAmount,
        ]);
    }
    
    // Employee Expenses Report
    public function byEmployee(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('expense reports')) {
            abort(403, __('payments.not_authorized'));
        }
        
        if (!$tenantId) {
            return redirect()->back()->with('error', __('accounting.invalid_tenant'));
        }
        
        // Get filter parameters
        $startDate = $request->get('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $employeeId = $request->get('employee_id');
        $requiresApproval = $request->get('requires_approval');
        
        // Validate dates
        try {
            $startDate = Carbon::parse($startDate)->format('Y-m-d');
            $endDate = Carbon::parse($endDate)->format('Y-m-d');
            
            if ($startDate > $endDate) {
                [$startDate, $endDate] = [$endDate, $startDate];
            }
        } catch (\Exception $e) {
            $startDate = Carbon::now()->startOfYear()->format('Y-m-d');
            $endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        }
        
        // Build main query using Eloquent (automatic conversion via accessors)
        $query = Expense::with(['employee', 'employee.department', 'category'])
            ->where('tenant_id', $tenantId)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereNotNull('employee_id');
        
        // Apply filters
        if ($employeeId && is_numeric($employeeId)) {
            $query->where('employee_id', (int)$employeeId);
        }
        
        if ($requiresApproval !== null && in_array($requiresApproval, ['0', '1'])) {
            $query->whereHas('category', function($q) use ($requiresApproval) {
                $q->where('requires_approval', (bool)$requiresApproval);
            });
        }
        
        // Get all expenses (values already converted by accessors)
        $allExpenses = $query->get();
        
        // Employee breakdown using collections
        $employeeBreakdown = $allExpenses->groupBy('employee_id')
            ->map(function($expenses, $empId) {
                $employee = $expenses->first()->employee;
                
                return (object)[
                    'employee_id' => $empId,
                    'first_name' => $employee->first_name ?? 'Unknown',
                    'last_name' => $employee->last_name ?? '',
                    'employee_name' => trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? '')),
                    'department' => $employee->department->name ?? 'No Department',
                    'expense_count' => $expenses->count(),
                    'total_amount' => $expenses->sum('total_amount'),
                    'total_tax' => $expenses->sum('tax_amount'),
                    'grand_total' => $expenses->sum('total_amount'),
                    'average_expense' => $expenses->avg('total_amount'),
                    'max_expense' => $expenses->max('total_amount'),
                    'pending_count' => $expenses->where('payment_status', 'pending')->count(),
                    'paid_count' => $expenses->where('payment_status', 'paid')->count(),
                    'reimbursed_count' => $expenses->where('payment_status', 'reimbursed')->count(),
                ];
            })
            ->sortByDesc('grand_total')
            ->values();
        
        // Monthly spending by employee using collections
        $monthlySpendingRaw = $allExpenses->groupBy(function($expense) {
            return $expense->date->format('Y-m');
        })->flatMap(function($expensesInMonth, $monthKey) {
            list($year, $month) = explode('-', $monthKey);
            
            return $expensesInMonth->groupBy('employee_id')
                ->map(function($empExpenses, $empId) use ($year, $month) {
                    $employee = $empExpenses->first()->employee;
                    
                    return (object)[
                        'year' => (int)$year,
                        'month' => (int)$month,
                        'employee_name' => trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? '')),
                        'department' => $employee->department->name ?? 'No Department',
                        'transaction_count' => $empExpenses->count(),
                        'monthly_total' => $empExpenses->sum('total_amount'),
                    ];
                })->values();
        });
        
        // Group monthly spending by employee name
        $monthlySpending = $monthlySpendingRaw->groupBy('employee_name');
        
        // Get all monthly data for chart (flattened)
        $allMonthlyData = $monthlySpendingRaw->values();
        
        // Categories by employee using collections
        $employeeCategoriesRaw = $allExpenses->groupBy('employee_id')
            ->flatMap(function($empExpenses, $empId) {
                $employee = $empExpenses->first()->employee;
                $employeeName = trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? ''));
                
                return $empExpenses->groupBy('category_id')
                    ->map(function($catExpenses, $catId) use ($employeeName) {
                        $category = $catExpenses->first()->category;
                        
                        return (object)[
                            'employee_name' => $employeeName,
                            'category_name' => $category->name ?? 'Uncategorized',
                            'count' => $catExpenses->count(),
                            'total' => $catExpenses->sum('total_amount'),
                        ];
                    })->values();
            });
        
        // Group categories by employee name
        $employeeCategories = $employeeCategoriesRaw->groupBy('employee_name');
        
        // Get filter options - FIXED: Don't select 'employee_id' column
        $employees = Employee::with('department')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']); // Removed 'employee_id' - not a column
        
        // For display in the form
        $displayStartDate = $startDate;
        $displayEndDate = $endDate;
        
        // Summary statistics
        $summary = [
            'total_employees' => $employeeBreakdown->count(),
            'total_expenses' => $allExpenses->count(),
            'total_amount' => $allExpenses->sum('total_amount'),
            'total_tax' => $allExpenses->sum('tax_amount'),
            'avg_per_employee' => $allExpenses->avg('total_amount'),
            'largest_expense' => $allExpenses->max('total_amount'),
        ];
        
        return view('reports.expenses.by-employee', [
            'employeeBreakdown' => $employeeBreakdown,
            'monthlySpending' => $monthlySpending,
            'allMonthlyData' => $allMonthlyData,
            'employeeCategories' => $employeeCategories,
            'employees' => $employees,
            'summary' => $summary,
            'startDate' => $displayStartDate,
            'endDate' => $displayEndDate,
            'employeeId' => $employeeId,
            'requiresApproval' => $requiresApproval,
        ]);
    }
            
    // Budget vs Actual Report
    public function budgetVsActual(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('expense reports')) {
            abort(403, __('payments.not_authorized'));
        }

        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('m'));
        
        // Get budgeted categories
        $budgetedCategories = ExpenseCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where(function($q) {
                $q->whereNotNull('budget_monthly')
                ->orWhereNotNull('budget_annual');
            })
            ->orderBy('name')
            ->get();
        
        $budgetData = [];
        $totalBudgetMonthly = 0;
        $totalBudgetAnnual = 0;
        $totalActualMonthly = 0;
        $totalActualAnnual = 0;
        
        foreach ($budgetedCategories as $category) {
            // Get actual expenses for the period using Eloquent
            $monthlyActual = Expense::where('tenant_id', $tenantId)
                ->where('category_id', $category->id)
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->sum('total_amount');
            
            $annualActual = Expense::where('tenant_id', $tenantId)
                ->where('category_id', $category->id)
                ->whereYear('date', $year)
                ->sum('total_amount');
            
            $budgetMonthly = $category->budget_monthly ?? 0;
            $budgetAnnual = $category->budget_annual ?? ($budgetMonthly * 12);
            
            $varianceMonthly = $budgetMonthly - $monthlyActual;
            $varianceAnnual = $budgetAnnual - $annualActual;
            
            $budgetData[] = [
                'category' => $category,
                'budget_monthly' => $budgetMonthly,
                'actual_monthly' => $monthlyActual,
                'variance_monthly' => $varianceMonthly,
                'variance_percentage_monthly' => $budgetMonthly > 0 ? ($varianceMonthly / $budgetMonthly) * 100 : 0,
                
                'budget_annual' => $budgetAnnual,
                'actual_annual' => $annualActual,
                'variance_annual' => $varianceAnnual,
                'variance_percentage_annual' => $budgetAnnual > 0 ? ($varianceAnnual / $budgetAnnual) * 100 : 0,
            ];
            
            $totalBudgetMonthly += $budgetMonthly;
            $totalBudgetAnnual += $budgetAnnual;
            $totalActualMonthly += $monthlyActual;
            $totalActualAnnual += $annualActual;
        }
        
        // Summary
        $summary = [
            'total_budget_monthly' => $totalBudgetMonthly,
            'total_actual_monthly' => $totalActualMonthly,
            'total_variance_monthly' => $totalBudgetMonthly - $totalActualMonthly,
            'variance_percentage_monthly' => $totalBudgetMonthly > 0 ? (($totalBudgetMonthly - $totalActualMonthly) / $totalBudgetMonthly) * 100 : 0,
            
            'total_budget_annual' => $totalBudgetAnnual,
            'total_actual_annual' => $totalActualAnnual,
            'total_variance_annual' => $totalBudgetAnnual - $totalActualAnnual,
            'variance_percentage_annual' => $totalBudgetAnnual > 0 ? (($totalBudgetAnnual - $totalActualAnnual) / $totalBudgetAnnual) * 100 : 0,
            
            'under_budget_count' => collect($budgetData)->where('variance_monthly', '>', 0)->count(),
            'over_budget_count' => collect($budgetData)->where('variance_monthly', '<', 0)->count(),
            'on_budget_count' => collect($budgetData)->where('variance_monthly', '==', 0)->count(),
        ];
        
        // Monthly trend for each category using Eloquent
        $monthlyTrends = [];
        foreach ($budgetedCategories as $category) {
            $monthlyData = Expense::where('tenant_id', $tenantId)
                ->where('category_id', $category->id)
                ->whereYear('date', $year)
                ->get()
                ->groupBy(function($expense) {
                    return $expense->date->month;
                })
                ->map(function($expenses) {
                    return $expenses->sum('total_amount');
                });
            
            $trend = [];
            for ($m = 1; $m <= 12; $m++) {
                $actual = $monthlyData[$m] ?? 0;
                $budget = $category->budget_monthly ?? 0;
                $trend[$m] = [
                    'month' => $m,
                    'budget' => $budget,
                    'actual' => $actual,
                    'variance' => $budget - $actual,
                    'variance_percentage' => $budget > 0 ? (($budget - $actual) / $budget) * 100 : 0,
                ];
            }
            
            $monthlyTrends[$category->id] = $trend;
        }
        
        $years = range(date('Y') - 5, date('Y') + 1);
        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];
        
        return view('reports.expenses.budget-vs-actual', compact(
            'budgetData',
            'summary',
            'monthlyTrends',
            'years',
            'months',
            'year',
            'month'
        ));
    }
    
    // Export functions for each report
    public function exportSummary(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('expense reports')) {
            abort(403, __('payments.not_authorized'));
        }

        $data = $this->summary($request);
        // Add export logic here
        return response()->streamDownload(function() use ($data) {
            echo "Expense Summary Report\n";
            echo "Generated: " . date('Y-m-d H:i:s') . "\n\n";
            // Export data in CSV format
        }, 'expense-summary-' . date('Y-m-d') . '.csv');
    }


    // Expenses by Payment Method
    public function byPaymentMethod(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('expense reports')) {
            abort(403, __('payments.not_authorized'));
        }
        
        $startDate = $request->get('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $paymentMethodId = $request->get('payment_method_id');
        
        // Build query using Eloquent
        $query = Expense::with(['paymentMethod', 'category'])
            ->where('tenant_id', $tenantId)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereNotNull('payment_method_id');
        
        if ($paymentMethodId) {
            $query->where('payment_method_id', $paymentMethodId);
        }
        
        // Get all expenses (values automatically converted by accessors)
        $allExpenses = $query->get();
        
        // Group by payment method for breakdown
        $methodBreakdown = $allExpenses->groupBy('payment_method_id')
            ->map(function($expenses, $methodId) {
                $paymentMethod = $expenses->first()->paymentMethod;
                
                return (object)[
                    'id' => $methodId,
                    'method_name' => $paymentMethod->name ?? 'Unknown',
                    'method_type' => $paymentMethod->type ?? 'unknown',
                    'is_active' => $paymentMethod->is_active ?? false,
                    'transaction_count' => $expenses->count(),
                    'total_amount' => $expenses->sum('total_amount'),
                    'total_tax' => $expenses->sum('tax_amount'),
                    'grand_total' => $expenses->sum('total_amount'),
                    'average_transaction' => $expenses->avg('total_amount'),
                    'max_transaction' => $expenses->max('total_amount'),
                    'min_transaction' => $expenses->min('total_amount'),
                    'categories_used' => $expenses->pluck('category_id')->unique()->count(),
                    'vendors_used' => $expenses->pluck('vendor_name')->unique()->count(),
                ];
            })
            ->sortByDesc('grand_total')
            ->values();
        
        // Monthly trend by payment method
        $monthlyTrendRaw = $allExpenses->groupBy(function($expense) {
            return $expense->date->format('Y-m');
        })->flatMap(function($expensesInMonth, $monthKey) {
            list($year, $month) = explode('-', $monthKey);
            
            return $expensesInMonth->groupBy('payment_method_id')
                ->map(function($methodExpenses, $methodId) use ($year, $month) {
                    $paymentMethod = $methodExpenses->first()->paymentMethod;
                    
                    return (object)[
                        'year' => (int)$year,
                        'month' => (int)$month,
                        'method_name' => $paymentMethod->name ?? 'Unknown',
                        'transaction_count' => $methodExpenses->count(),
                        'monthly_total' => $methodExpenses->sum('total_amount'),
                    ];
                })->values();
        });
        
        $monthlyTrend = $monthlyTrendRaw->groupBy('method_name');
        
        // Payment method by category
        $methodByCategoryRaw = $allExpenses->groupBy('payment_method_id')
            ->flatMap(function($expenses, $methodId) {
                $paymentMethod = $expenses->first()->paymentMethod;
                $methodName = $paymentMethod->name ?? 'Unknown';
                
                return $expenses->groupBy('category_id')
                    ->map(function($categoryExpenses, $categoryId) use ($methodName) {
                        $category = $categoryExpenses->first()->category;
                        
                        return (object)[
                            'method_name' => $methodName,
                            'category_name' => $category->name ?? 'Uncategorized',
                            'transaction_count' => $categoryExpenses->count(),
                            'total_amount' => $categoryExpenses->sum('total_amount'),
                        ];
                    })->values();
            });
        
        $methodByCategory = $methodByCategoryRaw->groupBy('method_name');
        
        // Get filter options
        $paymentMethods = PaymentMethod::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'is_default']);
        
        // Summary statistics
        $totalExpenses = $methodBreakdown->sum('grand_total');
        $summary = [
            'total_methods' => $methodBreakdown->count(),
            'total_transactions' => $methodBreakdown->sum('transaction_count'),
            'total_amount' => $totalExpenses,
            'avg_per_method' => $methodBreakdown->avg('grand_total'),
            'most_used_method' => $methodBreakdown->first()->method_name ?? 'N/A',
        ];
        
        $displayStartDate = $startDate;
        $displayEndDate = $endDate;
        
        return view('reports.expenses.by-payment-method', compact(
            'methodBreakdown',
            'monthlyTrend',
            'methodByCategory',
            'paymentMethods',
            'summary',
            'startDate',
            'endDate',
            'displayStartDate',
            'displayEndDate',
            'paymentMethodId'
        ));
    }
    
    // Recurring Expenses Report
    public function recurring(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('expense reports')) {
            abort(403, __('payments.not_authorized'));
        }
        
        $frequency = $request->get('frequency');
        $categoryId = $request->get('category_id');
        $status = $request->get('status', 'active');
        
        // Build query using Eloquent
        $query = Expense::with(['category', 'paymentMethod'])
            ->where('tenant_id', $tenantId)
            ->where('is_recurring', true);
        
        if ($frequency) {
            $query->where('recurring_frequency', $frequency);
        }
        
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }
        
        $today = Carbon::today();
        
        // Status filtering
        if ($status === 'active') {
            $query->where(function($q) use ($today) {
                $q->where('next_recurring_date', '>=', $today)
                ->orWhereNull('next_recurring_date');
            });
        } elseif ($status === 'upcoming') {
            $nextWeek = $today->copy()->addWeek();
            $query->whereBetween('next_recurring_date', [$today, $nextWeek]);
        } elseif ($status === 'overdue') {
            $query->where('next_recurring_date', '<', $today);
        }
        
        $recurringExpenses = $query->orderBy('next_recurring_date', 'asc')
            ->get()
            ->map(function($expense) {
                // Convert amounts (already converted by accessors, but ensure)
                $expense->total_amount = $expense->total_amount;
                $expense->tax_amount = $expense->tax_amount;
                $expense->gross_amount = $expense->gross_amount;
                return $expense;
            });
        
        // Group by frequency
        $byFrequency = $recurringExpenses->groupBy('recurring_frequency')->map(function($items, $freq) {
            return [
                'count' => $items->count(),
                'total_monthly' => $items->sum('total_amount'),
                'total_annual' => $items->sum(function($item) use ($freq) {
                    $multiplier = match($freq) {
                        'weekly' => 52,
                        'monthly' => 12,
                        'quarterly' => 4,
                        'annually' => 1,
                        default => 12
                    };
                    return $item->total_amount * $multiplier;
                })
            ];
        });
        
        // Upcoming expenses in next 30 days
        $upcomingNext30Days = $recurringExpenses->filter(function($expense) use ($today) {
            if (!$expense->next_recurring_date) return false;
            $nextDate = Carbon::parse($expense->next_recurring_date);
            return $nextDate->between($today, $today->copy()->addDays(30));
        })->sortBy('next_recurring_date');
        
        // Annual projection
        $annualProjection = $recurringExpenses->sum(function($expense) {
            $multiplier = match($expense->recurring_frequency) {
                'weekly' => 52,
                'monthly' => 12,
                'quarterly' => 4,
                'annually' => 1,
                default => 12
            };
            return $expense->total_amount * $multiplier;
        });
        
        // Monthly projection for the next 12 months
        $monthlyProjection = [];
        $currentMonth = Carbon::now()->startOfMonth();
        
        for ($i = 0; $i < 12; $i++) {
            $month = $currentMonth->copy()->addMonths($i);
            $monthKey = $month->format('Y-m');
            $monthName = $month->format('M Y');
            
            $monthlyTotal = $recurringExpenses->sum(function($expense) use ($month) {
                if (!$expense->next_recurring_date) return 0;
                
                $nextDate = Carbon::parse($expense->next_recurring_date);
                $frequency = $expense->recurring_frequency;
                
                switch ($frequency) {
                    case 'weekly':
                        $weeksInMonth = 4.33;
                        return $expense->total_amount * $weeksInMonth;
                        
                    case 'monthly':
                        return $expense->total_amount;
                        
                    case 'quarterly':
                        $monthsDiff = $nextDate->diffInMonths($month);
                        return ($monthsDiff % 3 === 0) ? $expense->total_amount : 0;
                        
                    case 'annually':
                        return ($nextDate->format('m') === $month->format('m')) ? $expense->total_amount : 0;
                        
                    default:
                        return 0;
                }
            });
            
            $monthlyProjection[] = [
                'month' => $monthName,
                'projected_total' => $monthlyTotal,
                'expense_count' => $recurringExpenses->count()
            ];
        }
        
        $categories = ExpenseCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
        
        $frequencies = [
            'weekly' => 'Weekly',
            'monthly' => 'Monthly', 
            'quarterly' => 'Quarterly',
            'annually' => 'Annually'
        ];
        
        // Summary statistics
        $summary = [
            'total_recurring' => $recurringExpenses->count(),
            'total_monthly_cost' => $recurringExpenses->sum('total_amount'),
            'total_annual_cost' => $annualProjection,
            'upcoming_30_days' => $upcomingNext30Days->count(),
            'avg_per_expense' => $recurringExpenses->avg('total_amount'),
        ];
        
        return view('reports.expenses.recurring', compact(
            'recurringExpenses',
            'byFrequency',
            'upcomingNext30Days',
            'annualProjection',
            'monthlyProjection',
            'categories',
            'frequencies',
            'summary',
            'frequency',
            'categoryId',
            'status'
        ));
    }
        
    // Expense Trends Report
    public function trends(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('expense reports')) {
            abort(403, __('payments.not_authorized'));
        }
        
        $period = $request->get('period', 'monthly'); // monthly, quarterly, yearly
        $year = $request->get('year', date('Y'));
        $categoryId = $request->get('category_id');
        
        // Initialize variables
        $trendData = [];
        $categoryTrends = collect();
        $movingAverages = [];
        $momGrowth = [];
        
        if ($period === 'monthly') {
            $startDate = Carbon::create($year, 1, 1)->startOfYear();
            $endDate = Carbon::create($year, 12, 31)->endOfYear();
            
            // Get all expenses for the year using Eloquent (converted via accessors)
            $expensesQuery = Expense::with('category')
                ->where('tenant_id', $tenantId)
                ->whereBetween('date', [$startDate, $endDate]);
            
            if ($categoryId) {
                $expensesQuery->where('category_id', $categoryId);
            }
            
            $expenses = $expensesQuery->get();
            
            // Group by month
            $monthlyExpenses = $expenses->groupBy(function($expense) {
                return $expense->date->month;
            });
            
            // Previous year comparison using Eloquent
            $prevYearStart = Carbon::create($year - 1, 1, 1)->startOfYear();
            $prevYearEnd = Carbon::create($year - 1, 12, 31)->endOfYear();
            
            $prevYearExpenses = Expense::where('tenant_id', $tenantId)
                ->whereBetween('date', [$prevYearStart, $prevYearEnd])
                ->when($categoryId, function($q) use ($categoryId) {
                    return $q->where('category_id', $categoryId);
                })
                ->get()
                ->groupBy(function($expense) {
                    return $expense->date->month;
                });
            
            // Build trend data for all months
            for ($month = 1; $month <= 12; $month++) {
                $currentMonthExpenses = $monthlyExpenses->get($month, collect());
                $previousMonthExpenses = $prevYearExpenses->get($month, collect());
                
                $currentYearTotal = $currentMonthExpenses->sum('total_amount');
                $previousYearTotal = $previousMonthExpenses->sum('total_amount') ?? 0;
                
                // Calculate growth
                $growth = 0;
                if ($previousYearTotal > 0) {
                    $growth = (($currentYearTotal - $previousYearTotal) / $previousYearTotal) * 100;
                }
                
                $trendData[$month] = [
                    'month' => $month,
                    'month_name' => Carbon::create()->month($month)->format('F'),
                    'current_year' => $currentYearTotal,
                    'previous_year' => $previousYearTotal,
                    'growth' => $growth,
                    'expense_count' => $currentMonthExpenses->count(),
                    'tax_total' => $currentMonthExpenses->sum('tax_amount'),
                ];
            }
            
            // Category trends (top categories by month)
            $categoryTrendsRaw = $expenses->groupBy('category_id')
                ->map(function($catExpenses, $catId) use ($expenses) {
                    $category = $catExpenses->first()->category;
                    $monthlyData = [];
                    for ($month = 1; $month <= 12; $month++) {
                        $monthlyData[$month] = $catExpenses->filter(function($e) use ($month) {
                            return $e->date->month == $month;
                        })->sum('total_amount');
                    }
                    return (object)[
                        'category_name' => $category->name ?? 'Uncategorized',
                        'monthly_data' => $monthlyData,
                        'total' => $catExpenses->sum('total_amount'),
                    ];
                })
                ->sortByDesc('total')
                ->take(5);
            
            // Moving averages (3-month rolling average)
            $monthlyTotals = collect($trendData)->pluck('current_year')->values()->toArray();
            
            for ($i = 2; $i < count($monthlyTotals); $i++) {
                $movingAverages[$i + 1] = (
                    ($monthlyTotals[$i - 2] ?? 0) + 
                    ($monthlyTotals[$i - 1] ?? 0) + 
                    ($monthlyTotals[$i] ?? 0)
                ) / 3;
            }
            
            // Month-over-month growth
            for ($i = 1; $i < count($monthlyTotals); $i++) {
                $prevMonthTotal = $monthlyTotals[$i - 1] ?? 0;
                $currentMonthTotal = $monthlyTotals[$i] ?? 0;
                
                if ($prevMonthTotal > 0) {
                    $momGrowth[$i + 1] = (($currentMonthTotal - $prevMonthTotal) / $prevMonthTotal) * 100;
                } else {
                    $momGrowth[$i + 1] = $currentMonthTotal > 0 ? 100 : 0;
                }
            }
            
        } elseif ($period === 'quarterly') {
            // Get all expenses for the year
            $expenses = Expense::where('tenant_id', $tenantId)
                ->whereYear('date', $year)
                ->when($categoryId, function($q) use ($categoryId) {
                    return $q->where('category_id', $categoryId);
                })
                ->get();
            
            for ($quarter = 1; $quarter <= 4; $quarter++) {
                $startMonth = ($quarter - 1) * 3 + 1;
                $endMonth = $startMonth + 2;
                
                $quarterExpenses = $expenses->filter(function($expense) use ($startMonth, $endMonth) {
                    $month = $expense->date->month;
                    return $month >= $startMonth && $month <= $endMonth;
                });
                
                $trendData[$quarter] = [
                    'quarter' => $quarter,
                    'quarter_name' => "Q{$quarter}",
                    'total' => $quarterExpenses->sum('total_amount'),
                    'start_month' => $startMonth,
                    'end_month' => $endMonth,
                    'expense_count' => $quarterExpenses->count(),
                ];
            }
            
        } elseif ($period === 'yearly') {
            // Yearly trend (last 5 years)
            $currentYear = date('Y');
            $years = range($currentYear - 4, $currentYear);
            
            foreach ($years as $yearItem) {
                $yearExpenses = Expense::where('tenant_id', $tenantId)
                    ->whereYear('date', $yearItem)
                    ->when($categoryId, function($q) use ($categoryId) {
                        return $q->where('category_id', $categoryId);
                    })
                    ->get();
                
                $yearTotal = $yearExpenses->sum('total_amount');
                $expenseCount = $yearExpenses->count();
                $average = $expenseCount > 0 ? ($yearTotal / $expenseCount) : 0;
                
                $trendData[$yearItem] = [
                    'year' => $yearItem,
                    'total' => $yearTotal,
                    'expense_count' => $expenseCount,
                    'average' => $average,
                    'tax_total' => $yearExpenses->sum('tax_amount'),
                ];
            }
        }
        
        $categories = ExpenseCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
        
        $years = range(date('Y') - 5, date('Y'));
        
        // For display
        $displayYear = $year;
        
        return view('reports.expenses.trends', compact(
            'trendData',
            'period',
            'year',
            'displayYear',
            'categoryId',
            'categories',
            'years',
            'categoryTrends',
            'movingAverages',
            'momGrowth'
        ));
    }
        
    // Tax Report
    public function taxReport(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('expense reports')) {
            abort(403, __('payments.not_authorized'));
        }
        
        $startDate = $request->get('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $categoryId = $request->get('category_id');
        $taxType = $request->get('tax_type'); // taxable, non-taxable, all
        
        // Build base query using Eloquent
        $baseQuery = Expense::where('tenant_id', $tenantId)
            ->whereBetween('date', [$startDate, $endDate])
            ->with(['category']);
        
        if ($categoryId) {
            $baseQuery->where('category_id', $categoryId);
        }
        
        if ($taxType === 'taxable') {
            $baseQuery->where('tax_amount', '>', 0);
        } elseif ($taxType === 'non-taxable') {
            $baseQuery->where('tax_amount', '=', 0);
        }
        
        // Get all expenses (values auto-converted via accessors)
        $allExpenses = $baseQuery->get();
        
        // Tax summary - CORRECTED calculations
        $taxableExpenses = $allExpenses->where('tax_amount', '>', 0);
        $nonTaxableExpenses = $allExpenses->where('tax_amount', '=', 0);
        $totalGross = $allExpenses->sum('gross_amount');
        $totalTax = $allExpenses->sum('tax_amount');
        $totalNet = $allExpenses->sum('net_amount');
        
        $taxSummary = [
            'total_expenses' => $allExpenses->count(),
            'total_gross' => $totalGross,                                    // Original amount before tax
            'total_tax' => $totalTax,                                        // Total tax (additive + withholding)
            'total_net' => $totalNet,                                        // Amount paid after tax
            'taxable_expenses' => $taxableExpenses->count(),
            'non_taxable_expenses' => $nonTaxableExpenses->count(),
            'avg_tax_rate' => $totalGross > 0 ? ($totalTax / $totalGross) * 100 : 0,
            'tax_percentage_of_gross' => $totalGross > 0 ? ($totalTax / $totalGross) * 100 : 0,
            'withholding_impact' => $totalGross > 0 ? (($totalGross - $totalNet) / $totalGross) * 100 : 0,
        ];
        
        // Tax by category - CORRECTED
        $taxByCategory = $allExpenses->groupBy('category_id')
            ->map(function($expenses, $catId) {
                $category = $expenses->first()->category;
                $taxableExpenses = $expenses->where('tax_amount', '>', 0);
                $grossAmount = $expenses->sum('gross_amount');
                $taxAmount = $expenses->sum('tax_amount');
                $netAmount = $expenses->sum('net_amount');
                
                return (object)[
                    'category_name' => $category->name ?? 'Uncategorized',
                    'expense_count' => $expenses->count(),
                    'gross_amount' => $grossAmount,      // Before tax
                    'tax_amount' => $taxAmount,           // Total tax
                    'net_amount' => $netAmount,           // After tax (what was paid)
                    'avg_tax_rate' => $grossAmount > 0 ? ($taxAmount / $grossAmount) * 100 : 0,
                    'taxable_count' => $taxableExpenses->count(),
                    'non_taxable_count' => $expenses->count() - $taxableExpenses->count(),
                ];
            })
            ->sortByDesc('tax_amount')
            ->values();
        
        // Monthly tax breakdown - CORRECTED
        $monthlyTax = $allExpenses->groupBy(function($expense) {
            return $expense->date->format('Y-m');
        })->map(function($expenses, $monthKey) {
            list($year, $month) = explode('-', $monthKey);
            $date = Carbon::createFromDate((int)$year, (int)$month, 1);
            $grossAmount = $expenses->sum('gross_amount');
            $taxAmount = $expenses->sum('tax_amount');
            $netAmount = $expenses->sum('net_amount');
            
            return (object)[
                'year' => (int)$year,
                'month' => (int)$month,
                'month_name' => $date->format('F Y'),
                'expense_count' => $expenses->count(),
                'gross_amount' => $grossAmount,
                'tax_amount' => $taxAmount,
                'net_amount' => $netAmount,
                'avg_tax_rate' => $grossAmount > 0 ? ($taxAmount / $grossAmount) * 100 : 0,
            ];
        })->sortByDesc(function($item) {
            return $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT);
        })->values();
        
        // Top tax expenses
        $topTaxExpenses = $allExpenses->where('tax_amount', '>', 0)
            ->sortByDesc('tax_amount')
            ->take(20)
            ->values()
            ->map(function($expense) {
                $expense->tax_rate = $expense->gross_amount > 0 
                    ? ($expense->tax_amount / $expense->gross_amount) * 100 
                    : 0;
                $expense->withholding_impact = $expense->gross_amount > 0 
                    ? (($expense->gross_amount - $expense->net_amount) / $expense->gross_amount) * 100 
                    : 0;
                return $expense;
            });
        
        // Tax rate distribution
        $taxRateDistribution = $allExpenses->where('tax_amount', '>', 0)
            ->where('gross_amount', '>', 0)
            ->groupBy(function($expense) {
                $rate = round(($expense->tax_amount / $expense->gross_amount) * 100, 0);
                return $rate;
            })
            ->map(function($expenses, $rate) {
                $totalTax = $expenses->sum('tax_amount');
                
                return (object)[
                    'tax_rate_percent' => (int)$rate,
                    'expense_count' => $expenses->count(),
                    'gross_amount' => $expenses->sum('gross_amount'),
                    'total_tax' => $totalTax,
                    'percentage' => $expenses->sum('tax_amount') > 0 ? ($totalTax / $expenses->sum('tax_amount')) * 100 : 0,
                ];
            })
            ->sortKeys()
            ->values();
        
        // Get filter options
        $categories = ExpenseCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
        
        $displayStartDate = $startDate;
        $displayEndDate = $endDate;
        
        return view('reports.expenses.tax-report', compact(
            'taxSummary',
            'taxByCategory',
            'monthlyTax',
            'topTaxExpenses',
            'taxRateDistribution',
            'categories',
            'startDate',
            'endDate',
            'displayStartDate',
            'displayEndDate',
            'categoryId',
            'taxType'
        ));
    }
    
    // Expense Audit Report (Fixed with Eloquent)
    public function audit(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('expense reports')) {
            abort(403, __('payments.not_authorized'));
        }
        
        $startDate = $request->get('start_date', Carbon::now()->subMonths(3)->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $auditType = $request->get('audit_type', 'all');
        $employeeId = $request->get('employee_id');
        $threshold = $request->get('threshold', 1000);
        
        // Build query using Eloquent
        $query = Expense::with(['category', 'paymentMethod', 'employee', 'approver'])
            ->where('tenant_id', $tenantId)
            ->whereBetween('date', [$startDate, $endDate]);
        
        // Apply audit type filters
        switch ($auditType) {
            case 'missing_receipts':
                $query->whereHas('category', function($q) {
                    $q->where('requires_receipt', true);
                })->where(function($q) {
                    $q->whereNull('receipt_url')
                    ->orWhere('receipt_url', '');
                });
                break;
                
            case 'unapproved':
                $query->whereHas('category', function($q) {
                    $q->where('requires_approval', true);
                })->whereNull('approved_at');
                break;
                
            case 'high_value':
                $query->where('total_amount', '>=', $threshold);
                break;
                
            case 'late_submissions':
                $query->whereRaw('DATEDIFF(created_at, date) > 7');
                break;
                
            case 'policy_violations':
                $query->whereHas('category', function($q) {
                    $q->where('requires_receipt', true);
                })->where(function($q) {
                    $q->whereNull('receipt_url')
                    ->orWhere('receipt_url', '');
                });
                break;
        }
        
        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }
        
        $auditItems = $query->orderBy('date', 'desc')
            ->get()
            ->map(function($item) {
                // Ensure amounts are converted (accessors handle this)
                $item->total_amount = $item->total_amount;
                return $item;
            });
        
        // Audit statistics using collections
        $auditStats = [
            'total_items' => $auditItems->count(),
            'total_amount' => $auditItems->sum('total_amount'),
            'missing_receipts' => $auditItems->filter(function($item) {
                $requiresReceipt = $item->category && $item->category->requires_receipt;
                return $requiresReceipt && empty($item->receipt_url);
            })->count(),
            'unapproved' => $auditItems->filter(function($item) {
                $requiresApproval = $item->category && $item->category->requires_approval;
                return $requiresApproval && !$item->approved_at;
            })->count(),
            'high_value' => $auditItems->filter(function($item) use ($threshold) {
                return $item->total_amount >= $threshold;
            })->count(),
            'average_age_days' => $auditItems->avg(function($item) {
                return Carbon::parse($item->created_at)->diffInDays(Carbon::today());
            }) ?? 0,
        ];
        
        // Group by category for analysis using collections
        $byCategory = $auditItems->groupBy(function($item) {
            return $item->category ? $item->category->name : 'Uncategorized';
        })->map(function($items, $category) {
            return [
                'category' => $category,
                'count' => $items->count(),
                'total_amount' => $items->sum('total_amount'),
                'missing_receipts' => $items->filter(function($item) {
                    return $item->category && $item->category->requires_receipt && empty($item->receipt_url);
                })->count(),
                'unapproved' => $items->filter(function($item) {
                    return $item->category && $item->category->requires_approval && !$item->approved_at;
                })->count(),
                'avg_amount' => $items->avg('total_amount')
            ];
        })->sortByDesc('count')->values();
        
        // Monthly audit trend using Eloquent (no DB::raw)
        $monthlyAuditRaw = Expense::where('tenant_id', $tenantId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy(function($expense) {
                return $expense->date->format('Y-m');
            })
            ->map(function($expenses, $monthKey) {
                list($year, $month) = explode('-', $monthKey);
                
                $missingReceipts = $expenses->filter(function($item) {
                    return $item->category && $item->category->requires_receipt && empty($item->receipt_url);
                })->count();
                
                $unapproved = $expenses->filter(function($item) {
                    return $item->category && $item->category->requires_approval && !$item->approved_at;
                })->count();
                
                return (object)[
                    'year' => (int)$year,
                    'month' => (int)$month,
                    'total_expenses' => $expenses->count(),
                    'missing_receipts' => $missingReceipts,
                    'unapproved' => $unapproved,
                    'monthly_total' => $expenses->sum('total_amount'),
                ];
            });
        
        $monthlyAudit = $monthlyAuditRaw->sortKeysDesc()->values();
        
        // Employee compliance using Eloquent
        $employeeComplianceRaw = Expense::where('expenses.tenant_id', $tenantId)
            ->whereBetween('expenses.date', [$startDate, $endDate])
            ->whereNotNull('expenses.employee_id')
            ->with(['employee', 'category'])
            ->get()
            ->groupBy('employee_id')
            ->map(function($expenses, $empId) {
                $employee = $expenses->first()->employee;
                
                return (object)[
                    'id' => $empId,
                    'first_name' => $employee->first_name ?? '',
                    'last_name' => $employee->last_name ?? '',
                    'email' => $employee->email ?? '',
                    'employee_name' => trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? '')),
                    'total_expenses' => $expenses->count(),
                    'missing_receipts' => $expenses->filter(function($item) {
                        $requiresReceipt = $item->category && $item->category->requires_receipt;
                        return $requiresReceipt && empty($item->receipt_url);
                    })->count(),
                    'unapproved' => $expenses->filter(function($item) {
                        $requiresApproval = $item->category && $item->category->requires_approval;
                        return $requiresApproval && !$item->approved_at;
                    })->count(),
                    'avg_expense' => $expenses->avg('total_amount'),
                ];
            });
        
        $employeeCompliance = $employeeComplianceRaw->sortByDesc('missing_receipts')->values();
        
        // Get employees for filter
        $employees = Employee::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);
        
        // Audit types for dropdown
        $auditTypes = [
            'all' => __('accounting.all_items'),
            'missing_receipts' => __('accounting.missing_receipts'),
            'unapproved' => __('accounting.unapproved_expenses'),
            'high_value' => __('accounting.high_value_expenses'),
            'late_submissions' => __('accounting.late_submissions'),
            'policy_violations' => __('accounting.policy_violations')
        ];
        
        $displayStartDate = $startDate;
        $displayEndDate = $endDate;
        
        return view('reports.expenses.audit', compact(
            'auditItems',
            'auditStats',
            'byCategory',
            'monthlyAudit',
            'employeeCompliance',
            'employees',
            'startDate',
            'endDate',
            'displayStartDate',
            'displayEndDate',
            'auditType',
            'employeeId',
            'auditTypes',
            'threshold'
        ));
    }
        
    // Export functions for each report
    public function exportByCategory(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('expense reports')) {
            abort(403, __('payments.not_authorized'));
        }
        $data = $this->byCategory($request);
        // Export logic here
        return response()->streamDownload(function() use ($data) {
            // CSV export implementation
        }, 'expense-by-category-' . date('Y-m-d') . '.csv');
    }

}
