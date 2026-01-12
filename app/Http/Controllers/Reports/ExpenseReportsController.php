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
        
        // Validate dates
        try {
            $startDate = Carbon::parse($startDate)->format('Y-m-d');
            $endDate = Carbon::parse($endDate)->format('Y-m-d');
            
            if ($startDate > $endDate) {
                [$startDate, $endDate] = [$endDate, $startDate];
            }
        } catch (\Exception $e) {
            $startDate = now()->startOfMonth()->format('Y-m-d');
            $endDate = now()->endOfMonth()->format('Y-m-d');
        }
        
        // Build main query
        $query = Expense::with(['category', 'paymentMethod', 'employee'])
            ->where('tenant_id', $tenantId)
            ->whereBetween('date', [$startDate, $endDate]);
        
        // Apply filters
        if ($categoryId && is_numeric($categoryId)) {
            $query->where('category_id', (int)$categoryId);
        }
        
        if ($vendorName && strlen(trim($vendorName)) >= 2) {
            $query->where('vendor_name', 'like', '%' . trim($vendorName) . '%');
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
        
        // Get summary statistics
        $summary = [
            'total_expenses' => $query->count(),
            'total_amount' => (float)($query->sum('total_amount') ?? 0),
            'total_tax' => (float)($query->sum('tax_amount') ?? 0),
            'avg_expense' => (float)($query->avg('total_amount') ?? 0),
            'max_expense' => (float)($query->max('total_amount') ?? 0),
            'min_expense' => (float)($query->min('total_amount') ?? 0),
        ];
        
        // Get daily breakdown
        $dailyBreakdown = DB::table('expenses')
            ->select(
                DB::raw('DATE(date) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total_amount) as total'),
                DB::raw('SUM(tax_amount) as tax'),
                DB::raw('AVG(total_amount) as average')
            )
            ->where('tenant_id', $tenantId)
            ->whereBetween('date', [$startDate, $endDate])
            ->when($categoryId, function($q) use ($categoryId) {
                return $q->where('category_id', $categoryId);
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
            ->groupBy(DB::raw('DATE(date)'))
            ->orderBy('date', 'desc')
            ->get();
        
        // Get top expenses - use the same query but with order by amount
        $topExpenses = $query->orderBy('total_amount', 'desc')->take(10)->get();
        
        // Get filter options
        $categories = ExpenseCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        $paymentMethods = PaymentMethod::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        $employees = Employee::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get();
        
        return view('reports.expenses.summary', [
            'summary' => $summary,
            'dailyBreakdown' => $dailyBreakdown,
            'topExpenses' => $topExpenses,
            'categories' => $categories,
            'paymentMethods' => $paymentMethods,
            'employees' => $employees,
            'startDate' => $startDate,
            'endDate' => $endDate,
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
        $tenantId = auth()->user()->tenant_id;
        
        $startDate = $request->get('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        
        $categoryBreakdown = Expense::where('expenses.tenant_id', $tenantId)
            ->whereBetween('expenses.date', [$startDate, $endDate])
            ->join('expense_categories', 'expenses.category_id', '=', 'expense_categories.id')
            ->select(
                'expense_categories.name as category_name',
                'expense_categories.code as category_code',
                DB::raw('COUNT(*) as expense_count'),
                DB::raw('SUM(expenses.amount) as total_amount'),
                DB::raw('SUM(expenses.tax_amount) as total_tax'),
                DB::raw('SUM(expenses.total_amount) as grand_total'),
                DB::raw('AVG(expenses.total_amount) as average_amount'),
                DB::raw('MAX(expenses.total_amount) as max_amount'),
                DB::raw('MIN(expenses.total_amount) as min_amount')
            )
            ->groupBy('expense_categories.id', 'expense_categories.name', 'expense_categories.code')
            ->orderBy('grand_total', 'desc')
            ->get();
        
        $totalExpenses = $categoryBreakdown->sum('grand_total');
        
        // Monthly trend by category
        $monthlyTrend = Expense::where('expenses.tenant_id', $tenantId)
            ->whereBetween('expenses.date', [$startDate, $endDate])
            ->join('expense_categories', 'expenses.category_id', '=', 'expense_categories.id')
            ->select(
                DB::raw('YEAR(expenses.date) as year'),
                DB::raw('MONTH(expenses.date) as month'),
                'expense_categories.name as category_name',
                DB::raw('SUM(expenses.total_amount) as monthly_total')
            )
            ->groupBy('year', 'month', 'expense_categories.name')
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->groupBy('category_name');
        
        $categories = ExpenseCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        return view('reports.expenses.by-category', compact(
            'categoryBreakdown',
            'totalExpenses',
            'monthlyTrend',
            'categories',
            'startDate',
            'endDate'
        ));
    }
    
    // Expenses by Vendor
    public function byVendor(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;
        
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
        
        // Build main query
        $query = Expense::where('expenses.tenant_id', $tenantId)
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
        
        // Get vendor breakdown
        $vendorBreakdown = $query->select(
                'vendor_name',
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('SUM(tax_amount) as total_tax'),
                DB::raw('SUM(total_amount) as grand_total'),
                DB::raw('AVG(total_amount) as average_transaction'),
                DB::raw('MAX(total_amount) as largest_transaction'),
                DB::raw('MIN(total_amount) as smallest_transaction'),
                DB::raw('COUNT(DISTINCT category_id) as categories_used')
            )
            ->groupBy('vendor_name')
            ->orderBy('grand_total', 'desc')
            ->get();
        
        // Summary statistics
        $summary = [
            'total_vendors' => $vendorBreakdown->count(),
            'total_transactions' => $vendorBreakdown->sum('transaction_count'),
            'total_amount' => (float)($vendorBreakdown->sum('grand_total') ?? 0),
            'total_tax' => (float)($vendorBreakdown->sum('total_tax') ?? 0),
            'avg_transaction' => (float)($vendorBreakdown->avg('average_transaction') ?? 0),
            'largest_single' => (float)($vendorBreakdown->max('largest_transaction') ?? 0),
            'unique_categories' => ExpenseCategory::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->count(),
        ];
        
        // Vendor payment methods - FIXED: Specify table for tenant_id
        $vendorPaymentMethods = Expense::join('payment_methods', 'expenses.payment_method_id', '=', 'payment_methods.id')
            ->where('expenses.tenant_id', $tenantId) // Specify expenses.tenant_id
            ->whereBetween('expenses.date', [$startDate, $endDate])
            ->whereNotNull('expenses.vendor_name')
            ->where('expenses.vendor_name', '!=', '')
            ->when($vendorName && strlen(trim($vendorName)) >= 2, function($q) use ($vendorName) {
                return $q->where('expenses.vendor_name', 'like', '%' . trim($vendorName) . '%');
            })
            ->when($categoryId && is_numeric($categoryId), function($q) use ($categoryId) {
                return $q->where('expenses.category_id', (int)$categoryId);
            })
            ->when($paymentMethodId && is_numeric($paymentMethodId), function($q) use ($paymentMethodId) {
                return $q->where('expenses.payment_method_id', (int)$paymentMethodId);
            })
            ->when($minAmount && is_numeric($minAmount), function($q) use ($minAmount) {
                return $q->where('expenses.total_amount', '>=', (float)$minAmount);
            })
            ->when($maxAmount && is_numeric($maxAmount), function($q) use ($maxAmount) {
                return $q->where('expenses.total_amount', '<=', (float)$maxAmount);
            })
            ->select(
                'expenses.vendor_name',
                'payment_methods.name as payment_method',
                'payment_methods.type as payment_type',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(expenses.total_amount) as total')
            )
            ->groupBy('expenses.vendor_name', 'payment_methods.name', 'payment_methods.type')
            ->orderBy('total', 'desc')
            ->get()
            ->groupBy('vendor_name');
        
        // Monthly vendor activity - FIXED: Specify table for tenant_id
        $monthlyVendorActivity = Expense::where('expenses.tenant_id', $tenantId)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereNotNull('vendor_name')
            ->where('vendor_name', '!=', '')
            ->when($vendorName && strlen(trim($vendorName)) >= 2, function($q) use ($vendorName) {
                return $q->where('vendor_name', 'like', '%' . trim($vendorName) . '%');
            })
            ->when($categoryId && is_numeric($categoryId), function($q) use ($categoryId) {
                return $q->where('category_id', (int)$categoryId);
            })
            ->when($paymentMethodId && is_numeric($paymentMethodId), function($q) use ($paymentMethodId) {
                return $q->where('payment_method_id', (int)$paymentMethodId);
            })
            ->when($minAmount && is_numeric($minAmount), function($q) use ($minAmount) {
                return $q->where('total_amount', '>=', (float)$minAmount);
            })
            ->when($maxAmount && is_numeric($maxAmount), function($q) use ($maxAmount) {
                return $q->where('total_amount', '<=', (float)$maxAmount);
            })
            ->select(
                DB::raw('YEAR(date) as year'),
                DB::raw('MONTH(date) as month'),
                'vendor_name',
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(total_amount) as monthly_total'),
                DB::raw('AVG(total_amount) as monthly_average')
            )
            ->groupBy('year', 'month', 'vendor_name')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->orderBy('monthly_total', 'desc')
            ->get();
        
        // Get filter options
        $uniqueVendors = Expense::where('tenant_id', $tenantId)
            ->whereNotNull('vendor_name')
            ->where('vendor_name', '!=', '')
            ->distinct('vendor_name')
            ->orderBy('vendor_name')
            ->pluck('vendor_name');
        
        $categories = ExpenseCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        $paymentMethods = PaymentMethod::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        return view('reports.expenses.by-vendor', [
            'vendorBreakdown' => $vendorBreakdown,
            'vendorPaymentMethods' => $vendorPaymentMethods,
            'monthlyVendorActivity' => $monthlyVendorActivity,
            'summary' => $summary,
            'uniqueVendors' => $uniqueVendors,
            'categories' => $categories,
            'paymentMethods' => $paymentMethods,
            'startDate' => $startDate,
            'endDate' => $endDate,
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
        
        // Build main query with departments join
        $query = Expense::where('expenses.tenant_id', $tenantId)
            ->whereBetween('expenses.date', [$startDate, $endDate])
            ->join('employees', 'expenses.employee_id', '=', 'employees.id')
            ->leftJoin('departments', 'employees.department_id', '=', 'departments.id') // Added left join
            ->whereNotNull('employee_id');
        
        // Apply filters
        if ($employeeId && is_numeric($employeeId)) {
            $query->where('expenses.employee_id', (int)$employeeId);
        }
        
        if ($requiresApproval !== null && in_array($requiresApproval, ['0', '1'])) {
            $query->whereHas('category', function($q) use ($requiresApproval) {
                $q->where('requires_approval', (bool)$requiresApproval);
            });
        }
        
        // Get employee breakdown with department name
        $employeeBreakdown = $query->select(
                'employees.first_name',
                'employees.last_name',
                'employees.id as employee_id',
                'departments.name as department', // Get department name from departments table
                DB::raw('CONCAT(employees.first_name, " ", employees.last_name) as employee_name'),
                DB::raw('COUNT(*) as expense_count'),
                DB::raw('SUM(expenses.amount) as total_amount'),
                DB::raw('SUM(expenses.tax_amount) as total_tax'),
                DB::raw('SUM(expenses.total_amount) as grand_total'),
                DB::raw('AVG(expenses.total_amount) as average_expense'),
                DB::raw('MAX(expenses.total_amount) as max_expense'),
                DB::raw('COUNT(CASE WHEN expenses.payment_status = "pending" THEN 1 END) as pending_count'),
                DB::raw('COUNT(CASE WHEN expenses.payment_status = "paid" THEN 1 END) as paid_count'),
                DB::raw('COUNT(CASE WHEN expenses.payment_status = "reimbursed" THEN 1 END) as reimbursed_count')
            )
            ->groupBy(
                'employees.id',
                'employees.first_name',
                'employees.last_name',
                'departments.name' // Group by department name instead of department column
            )
            ->orderBy('grand_total', 'desc')
            ->get();
        
        // Employee monthly spending with departments join
        $monthlySpending = Expense::where('expenses.tenant_id', $tenantId)
            ->whereBetween('expenses.date', [$startDate, $endDate])
            ->join('employees', 'expenses.employee_id', '=', 'employees.id')
            ->leftJoin('departments', 'employees.department_id', '=', 'departments.id') // Added left join
            ->whereNotNull('employee_id')
            ->when($employeeId && is_numeric($employeeId), function($q) use ($employeeId) {
                return $q->where('expenses.employee_id', (int)$employeeId);
            })
            ->when($requiresApproval !== null && in_array($requiresApproval, ['0', '1']), function($q) use ($requiresApproval) {
                return $q->whereHas('category', function($q2) use ($requiresApproval) {
                    $q2->where('requires_approval', (bool)$requiresApproval);
                });
            })
            ->select(
                DB::raw('YEAR(expenses.date) as year'),
                DB::raw('MONTH(expenses.date) as month'),
                'employees.first_name',
                'employees.last_name',
                'departments.name as department', // Added department name
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(expenses.total_amount) as monthly_total')
            )
            ->groupBy('year', 'month', 'employees.id', 'employees.first_name', 'employees.last_name', 'departments.name')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get()
            ->groupBy(function($item) {
                return $item->first_name . ' ' . $item->last_name;
            });
        
        // Get all monthly data for chart (flatten the grouped collection)
        $allMonthlyData = [];
        foreach($monthlySpending as $employeeName => $months) {
            foreach($months as $month) {
                $allMonthlyData[] = $month;
            }
        }
        
        // Categories by employee
        $employeeCategories = Expense::where('expenses.tenant_id', $tenantId)
            ->whereBetween('expenses.date', [$startDate, $endDate])
            ->join('employees', 'expenses.employee_id', '=', 'employees.id')
            ->join('expense_categories', 'expenses.category_id', '=', 'expense_categories.id')
            ->whereNotNull('employee_id')
            ->when($employeeId && is_numeric($employeeId), function($q) use ($employeeId) {
                return $q->where('expenses.employee_id', (int)$employeeId);
            })
            ->when($requiresApproval !== null && in_array($requiresApproval, ['0', '1']), function($q) use ($requiresApproval) {
                return $q->whereHas('category', function($q2) use ($requiresApproval) {
                    $q2->where('requires_approval', (bool)$requiresApproval);
                });
            })
            ->select(
                'employees.first_name',
                'employees.last_name',
                'expense_categories.name as category_name',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(expenses.total_amount) as total')
            )
            ->groupBy('employees.id', 'employees.first_name', 'employees.last_name', 'expense_categories.name')
            ->orderBy('total', 'desc')
            ->get()
            ->groupBy(function($item) {
                return $item->first_name . ' ' . $item->last_name;
            });
        
        // Get filter options
        $employees = Employee::with('department') // Eager load department relationship
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get();
        
        return view('reports.expenses.by-employee', [
            'employeeBreakdown' => $employeeBreakdown,
            'monthlySpending' => $monthlySpending,
            'allMonthlyData' => $allMonthlyData,
            'employeeCategories' => $employeeCategories,
            'employees' => $employees,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'employeeId' => $employeeId,
            'requiresApproval' => $requiresApproval,
        ]);
    }
        
    // Budget vs Actual Report
    public function budgetVsActual(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('m'));
        
        // Get budgeted categories
        $budgetedCategories = ExpenseCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereNotNull('budget_monthly')
            ->orWhereNotNull('budget_annual')
            ->orderBy('name')
            ->get();
        
        $budgetData = [];
        $totalBudgetMonthly = 0;
        $totalBudgetAnnual = 0;
        $totalActualMonthly = 0;
        $totalActualAnnual = 0;
        
        foreach ($budgetedCategories as $category) {
            // Get actual expenses for the period
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
            
            'total_budget_annual' => $totalBudgetAnnual,
            'total_actual_annual' => $totalActualAnnual,
            'total_variance_annual' => $totalBudgetAnnual - $totalActualAnnual,
            
            'under_budget_count' => collect($budgetData)->where('variance_monthly', '>', 0)->count(),
            'over_budget_count' => collect($budgetData)->where('variance_monthly', '<', 0)->count(),
            'on_budget_count' => collect($budgetData)->where('variance_monthly', '==', 0)->count(),
        ];
        
        // Monthly trend for each category
        $monthlyTrends = [];
        foreach ($budgetedCategories as $category) {
            $monthlyData = Expense::where('tenant_id', $tenantId)
                ->where('category_id', $category->id)
                ->whereYear('date', $year)
                ->select(
                    DB::raw('MONTH(date) as month'),
                    DB::raw('SUM(total_amount) as actual')
                )
                ->groupBy(DB::raw('MONTH(date)'))
                ->orderBy('month')
                ->get()
                ->keyBy('month');
            
            $trend = [];
            for ($m = 1; $m <= 12; $m++) {
                $trend[$m] = [
                    'month' => $m,
                    'budget' => $category->budget_monthly ?? 0,
                    'actual' => $monthlyData[$m]->actual ?? 0,
                    'variance' => ($category->budget_monthly ?? 0) - ($monthlyData[$m]->actual ?? 0)
                ];
            }
            
            $monthlyTrends[$category->id] = $trend;
        }
        
        $years = range(date('Y') - 5, date('Y'));
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
        $tenantId = auth()->user()->tenant_id;
        
        $startDate = $request->get('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $paymentMethodId = $request->get('payment_method_id');
        
        $query = Expense::where('expenses.tenant_id', $tenantId)
            ->whereBetween('expenses.date', [$startDate, $endDate])
            ->join('payment_methods', 'expenses.payment_method_id', '=', 'payment_methods.id');
        
        if ($paymentMethodId) {
            $query->where('payment_methods.id', $paymentMethodId);
        }
        
        $methodBreakdown = $query->select(
                'payment_methods.id',
                'payment_methods.name as method_name',
                'payment_methods.type as method_type',
                'payment_methods.is_active',
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(expenses.amount) as total_amount'),
                DB::raw('SUM(expenses.tax_amount) as total_tax'),
                DB::raw('SUM(expenses.total_amount) as grand_total'),
                DB::raw('AVG(expenses.total_amount) as average_transaction'),
                DB::raw('MAX(expenses.total_amount) as max_transaction'),
                DB::raw('MIN(expenses.total_amount) as min_transaction'),
                DB::raw('COUNT(DISTINCT expenses.category_id) as categories_used'),
                DB::raw('COUNT(DISTINCT expenses.vendor_name) as vendors_used')
            )
            ->groupBy(
                'payment_methods.id',
                'payment_methods.name',
                'payment_methods.type',
                'payment_methods.is_active'
            )
            ->orderBy('grand_total', 'desc')
            ->get();
        
        // Monthly trend by payment method
        $monthlyTrend = Expense::where('expenses.tenant_id', $tenantId)
            ->whereBetween('expenses.date', [$startDate, $endDate])
            ->join('payment_methods', 'expenses.payment_method_id', '=', 'payment_methods.id')
            ->select(
                DB::raw('YEAR(expenses.date) as year'),
                DB::raw('MONTH(expenses.date) as month'),
                'payment_methods.name as method_name',
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(expenses.total_amount) as monthly_total')
            )
            ->groupBy('year', 'month', 'payment_methods.name')
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->groupBy('method_name');
        
        // Payment method by category
        $methodByCategory = Expense::where('expenses.tenant_id', $tenantId)
            ->whereBetween('expenses.date', [$startDate, $endDate])
            ->join('payment_methods', 'expenses.payment_method_id', '=', 'payment_methods.id')
            ->join('expense_categories', 'expenses.category_id', '=', 'expense_categories.id')
            ->select(
                'payment_methods.name as method_name',
                'expense_categories.name as category_name',
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(expenses.total_amount) as total_amount')
            )
            ->groupBy('payment_methods.name', 'expense_categories.name')
            ->orderBy('method_name')
            ->orderBy('total_amount', 'desc')
            ->get()
            ->groupBy('method_name');
        
        $paymentMethods = PaymentMethod::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        return view('reports.expenses.by-payment-method', compact(
            'methodBreakdown',
            'monthlyTrend',
            'methodByCategory',
            'paymentMethods',
            'startDate',
            'endDate',
            'paymentMethodId'
        ));
    }
    
    // Recurring Expenses Report
    public function recurring(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        
        $frequency = $request->get('frequency');
        $categoryId = $request->get('category_id');
        $status = $request->get('status', 'active'); // active, upcoming, overdue
        
        $query = Expense::where('tenant_id', $tenantId)
            ->where('is_recurring', true)
            ->with(['category', 'paymentMethod']);
        
        if ($frequency) {
            $query->where('recurring_frequency', $frequency);
        }
        
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }
        
        // Status filtering
        $today = Carbon::today();
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
            ->get();
        
        // Group by frequency
        $byFrequency = $recurringExpenses->groupBy('recurring_frequency')->map(function($items) {
            return [
                'count' => $items->count(),
                'total_monthly' => $items->sum('total_amount'),
                'total_annual' => $items->sum(function($item) {
                    $multiplier = match($item->recurring_frequency) {
                        'weekly' => 4.33 * 12, // Approximate weeks in year
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
        
        // Monthly projection for the next 12 months (instead of historical)
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
                
                // Check if expense occurs in this month
                switch ($frequency) {
                    case 'weekly':
                        $weeksInMonth = 4.33;
                        return $expense->total_amount * $weeksInMonth;
                        
                    case 'monthly':
                        return $expense->total_amount;
                        
                    case 'quarterly':
                        // Check if this month is a quarter after the next date
                        $monthsDiff = $nextDate->diffInMonths($month);
                        return ($monthsDiff % 3 === 0) ? $expense->total_amount : 0;
                        
                    case 'annually':
                        // Check if this month is the anniversary month
                        return ($nextDate->format('m') === $month->format('m')) ? $expense->total_amount : 0;
                        
                    default:
                        return 0;
                }
            });
            
            $monthlyProjection[$monthKey] = [
                'month' => $monthName,
                'projected_total' => $monthlyTotal,
                'expense_count' => $recurringExpenses->count() // All recurring count for now
            ];
        }
        
        $categories = ExpenseCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        $frequencies = [
            'weekly' => 'Weekly',
            'monthly' => 'Monthly', 
            'quarterly' => 'Quarterly',
            'annually' => 'Annually'
        ];
        
        return view('reports.expenses.recurring', compact(
            'recurringExpenses',
            'byFrequency',
            'upcomingNext30Days',
            'annualProjection',
            'monthlyProjection', // Changed from historicalExecutions
            'categories',
            'frequencies',
            'frequency',
            'categoryId',
            'status'
        ));
    }
        
    // Expense Trends Report (Optimized)
    public function trends(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        
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
            
            // Monthly trend query
            $monthlyTrend = Expense::where('expenses.tenant_id', $tenantId)
                ->whereBetween('expenses.date', [$startDate, $endDate])
                ->select(
                    DB::raw('YEAR(expenses.date) as year'),
                    DB::raw('MONTH(expenses.date) as month'),
                    DB::raw('COUNT(*) as expense_count'),
                    DB::raw('SUM(expenses.amount) as total_amount'),
                    DB::raw('SUM(expenses.tax_amount) as total_tax'),
                    DB::raw('SUM(expenses.total_amount) as grand_total'),
                    DB::raw('AVG(expenses.total_amount) as average_amount')
                )
                ->groupBy(DB::raw('YEAR(expenses.date), MONTH(expenses.date)'))
                ->orderBy('year')
                ->orderBy('month')
                ->get();
            
            // Previous year comparison
            $prevYear = $year - 1;
            $prevYearTrend = Expense::where('tenant_id', $tenantId)
                ->whereBetween('date', [
                    Carbon::create($prevYear, 1, 1)->startOfYear(),
                    Carbon::create($prevYear, 12, 31)->endOfYear()
                ])
                ->select(
                    DB::raw('MONTH(date) as month'),
                    DB::raw('SUM(total_amount) as monthly_total')
                )
                ->groupBy(DB::raw('MONTH(date)'))
                ->orderBy('month')
                ->get()
                ->keyBy('month');
            
            // Build trend data for all months
            for ($month = 1; $month <= 12; $month++) {
                $currentMonth = $monthlyTrend->firstWhere('month', $month);
                $previousMonth = $prevYearTrend->get($month);
                
                $currentYearTotal = $currentMonth ? ($currentMonth->grand_total ?? 0) : 0;
                $previousYearTotal = $previousMonth ? ($previousMonth->monthly_total ?? 0) : 0;
                
                // Calculate growth
                $growth = 0;
                if ($previousYearTotal > 0) {
                    $growth = (($currentYearTotal - $previousYearTotal) / $previousYearTotal) * 100;
                }
                
                $trendData[$month] = [
                    'month' => $month,
                    'current_year' => $currentYearTotal,
                    'previous_year' => $previousYearTotal,
                    'growth' => $growth,
                    'expense_count' => $currentMonth ? ($currentMonth->expense_count ?? 0) : 0,
                ];
            }
            
            // Category trends - FIXED: Specify table for tenant_id
            $categoryTrends = Expense::where('expenses.tenant_id', $tenantId)
                ->whereBetween('expenses.date', [$startDate, $endDate])
                ->join('expense_categories', function($join) use ($tenantId) {
                    $join->on('expenses.category_id', '=', 'expense_categories.id')
                        ->where('expense_categories.tenant_id', '=', $tenantId);
                })
                ->select(
                    'expense_categories.name as category_name',
                    DB::raw('MONTH(expenses.date) as month'),
                    DB::raw('SUM(expenses.total_amount) as monthly_total')
                )
                ->groupBy('expense_categories.name', DB::raw('MONTH(expenses.date)'))
                ->orderBy('expense_categories.name')
                ->orderBy('month')
                ->get()
                ->groupBy('category_name');
            
            // Moving averages and growth rates
            $monthlyTotals = collect($trendData)->pluck('current_year')->toArray();
            
            // 3-month moving average
            if (count($monthlyTotals) >= 3) {
                for ($i = 2; $i < count($monthlyTotals); $i++) {
                    $movingAverages[$i + 1] = (
                        ($monthlyTotals[$i - 2] ?? 0) + 
                        ($monthlyTotals[$i - 1] ?? 0) + 
                        ($monthlyTotals[$i] ?? 0)
                    ) / 3;
                }
            }
            
            // Month-over-month growth
            if (count($monthlyTotals) >= 2) {
                for ($i = 1; $i < count($monthlyTotals); $i++) {
                    $prevMonthTotal = $monthlyTotals[$i - 1] ?? 0;
                    $currentMonthTotal = $monthlyTotals[$i] ?? 0;
                    
                    if ($prevMonthTotal > 0) {
                        $momGrowth[$i + 1] = (($currentMonthTotal - $prevMonthTotal) / $prevMonthTotal) * 100;
                    } else {
                        $momGrowth[$i + 1] = $currentMonthTotal > 0 ? 100 : 0;
                    }
                }
            }
            
        } elseif ($period === 'quarterly') {
            // Quarterly trend logic
            $quarters = [];
            for ($quarter = 1; $quarter <= 4; $quarter++) {
                $startMonth = ($quarter - 1) * 3 + 1;
                $endMonth = $startMonth + 2;
                
                $quarterTotal = Expense::where('tenant_id', $tenantId)
                    ->whereYear('date', $year)
                    ->whereBetween(DB::raw('MONTH(date)'), [$startMonth, $endMonth])
                    ->sum('total_amount') ?? 0;
                
                $quarters[$quarter] = [
                    'quarter' => $quarter,
                    'total' => $quarterTotal,
                    'start_month' => $startMonth,
                    'end_month' => $endMonth
                ];
            }
            
            $trendData = $quarters;
            
        } elseif ($period === 'yearly') {
            // Yearly trend (last 5 years)
            $currentYear = date('Y');
            $years = range($currentYear - 4, $currentYear);
            
            $yearlyTrend = [];
            foreach ($years as $yearItem) {
                $yearTotal = Expense::where('tenant_id', $tenantId)
                    ->whereYear('date', $yearItem)
                    ->sum('total_amount') ?? 0;
                
                $expenseCount = Expense::where('tenant_id', $tenantId)
                    ->whereYear('date', $yearItem)
                    ->count();
                
                $average = $expenseCount > 0 ? ($yearTotal / $expenseCount) : 0;
                
                $yearlyTrend[$yearItem] = [
                    'year' => $yearItem,
                    'total' => $yearTotal,
                    'expense_count' => $expenseCount,
                    'average' => $average
                ];
            }
            
            $trendData = $yearlyTrend;
        }
        
        // Category filter (if selected)
        if ($categoryId) {
            // Recalculate trend data for specific category
            $category = ExpenseCategory::find($categoryId);
            if ($category) {
                $filteredTrendData = [];
                
                if ($period === 'monthly') {
                    for ($month = 1; $month <= 12; $month++) {
                        $monthTotal = Expense::where('tenant_id', $tenantId)
                            ->where('category_id', $categoryId)
                            ->whereYear('date', $year)
                            ->whereMonth('date', $month)
                            ->sum('total_amount') ?? 0;
                        
                        $prevMonthTotal = Expense::where('tenant_id', $tenantId)
                            ->where('category_id', $categoryId)
                            ->whereYear('date', $year - 1)
                            ->whereMonth('date', $month)
                            ->sum('total_amount') ?? 0;
                        
                        $growth = $prevMonthTotal > 0 ? (($monthTotal - $prevMonthTotal) / $prevMonthTotal) * 100 : 0;
                        
                        $filteredTrendData[$month] = [
                            'month' => $month,
                            'current_year' => $monthTotal,
                            'previous_year' => $prevMonthTotal,
                            'growth' => $growth,
                            'expense_count' => Expense::where('tenant_id', $tenantId)
                                ->where('category_id', $categoryId)
                                ->whereYear('date', $year)
                                ->whereMonth('date', $month)
                                ->count(),
                        ];
                    }
                    $trendData = $filteredTrendData;
                }
                // Add similar logic for quarterly and yearly periods if needed
            }
        }
        
        $categories = ExpenseCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        $years = range(date('Y') - 5, date('Y'));
        
        return view('reports.expenses.trends', compact(
            'trendData',
            'period',
            'year',
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
        $tenantId = auth()->user()->tenant_id;
        
        $startDate = $request->get('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $categoryId = $request->get('category_id');
        $taxType = $request->get('tax_type'); // taxable, non-taxable, all
        
        $query = Expense::where('tenant_id', $tenantId)
            ->whereBetween('date', [$startDate, $endDate])
            ->with(['category']);
        
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }
        
        if ($taxType === 'taxable') {
            $query->where('tax_amount', '>', 0);
        } elseif ($taxType === 'non-taxable') {
            $query->where('tax_amount', '=', 0);
        }
        
        // Tax summary
        $taxSummary = [
            'total_expenses' => $query->count(),
            'total_amount' => $query->sum('amount'),
            'total_tax' => $query->sum('tax_amount'),
            'total_with_tax' => $query->sum('total_amount'),
            'taxable_expenses' => $query->clone()->where('tax_amount', '>', 0)->count(),
            'non_taxable_expenses' => $query->clone()->where('tax_amount', '=', 0)->count(),
            'avg_tax_rate' => $query->where('tax_amount', '>', 0)
                ->avg(DB::raw('(tax_amount / amount) * 100'))
        ];
        
        // Tax by category
        $taxByCategory = Expense::where('expenses.tenant_id', $tenantId)
            ->whereBetween('expenses.date', [$startDate, $endDate])
            ->join('expense_categories', 'expenses.category_id', '=', 'expense_categories.id')
            ->select(
                'expense_categories.name as category_name',
                DB::raw('COUNT(*) as expense_count'),
                DB::raw('SUM(expenses.amount) as subtotal'),
                DB::raw('SUM(expenses.tax_amount) as tax_total'),
                DB::raw('SUM(expenses.total_amount) as grand_total'),
                DB::raw('AVG((expenses.tax_amount / expenses.amount) * 100) as avg_tax_rate'),
                DB::raw('COUNT(CASE WHEN expenses.tax_amount > 0 THEN 1 END) as taxable_count'),
                DB::raw('COUNT(CASE WHEN expenses.tax_amount = 0 THEN 1 END) as non_taxable_count')
            )
            ->groupBy('expense_categories.name')
            ->orderBy('tax_total', 'desc')
            ->get();
        
        // Monthly tax breakdown
        $monthlyTax = Expense::where('tenant_id', $tenantId)
            ->whereBetween('date', [$startDate, $endDate])
            ->select(
                DB::raw('YEAR(date) as year'),
                DB::raw('MONTH(date) as month'),
                DB::raw('SUM(amount) as monthly_subtotal'),
                DB::raw('SUM(tax_amount) as monthly_tax'),
                DB::raw('SUM(total_amount) as monthly_total'),
                DB::raw('COUNT(*) as expense_count'),
                DB::raw('AVG((tax_amount / amount) * 100) as avg_monthly_tax_rate')
            )
            ->groupBy(DB::raw('YEAR(date), MONTH(date)'))
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();
        
        // Top tax expenses
        $topTaxExpenses = Expense::where('tenant_id', $tenantId)
            ->whereBetween('date', [$startDate, $endDate])
            ->where('tax_amount', '>', 0)
            ->with(['category', 'paymentMethod'])
            ->orderBy('tax_amount', 'desc')
            ->take(20)
            ->get();
        
        // Tax rate distribution
        $taxRateDistribution = Expense::where('tenant_id', $tenantId)
            ->whereBetween('date', [$startDate, $endDate])
            ->where('tax_amount', '>', 0)
            ->where('amount', '>', 0)
            ->select(
                DB::raw('ROUND((tax_amount / amount) * 100, 0) as tax_rate_percent'),
                DB::raw('COUNT(*) as expense_count'),
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('SUM(tax_amount) as total_tax')
            )
            ->groupBy(DB::raw('ROUND((tax_amount / amount) * 100, 0)'))
            ->orderBy('tax_rate_percent')
            ->get();
        
        $categories = ExpenseCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        return view('reports.expenses.tax-report', compact(
            'taxSummary',
            'taxByCategory',
            'monthlyTax',
            'topTaxExpenses',
            'taxRateDistribution',
            'categories',
            'startDate',
            'endDate',
            'categoryId',
            'taxType'
        ));
    }
    
    // Expense Audit Report (Fixed with Locale)
    public function audit(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        
        $startDate = $request->get('start_date', Carbon::now()->subMonths(3)->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $auditType = $request->get('audit_type', 'all'); // all, missing_receipts, unapproved, high_value
        $employeeId = $request->get('employee_id');
        
        $query = Expense::where('expenses.tenant_id', $tenantId)
            ->whereBetween('expenses.date', [$startDate, $endDate])
            ->with(['category', 'paymentMethod', 'employee', 'approver']);
        
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
                $threshold = $request->get('threshold', 1000);
                $query->where('total_amount', '>=', $threshold);
                break;
                
            case 'late_submissions':
                $query->where(DB::raw('DATEDIFF(created_at, date)'), '>', 7);
                break;
                
            case 'policy_violations':
                // Example: Expenses without receipt when required
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
            ->get();
        
        // Audit statistics
        $auditStats = [
            'total_items' => $auditItems->count(),
            'total_amount' => $auditItems->sum('total_amount'),
            'missing_receipts' => $auditItems->filter(function($item) {
                return $item->category && $item->category->requires_receipt && empty($item->receipt_url);
            })->count(),
            'unapproved' => $auditItems->filter(function($item) {
                return $item->category && $item->category->requires_approval && !$item->approved_at;
            })->count(),
            'high_value' => $auditItems->filter(function($item) use ($request) {
                $threshold = $request->get('threshold', 1000);
                return $item->total_amount >= $threshold;
            })->count(),
            'average_age_days' => $auditItems->avg(function($item) {
                return Carbon::parse($item->created_at)->diffInDays(Carbon::today());
            }) ?? 0,
        ];
        
        // Group by category for analysis
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
        
        // Monthly audit trend
        $monthlyAudit = Expense::where('expenses.tenant_id', $tenantId)
            ->whereBetween('expenses.date', [$startDate, $endDate])
            ->select(
                DB::raw('YEAR(expenses.date) as year'),
                DB::raw('MONTH(expenses.date) as month'),
                DB::raw('COUNT(*) as total_expenses'),
                DB::raw('COUNT(CASE WHEN receipt_url IS NULL OR receipt_url = "" THEN 1 END) as missing_receipts'),
                DB::raw('COUNT(CASE WHEN approved_at IS NULL THEN 1 END) as unapproved'),
                DB::raw('SUM(total_amount) as monthly_total')
            )
            ->groupBy(DB::raw('YEAR(expenses.date), MONTH(expenses.date)'))
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();
        
        // Employee compliance - FIXED: Use employees.id instead of employees.employee_id
        $employeeCompliance = Expense::where('expenses.tenant_id', $tenantId)
            ->whereBetween('expenses.date', [$startDate, $endDate])
            ->whereNotNull('expenses.employee_id')
            ->join('employees', 'expenses.employee_id', '=', 'employees.id')
            ->join('expense_categories', 'expenses.category_id', '=', 'expense_categories.id')
            ->select(
                'employees.id',
                'employees.first_name',
                'employees.last_name',
                'employees.email',
                DB::raw('CONCAT(employees.first_name, " ", employees.last_name) as employee_name'),
                DB::raw('COUNT(*) as total_expenses'),
                DB::raw('COUNT(CASE WHEN (expenses.receipt_url IS NULL OR expenses.receipt_url = "") 
                        AND expense_categories.requires_receipt = 1 THEN 1 END) as missing_receipts'),
                DB::raw('COUNT(CASE WHEN expenses.approved_at IS NULL 
                        AND expense_categories.requires_approval = 1 THEN 1 END) as unapproved'),
                DB::raw('AVG(expenses.total_amount) as avg_expense')
            )
            ->groupBy(
                'employees.id',
                'employees.first_name',
                'employees.last_name',
                'employees.email'
            )
            ->orderBy('missing_receipts', 'desc')
            ->get();
        
        $employees = Employee::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get();
        
        // Use locale for audit types
        $auditTypes = [
            'all' => __('accounting.all_items'),
            'missing_receipts' => __('accounting.missing_receipts'),
            'unapproved' => __('accounting.unapproved_expenses'),
            'high_value' => __('accounting.high_value_expenses'),
            'late_submissions' => __('accounting.late_submissions'),
            'policy_violations' => __('accounting.policy_violations')
        ];
        
        return view('reports.expenses.audit', compact(
            'auditItems',
            'auditStats',
            'byCategory',
            'monthlyAudit',
            'employeeCompliance',
            'employees',
            'startDate',
            'endDate',
            'auditType',
            'employeeId',
            'auditTypes'
        ));
    }
        
    // Export functions for each report
    public function exportByCategory(Request $request)
    {
        $data = $this->byCategory($request);
        // Export logic here
        return response()->streamDownload(function() use ($data) {
            // CSV export implementation
        }, 'expense-by-category-' . date('Y-m-d') . '.csv');
    }

}
