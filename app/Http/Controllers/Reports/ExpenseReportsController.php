<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{ Expense, Location };
use App\Models\ExpenseCategory;
use App\Models\PaymentMethod;
use App\Models\Employee;
use Illuminate\Support\Facades\{ DB };
use Carbon\Carbon;

class ExpenseReportsController extends Controller
{

    public function summary(Request $request)
    {
        $user     = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('expense reports')) {
            abort(403, __('payments.not_authorized'));
        }
        if (!tenant_can('expense_reports')) {
            abort(403, __('payments.feature_not_available_in_plan'));
        }
        if (!$tenantId) {
            return redirect()->back()->with('error', __('accounting.invalid_tenant'));
        }

        // ── Visibility rule ─────────────────────────────────────────
        $isAdmin         = $user->hasAnyRole(['super_admin', 'admin']);
        $userLocationIds = $user->locations()->pluck('locations.id')->toArray();

        // ── Date range ──────────────────────────────────────────────
        $startInput = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endInput   = $request->get('end_date',   now()->endOfMonth()->format('Y-m-d'));

        try {
            $startDateTime = Carbon::parse($startInput)->startOfDay();
            $endDateTime   = Carbon::parse($endInput)->endOfDay();
            if ($startDateTime > $endDateTime) {
                [$startDateTime, $endDateTime] = [$endDateTime->copy()->startOfDay(), $startDateTime->copy()->endOfDay()];
            }
        } catch (\Throwable $e) {
            $startDateTime = now()->startOfMonth();
            $endDateTime   = now()->endOfMonth();
        }

        $startDate = $startDateTime->format('Y-m-d');
        $endDate   = $endDateTime->format('Y-m-d');

        // ── Filters ─────────────────────────────────────────────────
        $categoryId      = $request->input('category_id');
        $vendorName      = trim((string) $request->input('vendor_name', ''));
        $paymentMethodId = $request->input('payment_method_id');
        $paymentStatus   = $request->input('payment_status');
        $employeeId      = $request->input('employee_id');
        $requiresReceipt = $request->input('requires_receipt');
        $isRecurring     = $request->input('is_recurring');
        $locationId      = $request->input('location_id');

        // ── Base query ──────────────────────────────────────────────
        $baseQuery = Expense::query()
            ->where('tenant_id', $tenantId)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate);

        if (!empty($categoryId) && is_numeric($categoryId)) {
            $baseQuery->where('category_id', (int) $categoryId);
        }

        if ($vendorName !== '' && mb_strlen($vendorName) >= 2) {
            $baseQuery->where(function ($q) use ($vendorName) {
                $q->where('vendor_name', 'like', "%{$vendorName}%")
                ->orWhereHas('supplier', fn($s) => $s->where('name', 'like', "%{$vendorName}%"));
            });
        }

        if (!empty($paymentMethodId) && is_numeric($paymentMethodId)) {
            $baseQuery->where('payment_method_id', (int) $paymentMethodId);
        }

        if (!empty($paymentStatus) && in_array($paymentStatus, ['pending', 'paid', 'reimbursed'], true)) {
            $baseQuery->where('payment_status', $paymentStatus);
        }

        if (!empty($employeeId) && is_numeric($employeeId)) {
            $baseQuery->where('employee_id', (int) $employeeId);
        }

        if ($requiresReceipt !== null && $requiresReceipt !== '' && in_array((string) $requiresReceipt, ['0', '1'], true)) {
            $baseQuery->whereHas('category', fn($c) => $c->where('requires_receipt', (bool) $requiresReceipt));
        }

        if ($isRecurring !== null && $isRecurring !== '' && in_array((string) $isRecurring, ['0', '1'], true)) {
            $baseQuery->where('is_recurring', (bool) $isRecurring);
        }

        // ── Location filter (with non-admin scoping) ────────────────
        if (!empty($locationId) && is_numeric($locationId)) {
            $requestedLocationId = (int) $locationId;

            $allowedToFilter = $isAdmin
                || in_array($requestedLocationId, $userLocationIds, true);

            if ($allowedToFilter) {
                $baseQuery->where('location_id', $requestedLocationId);
            }
            // else: silently ignore — user tried to filter by a location they don't own
        }

        // ── Data ────────────────────────────────────────────────────
        $expensesCollection = (clone $baseQuery)
            ->with(['category', 'paymentMethod', 'employee', 'supplier', 'location'])
            ->get();

        $totalAmount = (float) $expensesCollection->sum('total_amount');
        $totalTax    = (float) $expensesCollection->sum('tax_amount');

        $summary = [
            'total_expenses' => $expensesCollection->count(),
            'total_amount'   => $totalAmount,
            'total_tax'      => $totalTax,
            'avg_expense'    => $expensesCollection->count() > 0 ? $totalAmount / $expensesCollection->count() : 0,
            'max_expense'    => (float) ($expensesCollection->max('total_amount') ?? 0),
            'min_expense'    => (float) ($expensesCollection->min('total_amount') ?? 0),
        ];

        $dailyBreakdown = $expensesCollection
            ->groupBy(fn($expense) => $expense->date->format('Y-m-d'))
            ->map(function ($group, $date) {
                $count = $group->count();
                $total = (float) $group->sum('total_amount');
                return (object) [
                    'date'    => $date,
                    'count'   => $count,
                    'total'   => $total,
                    'tax'     => (float) $group->sum('tax_amount'),
                    'average' => $count > 0 ? $total / $count : 0,
                ];
            })
            ->sortByDesc('date')
            ->values();

        $topExpenses = (clone $baseQuery)
            ->with(['category', 'paymentMethod', 'employee', 'supplier', 'location'])
            ->orderByDesc('total_amount')
            ->take(10)
            ->get();

        // ── Filter options ─────────────────────────────────────────
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

        // Locations — scoped for non-admins
        $locationsQuery = Location::where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->orderBy('name');

        if (!$isAdmin) {
            $locationsQuery->whereIn('id', $userLocationIds);
        }

        $locations = $locationsQuery->get(['id', 'name']);

        return view('reports.expenses.summary', [
            'summary'         => $summary,
            'dailyBreakdown'  => $dailyBreakdown,
            'topExpenses'     => $topExpenses,
            'categories'      => $categories,
            'paymentMethods'  => $paymentMethods,
            'employees'       => $employees,
            'locations'       => $locations,
            'startDate'       => $startDate,
            'endDate'         => $endDate,
            'categoryId'      => $categoryId,
            'vendorName'      => $vendorName,
            'paymentMethodId' => $paymentMethodId,
            'paymentStatus'   => $paymentStatus,
            'employeeId'      => $employeeId,
            'requiresReceipt' => $requiresReceipt,
            'isRecurring'     => $isRecurring,
            'locationId'      => $locationId,
        ]);
    }
   
        
    public function byCategory(Request $request)
    {
        $user     = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('expense reports')) {
            abort(403, __('payments.not_authorized'));
        }
        if (!tenant_can('expense_reports')) {
            abort(403, __('payments.feature_not_available_in_plan'));
        }

        // ── Visibility rule ─────────────────────────────────────────
        $isAdmin         = $user->hasAnyRole(['super_admin', 'admin']);
        $userLocationIds = $user->locations()->pluck('locations.id')->toArray();

        // ── Date range ──────────────────────────────────────────────
        $startInput = $request->get('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endInput   = $request->get('end_date',   Carbon::now()->endOfMonth()->format('Y-m-d'));

        try {
            $startDate = Carbon::parse($startInput)->format('Y-m-d');
            $endDate   = Carbon::parse($endInput)->format('Y-m-d');
            if ($startDate > $endDate) {
                [$startDate, $endDate] = [$endDate, $startDate];
            }
        } catch (\Throwable $e) {
            $startDate = Carbon::now()->startOfYear()->format('Y-m-d');
            $endDate   = Carbon::now()->endOfMonth()->format('Y-m-d');
        }

        // ── Filters ─────────────────────────────────────────────────
        $categoryId = $request->get('category_id');
        $locationId = $request->get('location_id');

        // Resolve location filter (with non-admin scoping)
        $locationFilter = null;
        if (!empty($locationId) && is_numeric($locationId)) {
            $req = (int) $locationId;
            if ($isAdmin || in_array($req, $userLocationIds, true)) {
                $locationFilter = $req;
            }
        }

        // ── Base query ──────────────────────────────────────────────
        $baseQuery = Expense::query()
            ->where('tenant_id', $tenantId)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->when($categoryId && is_numeric($categoryId),
                fn($q) => $q->where('category_id', (int) $categoryId))
            ->when($locationFilter,
                fn($q) => $q->where('location_id', $locationFilter));

        // ── Category breakdown ──────────────────────────────────────
        $expenses = (clone $baseQuery)->with('category')->get();

        $categoryBreakdown = $expenses
            ->groupBy('category_id')
            ->map(function ($group) {
                $category    = $group->first()->category;
                $count       = $group->count();
                $grandTotal  = (float) $group->sum('total_amount');

                return (object) [
                    'category_name'   => $category->name ?? 'Uncategorized',
                    'category_code'   => $category->code ?? 'N/A',
                    'expense_count'   => $count,
                    'total_amount'    => $grandTotal,
                    'total_tax'       => (float) $group->sum('tax_amount'),
                    'grand_total'     => $grandTotal,
                    'average_amount'  => $count > 0 ? $grandTotal / $count : 0,
                    'max_amount'      => (float) ($group->max('total_amount') ?? 0),
                    'min_amount'      => (float) ($group->min('total_amount') ?? 0),
                ];
            })
            ->sortByDesc('grand_total')
            ->values();

        $totalExpenses = (float) $categoryBreakdown->sum('grand_total');

        // ── Monthly trend by category ───────────────────────────────
        $monthlyData = (clone $baseQuery)
            ->with('category')
            ->get()
            ->groupBy(fn($expense) => $expense->date->format('Y-m'));

        $monthlyTrend = collect();
        foreach ($monthlyData as $month => $expensesInMonth) {
            foreach ($expensesInMonth->groupBy('category_id') as $catId => $categoryExpenses) {
                $category = $categoryExpenses->first()->category;
                $monthlyTrend->push((object) [
                    'year'          => (int) date('Y', strtotime($month)),
                    'month'         => (int) date('n', strtotime($month)),
                    'category_name' => $category->name ?? 'Uncategorized',
                    'monthly_total' => (float) $categoryExpenses->sum('total_amount'),
                ]);
            }
        }

        $monthlyTrendGrouped = $monthlyTrend->groupBy('category_name');

        $uniqueMonths = $monthlyTrend
            ->groupBy(fn($item) => $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT))
            ->keys()
            ->sort()
            ->map(fn($key) => (object) [
                'year'  => (int) substr($key, 0, 4),
                'month' => (int) substr($key, 5, 2),
                'label' => date('M Y', strtotime($key . '-01')),
            ])
            ->values();

        // ── Filter options ──────────────────────────────────────────
        $categories = ExpenseCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $locationsQuery = \App\Models\Location::where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->orderBy('name');

        if (!$isAdmin) {
            $locationsQuery->whereIn('id', $userLocationIds);
        }

        $locations = $locationsQuery->get(['id', 'name']);

        // ── Matrix + totals for the trend table ─────────────────────
        $monthlyDataMatrix = [];
        foreach ($monthlyTrendGrouped as $categoryName => $categoryData) {
            foreach ($categoryData as $item) {
                $monthKey = $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT);
                $monthlyDataMatrix[$monthKey][$categoryName] = $item->monthly_total;
            }
        }

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
            'locations',
            'startDate',
            'endDate',
            'categoryId',
            'locationId',
            'monthlyDataMatrix',
            'categoryTotals'
        ));
    }
        
    public function byVendor(Request $request)
    {
        $user     = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('expense reports')) {
            abort(403, __('payments.not_authorized'));
        }
        if (!tenant_can('expense_reports')) {
            abort(403, __('payments.feature_not_available_in_plan'));
        }
        if (!$tenantId) {
            return redirect()->back()->with('error', __('accounting.invalid_tenant'));
        }

        // ── Visibility rule ─────────────────────────────────────────
        $isAdmin         = $user->hasAnyRole(['super_admin', 'admin']);
        $userLocationIds = $user->locations()->pluck('locations.id')->toArray();

        // ── Date range ──────────────────────────────────────────────
        $startInput = $request->get('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endInput   = $request->get('end_date',   Carbon::now()->endOfMonth()->format('Y-m-d'));

        try {
            $startDate = Carbon::parse($startInput)->format('Y-m-d');
            $endDate   = Carbon::parse($endInput)->format('Y-m-d');
            if ($startDate > $endDate) {
                [$startDate, $endDate] = [$endDate, $startDate];
            }
        } catch (\Throwable $e) {
            $startDate = Carbon::now()->startOfYear()->format('Y-m-d');
            $endDate   = Carbon::now()->endOfMonth()->format('Y-m-d');
        }

        // ── Filters ─────────────────────────────────────────────────
        $vendorName      = $request->get('vendor_name');
        $categoryId      = $request->get('category_id');
        $paymentMethodId = $request->get('payment_method_id');
        $minAmount       = $request->get('min_amount');
        $maxAmount       = $request->get('max_amount');
        $locationId      = $request->get('location_id');

        // Resolve location filter (non-admin scoping)
        $locationFilter = null;
        if (!empty($locationId) && is_numeric($locationId)) {
            $req = (int) $locationId;
            if ($isAdmin || in_array($req, $userLocationIds, true)) {
                $locationFilter = $req;
            }
        }

        // ── Base query ──────────────────────────────────────────────
        $baseQuery = Expense::query()
            ->where('tenant_id', $tenantId)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->whereNotNull('vendor_name')
            ->where('vendor_name', '!=', '');

        if ($vendorName && strlen(trim($vendorName)) >= 2) {
            $baseQuery->where('vendor_name', 'like', '%' . trim($vendorName) . '%');
        }
        if ($categoryId && is_numeric($categoryId)) {
            $baseQuery->where('category_id', (int) $categoryId);
        }
        if ($paymentMethodId && is_numeric($paymentMethodId)) {
            $baseQuery->where('payment_method_id', (int) $paymentMethodId);
        }
        if ($minAmount && is_numeric($minAmount)) {
            $baseQuery->where('total_amount', '>=', (float) $minAmount);
        }
        if ($maxAmount && is_numeric($maxAmount)) {
            $baseQuery->where('total_amount', '<=', (float) $maxAmount);
        }
        if ($locationFilter) {
            $baseQuery->where('location_id', $locationFilter);
        }

        // ── Data ────────────────────────────────────────────────────
        $allExpenses = (clone $baseQuery)
            ->with(['category', 'paymentMethod', 'location'])
            ->get();

        // Vendor breakdown
        $vendorBreakdown = $allExpenses->groupBy('vendor_name')
            ->map(function ($expenses, $vendorName) {
                $count      = $expenses->count();
                $grandTotal = (float) $expenses->sum('total_amount');

                return (object) [
                    'vendor_name'         => $vendorName,
                    'transaction_count'   => $count,
                    'total_amount'        => $grandTotal,
                    'total_tax'           => (float) $expenses->sum('tax_amount'),
                    'grand_total'         => $grandTotal,
                    'average_transaction' => $count > 0 ? $grandTotal / $count : 0,
                    'largest_transaction' => (float) ($expenses->max('total_amount') ?? 0),
                    'smallest_transaction'=> (float) ($expenses->min('total_amount') ?? 0),
                    'categories_used'     => $expenses->pluck('category_id')->unique()->count(),
                ];
            })
            ->sortByDesc('grand_total')
            ->values();

        // Summary
        $summary = [
            'total_vendors'      => $vendorBreakdown->count(),
            'total_transactions' => $allExpenses->count(),
            'total_amount'       => (float) $allExpenses->sum('total_amount'),
            'total_tax'          => (float) $allExpenses->sum('tax_amount'),
            'avg_transaction'    => (float) ($allExpenses->avg('total_amount') ?? 0),
            'largest_single'     => (float) ($allExpenses->max('total_amount') ?? 0),
            'unique_categories'  => ExpenseCategory::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->count(),
        ];

        // Vendor × payment method
        $vendorPaymentMethods = $allExpenses->groupBy('vendor_name')
            ->map(function ($expenses, $vendorName) {
                return $expenses->groupBy(fn($expense) => $expense->paymentMethod->name ?? 'Unknown')
                    ->map(function ($groupedExpenses, $methodName) use ($vendorName) {
                        return (object) [
                            'vendor_name'    => $vendorName,
                            'payment_method' => $methodName,
                            'payment_type'   => $groupedExpenses->first()->paymentMethod->type ?? 'unknown',
                            'count'          => $groupedExpenses->count(),
                            'total'          => (float) $groupedExpenses->sum('total_amount'),
                        ];
                    })
                    ->values();
            })
            ->filter()
            ->flatMap(fn($items) => $items)
            ->values();

        // Monthly activity
        $monthlyVendorActivity = $allExpenses->groupBy(fn($expense) => $expense->date->format('Y-m'))
            ->flatMap(function ($expensesInMonth, $monthKey) {
                [$year, $month] = explode('-', $monthKey);

                return $expensesInMonth->groupBy('vendor_name')
                    ->map(function ($vendorExpenses, $vendorName) use ($year, $month) {
                        return (object) [
                            'year'              => (int) $year,
                            'month'             => (int) $month,
                            'vendor_name'       => $vendorName,
                            'transaction_count' => $vendorExpenses->count(),
                            'monthly_total'     => (float) $vendorExpenses->sum('total_amount'),
                            'monthly_average'   => (float) ($vendorExpenses->avg('total_amount') ?? 0),
                        ];
                    })
                    ->values();
            })
            ->sortByDesc(fn($item) => $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT))
            ->values();

        // ── Filter options ──────────────────────────────────────────
        $uniqueVendors = $allExpenses->pluck('vendor_name')->unique()->sort()->values();

        $categories = ExpenseCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $paymentMethods = PaymentMethod::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'is_default']);

        // Locations (scoped for non-admins)
        $locationsQuery = \App\Models\Location::where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->orderBy('name');

        if (!$isAdmin) {
            $locationsQuery->whereIn('id', $userLocationIds);
        }

        $locations = $locationsQuery->get(['id', 'name']);

        return view('reports.expenses.by-vendor', [
            'vendorBreakdown'       => $vendorBreakdown,
            'vendorPaymentMethods'  => $vendorPaymentMethods,
            'monthlyVendorActivity' => $monthlyVendorActivity,
            'summary'               => $summary,
            'uniqueVendors'         => $uniqueVendors,
            'categories'            => $categories,
            'paymentMethods'        => $paymentMethods,
            'locations'             => $locations,
            'startDate'             => $startDate,
            'endDate'               => $endDate,
            'vendorName'            => $vendorName,
            'categoryId'            => $categoryId,
            'paymentMethodId'       => $paymentMethodId,
            'locationId'            => $locationId,
            'minAmount'             => $minAmount,
            'maxAmount'             => $maxAmount,
        ]);
    }
    
    // Employee Expenses Report
    public function byEmployee(Request $request)
    {
        $user     = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('expense reports')) {
            abort(403, __('payments.not_authorized'));
        }
        if (!tenant_can('expense_reports')) {
            abort(403, __('payments.feature_not_available_in_plan'));
        }
        if (!$tenantId) {
            return redirect()->back()->with('error', __('accounting.invalid_tenant'));
        }

        // ── Visibility rule ─────────────────────────────────────────
        $isAdmin         = $user->hasAnyRole(['super_admin', 'admin']);
        $userLocationIds = $user->locations()->pluck('locations.id')->toArray();

        // ── Date range ──────────────────────────────────────────────
        $startInput = $request->get('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endInput   = $request->get('end_date',   Carbon::now()->endOfMonth()->format('Y-m-d'));

        try {
            $startDate = Carbon::parse($startInput)->format('Y-m-d');
            $endDate   = Carbon::parse($endInput)->format('Y-m-d');
            if ($startDate > $endDate) {
                [$startDate, $endDate] = [$endDate, $startDate];
            }
        } catch (\Throwable $e) {
            $startDate = Carbon::now()->startOfYear()->format('Y-m-d');
            $endDate   = Carbon::now()->endOfMonth()->format('Y-m-d');
        }

        // ── Filters ─────────────────────────────────────────────────
        $employeeId       = $request->get('employee_id');
        $requiresApproval = $request->get('requires_approval');
        $locationId       = $request->get('location_id');

        $locationFilter = null;
        if (!empty($locationId) && is_numeric($locationId)) {
            $req = (int) $locationId;
            if ($isAdmin || in_array($req, $userLocationIds, true)) {
                $locationFilter = $req;
            }
        }

        // ── Base query ──────────────────────────────────────────────
        $baseQuery = Expense::query()
            ->where('tenant_id', $tenantId)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->whereNotNull('employee_id');

        if ($employeeId && is_numeric($employeeId)) {
            $baseQuery->where('employee_id', (int) $employeeId);
        }

        if ($requiresApproval !== null && $requiresApproval !== '' && in_array((string) $requiresApproval, ['0', '1'], true)) {
            $baseQuery->whereHas('category', fn($q) => $q->where('requires_approval', (bool) $requiresApproval));
        }

        if ($locationFilter) {
            $baseQuery->where('location_id', $locationFilter);
        }

        // ── Data ────────────────────────────────────────────────────
        $allExpenses = (clone $baseQuery)
            ->with(['employee', 'employee.department', 'category', 'location'])
            ->get();

        // Employee breakdown
        $employeeBreakdown = $allExpenses->groupBy('employee_id')
            ->map(function ($expenses, $empId) {
                $employee = $expenses->first()->employee;
                $count    = $expenses->count();
                $grandTotal = (float) $expenses->sum('total_amount');

                return (object) [
                    'employee_id'        => $empId,
                    'first_name'         => $employee->first_name ?? 'Unknown',
                    'last_name'          => $employee->last_name ?? '',
                    'employee_name'      => trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? '')),
                    'department'         => $employee->department->name ?? 'No Department',
                    'expense_count'      => $count,
                    'total_amount'       => $grandTotal,
                    'total_tax'          => (float) $expenses->sum('tax_amount'),
                    'grand_total'        => $grandTotal,
                    'average_expense'    => $count > 0 ? $grandTotal / $count : 0,
                    'max_expense'        => (float) ($expenses->max('total_amount') ?? 0),
                    'pending_count'      => $expenses->where('payment_status', 'pending')->count(),
                    'paid_count'         => $expenses->where('payment_status', 'paid')->count(),
                    'reimbursed_count'   => $expenses->where('payment_status', 'reimbursed')->count(),
                ];
            })
            ->sortByDesc('grand_total')
            ->values();

        // Monthly spending
        $monthlySpendingRaw = $allExpenses
            ->groupBy(fn($expense) => $expense->date->format('Y-m'))
            ->flatMap(function ($expensesInMonth, $monthKey) {
                [$year, $month] = explode('-', $monthKey);

                return $expensesInMonth->groupBy('employee_id')
                    ->map(function ($empExpenses) use ($year, $month) {
                        $employee = $empExpenses->first()->employee;

                        return (object) [
                            'year'              => (int) $year,
                            'month'             => (int) $month,
                            'employee_name'     => trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? '')),
                            'department'        => $employee->department->name ?? 'No Department',
                            'transaction_count' => $empExpenses->count(),
                            'monthly_total'     => (float) $empExpenses->sum('total_amount'),
                        ];
                    })
                    ->values();
            })
            ->values();

        $monthlySpending = $monthlySpendingRaw->groupBy('employee_name');
        $allMonthlyData  = $monthlySpendingRaw->values();

        // Categories by employee
        $employeeCategoriesRaw = $allExpenses->groupBy('employee_id')
            ->flatMap(function ($empExpenses) {
                $employee     = $empExpenses->first()->employee;
                $employeeName = trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? ''));

                return $empExpenses->groupBy('category_id')
                    ->map(function ($catExpenses) use ($employeeName) {
                        $category = $catExpenses->first()->category;

                        return (object) [
                            'employee_name' => $employeeName,
                            'category_name' => $category->name ?? 'Uncategorized',
                            'count'         => $catExpenses->count(),
                            'total'         => (float) $catExpenses->sum('total_amount'),
                        ];
                    })
                    ->values();
            });

        $employeeCategories = $employeeCategoriesRaw->groupBy('employee_name');

        // ── Summary ─────────────────────────────────────────────────
        $summary = [
            'total_employees'  => $employeeBreakdown->count(),
            'total_expenses'   => $allExpenses->count(),
            'total_amount'     => (float) $allExpenses->sum('total_amount'),
            'total_tax'        => (float) $allExpenses->sum('tax_amount'),
            'avg_per_employee' => (float) ($allExpenses->avg('total_amount') ?? 0),
            'largest_expense'  => (float) ($allExpenses->max('total_amount') ?? 0),
        ];

        // ── Filter options ──────────────────────────────────────────
        $employees = Employee::with('department')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

        // Locations (scoped for non-admins)
        $locationsQuery = \App\Models\Location::where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->orderBy('name');

        if (!$isAdmin) {
            $locationsQuery->whereIn('id', $userLocationIds);
        }

        $locations = $locationsQuery->get(['id', 'name']);

        return view('reports.expenses.by-employee', [
            'employeeBreakdown' => $employeeBreakdown,
            'monthlySpending'   => $monthlySpending,
            'allMonthlyData'    => $allMonthlyData,
            'employeeCategories'=> $employeeCategories,
            'employees'         => $employees,
            'locations'         => $locations,
            'summary'           => $summary,
            'startDate'         => $startDate,
            'endDate'           => $endDate,
            'employeeId'        => $employeeId,
            'requiresApproval'  => $requiresApproval,
            'locationId'        => $locationId,
        ]);
    }

    public function byPaymentMethod(Request $request)
    {
        $user     = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('expense reports')) {
            abort(403, __('payments.not_authorized'));
        }
        if (!tenant_can('expense_reports')) {
            abort(403, __('payments.feature_not_available_in_plan'));
        }

        // ── Visibility rule ─────────────────────────────────────────
        $isAdmin         = $user->hasAnyRole(['super_admin', 'admin']);
        $userLocationIds = $user->locations()->pluck('locations.id')->toArray();

        // ── Date range ──────────────────────────────────────────────
        $startInput = $request->get('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endInput   = $request->get('end_date',   Carbon::now()->endOfMonth()->format('Y-m-d'));

        try {
            $startDate = Carbon::parse($startInput)->format('Y-m-d');
            $endDate   = Carbon::parse($endInput)->format('Y-m-d');
            if ($startDate > $endDate) {
                [$startDate, $endDate] = [$endDate, $startDate];
            }
        } catch (\Throwable $e) {
            $startDate = Carbon::now()->startOfYear()->format('Y-m-d');
            $endDate   = Carbon::now()->endOfMonth()->format('Y-m-d');
        }

        // ── Filters ─────────────────────────────────────────────────
        $paymentMethodId = $request->get('payment_method_id');
        $locationId      = $request->get('location_id');

        $locationFilter = null;
        if (!empty($locationId) && is_numeric($locationId)) {
            $req = (int) $locationId;
            if ($isAdmin || in_array($req, $userLocationIds, true)) {
                $locationFilter = $req;
            }
        }

        // ── Base query ──────────────────────────────────────────────
        $baseQuery = Expense::query()
            ->where('tenant_id', $tenantId)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->whereNotNull('payment_method_id');

        if ($paymentMethodId && is_numeric($paymentMethodId)) {
            $baseQuery->where('payment_method_id', (int) $paymentMethodId);
        }
        if ($locationFilter) {
            $baseQuery->where('location_id', $locationFilter);
        }

        // ── Data ────────────────────────────────────────────────────
        $allExpenses = (clone $baseQuery)
            ->with(['paymentMethod', 'category', 'location'])
            ->get();

        // Group by payment method
        $methodBreakdown = $allExpenses->groupBy('payment_method_id')
            ->map(function ($expenses, $methodId) {
                $paymentMethod = $expenses->first()->paymentMethod;
                $count         = $expenses->count();
                $grandTotal    = (float) $expenses->sum('total_amount');

                return (object) [
                    'id'                  => $methodId,
                    'method_name'         => $paymentMethod->name ?? 'Unknown',
                    'method_type'         => $paymentMethod->type ?? 'unknown',
                    'is_active'           => $paymentMethod->is_active ?? false,
                    'transaction_count'   => $count,
                    'total_amount'        => $grandTotal,
                    'total_tax'           => (float) $expenses->sum('tax_amount'),
                    'grand_total'         => $grandTotal,
                    'average_transaction' => $count > 0 ? $grandTotal / $count : 0,
                    'max_transaction'     => (float) ($expenses->max('total_amount') ?? 0),
                    'min_transaction'     => (float) ($expenses->min('total_amount') ?? 0),
                    'categories_used'     => $expenses->pluck('category_id')->unique()->count(),
                    'vendors_used'        => $expenses->pluck('vendor_name')->unique()->count(),
                ];
            })
            ->sortByDesc('grand_total')
            ->values();

        // Monthly trend
        $monthlyTrendRaw = $allExpenses
            ->groupBy(fn($expense) => $expense->date->format('Y-m'))
            ->flatMap(function ($expensesInMonth, $monthKey) {
                [$year, $month] = explode('-', $monthKey);

                return $expensesInMonth->groupBy('payment_method_id')
                    ->map(function ($methodExpenses) use ($year, $month) {
                        $paymentMethod = $methodExpenses->first()->paymentMethod;

                        return (object) [
                            'year'              => (int) $year,
                            'month'             => (int) $month,
                            'method_name'       => $paymentMethod->name ?? 'Unknown',
                            'transaction_count' => $methodExpenses->count(),
                            'monthly_total'     => (float) $methodExpenses->sum('total_amount'),
                        ];
                    })
                    ->values();
            })
            ->values();

        $monthlyTrend = $monthlyTrendRaw->groupBy('method_name');

        // Method × category
        $methodByCategoryRaw = $allExpenses->groupBy('payment_method_id')
            ->flatMap(function ($expenses) {
                $paymentMethod = $expenses->first()->paymentMethod;
                $methodName    = $paymentMethod->name ?? 'Unknown';

                return $expenses->groupBy('category_id')
                    ->map(function ($categoryExpenses) use ($methodName) {
                        $category = $categoryExpenses->first()->category;

                        return (object) [
                            'method_name'       => $methodName,
                            'category_name'     => $category->name ?? 'Uncategorized',
                            'transaction_count' => $categoryExpenses->count(),
                            'total_amount'      => (float) $categoryExpenses->sum('total_amount'),
                        ];
                    })
                    ->values();
            })
            ->values();

        $methodByCategory = $methodByCategoryRaw->groupBy('method_name');

        // Summary
        $totalExpenses = (float) $methodBreakdown->sum('grand_total');
        $summary = [
            'total_methods'      => $methodBreakdown->count(),
            'total_transactions' => $methodBreakdown->sum('transaction_count'),
            'total_amount'       => $totalExpenses,
            'avg_per_method'     => $methodBreakdown->count() > 0 ? $totalExpenses / $methodBreakdown->count() : 0,
            'most_used_method'   => $methodBreakdown->first()->method_name ?? 'N/A',
        ];

        // ── Filter options ──────────────────────────────────────────
        $paymentMethods = PaymentMethod::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'is_default']);

        $locationsQuery = \App\Models\Location::where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->orderBy('name');

        if (!$isAdmin) {
            $locationsQuery->whereIn('id', $userLocationIds);
        }

        $locations = $locationsQuery->get(['id', 'name']);

        return view('reports.expenses.by-payment-method', compact(
            'methodBreakdown',
            'monthlyTrend',
            'methodByCategory',
            'paymentMethods',
            'locations',
            'summary',
            'startDate',
            'endDate',
            'paymentMethodId',
            'locationId'
        ));
    }

            
    public function budgetVsActual(Request $request)
    {
        $user     = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('expense reports')) {
            abort(403, __('payments.not_authorized'));
        }
        if (!tenant_can('expense_reports')) {
            abort(403, __('payments.feature_not_available_in_plan'));
        }

        // ── Visibility rule ─────────────────────────────────────────
        $isAdmin         = $user->hasAnyRole(['super_admin', 'admin']);
        $userLocationIds = $user->locations()->pluck('locations.id')->toArray();

        // ── Filters ─────────────────────────────────────────────────
        $year       = (int) $request->get('year', date('Y'));
        $month      = (int) $request->get('month', date('m'));
        $locationId = $request->get('location_id');

        $locationFilter = null;
        if (!empty($locationId) && is_numeric($locationId)) {
            $req = (int) $locationId;
            if ($isAdmin || in_array($req, $userLocationIds, true)) {
                $locationFilter = $req;
            }
        }

        // ── Budgeted categories ─────────────────────────────────────
        $budgetedCategories = ExpenseCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNotNull('budget_monthly')
                ->orWhereNotNull('budget_annual');
            })
            ->orderBy('name')
            ->get();

        // ── Base query for actuals (with location filter) ───────────
        $baseQuery = Expense::query()
            ->where('tenant_id', $tenantId)
            ->whereYear('date', $year)
            ->when($locationFilter, fn($q) => $q->where('location_id', $locationFilter));

        $budgetData          = [];
        $totalBudgetMonthly  = 0;
        $totalBudgetAnnual   = 0;
        $totalActualMonthly  = 0;
        $totalActualAnnual   = 0;

        // Fetch all expenses for the year once, then group in PHP.
        // Query-builder sum() returns raw cents; going through the collection
        // means the accessor fires and we get display units.
        $allYearExpenses = (clone $baseQuery)->get();

        $expensesByCategory = $allYearExpenses->groupBy('category_id');

        foreach ($budgetedCategories as $category) {
            $categoryExpenses = $expensesByCategory->get($category->id, collect());

            // Monthly actual — filter the collection by month
            $monthlyActual = (float) $categoryExpenses
                ->filter(fn($e) => $e->date->month === $month)
                ->sum('total_amount');

            // Annual actual — the whole year
            $annualActual = (float) $categoryExpenses->sum('total_amount');

            $budgetMonthly = (float) ($category->budget_monthly ?? 0);
            $budgetAnnual  = (float) ($category->budget_annual ?? ($budgetMonthly * 12));

            $varianceMonthly = $budgetMonthly - $monthlyActual;
            $varianceAnnual  = $budgetAnnual - $annualActual;

            $budgetData[] = [
                'category'                    => $category,
                'budget_monthly'              => $budgetMonthly,
                'actual_monthly'              => $monthlyActual,
                'variance_monthly'            => $varianceMonthly,
                'variance_percentage_monthly' => $budgetMonthly > 0 ? ($varianceMonthly / $budgetMonthly) * 100 : 0,

                'budget_annual'               => $budgetAnnual,
                'actual_annual'               => $annualActual,
                'variance_annual'             => $varianceAnnual,
                'variance_percentage_annual'  => $budgetAnnual > 0 ? ($varianceAnnual / $budgetAnnual) * 100 : 0,
            ];

            $totalBudgetMonthly  += $budgetMonthly;
            $totalBudgetAnnual   += $budgetAnnual;
            $totalActualMonthly  += $monthlyActual;
            $totalActualAnnual   += $annualActual;
        }

        // ── Summary ─────────────────────────────────────────────────
        $summary = [
            'total_budget_monthly'        => $totalBudgetMonthly,
            'total_actual_monthly'        => $totalActualMonthly,
            'total_variance_monthly'      => $totalBudgetMonthly - $totalActualMonthly,
            'variance_percentage_monthly' => $totalBudgetMonthly > 0
                ? (($totalBudgetMonthly - $totalActualMonthly) / $totalBudgetMonthly) * 100
                : 0,

            'total_budget_annual'         => $totalBudgetAnnual,
            'total_actual_annual'         => $totalActualAnnual,
            'total_variance_annual'       => $totalBudgetAnnual - $totalActualAnnual,
            'variance_percentage_annual'  => $totalBudgetAnnual > 0
                ? (($totalBudgetAnnual - $totalActualAnnual) / $totalBudgetAnnual) * 100
                : 0,

            'under_budget_count'          => collect($budgetData)->where('variance_monthly', '>', 0)->count(),
            'over_budget_count'           => collect($budgetData)->where('variance_monthly', '<', 0)->count(),
            'on_budget_count'             => collect($budgetData)->where('variance_monthly', '==', 0)->count(),
        ];

        // ── Monthly trends ──────────────────────────────────────────
        $monthlyTrends = [];
        foreach ($budgetedCategories as $category) {
            $categoryExpenses = $expensesByCategory->get($category->id, collect());

            $monthlyData = $categoryExpenses
                ->groupBy(fn($expense) => $expense->date->month)
                ->map(fn($group) => (float) $group->sum('total_amount'));

            $trend = [];
            for ($m = 1; $m <= 12; $m++) {
                $actual = $monthlyData[$m] ?? 0;
                $budget = (float) ($category->budget_monthly ?? 0);

                $trend[$m] = [
                    'month'               => $m,
                    'budget'              => $budget,
                    'actual'              => $actual,
                    'variance'            => $budget - $actual,
                    'variance_percentage' => $budget > 0 ? (($budget - $actual) / $budget) * 100 : 0,
                ];
            }

            $monthlyTrends[$category->id] = $trend;
        }

        // ── Filter options ──────────────────────────────────────────
        $years = range(date('Y') - 5, date('Y') + 1);
        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
        ];

        $locationsQuery = \App\Models\Location::where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->orderBy('name');

        if (!$isAdmin) {
            $locationsQuery->whereIn('id', $userLocationIds);
        }

        $locations = $locationsQuery->get(['id', 'name']);

        return view('reports.expenses.budget-vs-actual', compact(
            'budgetData',
            'summary',
            'monthlyTrends',
            'years',
            'months',
            'year',
            'month',
            'locations',
            'locationId'
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
        if (!tenant_can('expense_reports')) {
            abort(403, __('payments.feature_not_available_in_plan'));
        }

        $data = $this->summary($request);
        // Add export logic here
        return response()->streamDownload(function() use ($data) {
            echo "Expense Summary Report\n";
            echo "Generated: " . date('Y-m-d H:i:s') . "\n\n";
            // Export data in CSV format
        }, 'expense-summary-' . date('Y-m-d') . '.csv');
    }

    
    // Recurring Expenses Report
    public function recurring(Request $request)
    {
        $user     = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('expense reports')) {
            abort(403, __('payments.not_authorized'));
        }
        if (!tenant_can('expense_reports')) {
            abort(403, __('payments.feature_not_available_in_plan'));
        }

        // ── Visibility rule ─────────────────────────────────────────
        $isAdmin         = $user->hasAnyRole(['super_admin', 'admin']);
        $userLocationIds = $user->locations()->pluck('locations.id')->toArray();

        // ── Filters ─────────────────────────────────────────────────
        $frequency  = $request->get('frequency');
        $categoryId = $request->get('category_id');
        $status     = $request->get('status', 'active');
        $locationId = $request->get('location_id');

        $locationFilter = null;
        if (!empty($locationId) && is_numeric($locationId)) {
            $req = (int) $locationId;
            if ($isAdmin || in_array($req, $userLocationIds, true)) {
                $locationFilter = $req;
            }
        }

        // ── Base query ──────────────────────────────────────────────
        $baseQuery = Expense::query()
            ->where('tenant_id', $tenantId)
            ->where('is_recurring', true);

        if ($frequency) {
            $baseQuery->where('recurring_frequency', $frequency);
        }
        if ($categoryId && is_numeric($categoryId)) {
            $baseQuery->where('category_id', (int) $categoryId);
        }
        if ($locationFilter) {
            $baseQuery->where('location_id', $locationFilter);
        }

        $today = Carbon::today();

        if ($status === 'active') {
            $baseQuery->where(function ($q) use ($today) {
                $q->where('next_recurring_date', '>=', $today)
                ->orWhereNull('next_recurring_date');
            });
        } elseif ($status === 'upcoming') {
            $nextWeek = $today->copy()->addWeek();
            $baseQuery->whereBetween('next_recurring_date', [$today, $nextWeek]);
        } elseif ($status === 'overdue') {
            $baseQuery->where('next_recurring_date', '<', $today);
        }

        // ── Data ────────────────────────────────────────────────────
        $recurringExpenses = (clone $baseQuery)
            ->with(['category', 'paymentMethod', 'location'])
            ->orderBy('next_recurring_date', 'asc')
            ->get();

        // Frequency breakdown
        $byFrequency = $recurringExpenses->groupBy('recurring_frequency')
            ->map(function ($items, $freq) {
                $multiplier = match ($freq) {
                    'weekly'    => 52,
                    'monthly'   => 12,
                    'quarterly' => 4,
                    'annually'  => 1,
                    default     => 12,
                };

                $monthlyTotal = (float) $items->sum('total_amount');

                return [
                    'count'         => $items->count(),
                    'total_monthly' => $monthlyTotal,
                    'total_annual'  => $items->sum(fn($item) => $item->total_amount * $multiplier),
                ];
            });

        // Upcoming 30 days
        $upcomingNext30Days = $recurringExpenses->filter(function ($expense) use ($today) {
            if (!$expense->next_recurring_date) return false;
            $nextDate = Carbon::parse($expense->next_recurring_date);
            return $nextDate->between($today, $today->copy()->addDays(30));
        })->sortBy('next_recurring_date');

        // Annual projection
        $annualProjection = $recurringExpenses->sum(function ($expense) {
            $multiplier = match ($expense->recurring_frequency) {
                'weekly'    => 52,
                'monthly'   => 12,
                'quarterly' => 4,
                'annually'  => 1,
                default     => 12,
            };
            return $expense->total_amount * $multiplier;
        });

        // Monthly projection (12 months)
        $monthlyProjection = [];
        $currentMonth = Carbon::now()->startOfMonth();

        for ($i = 0; $i < 12; $i++) {
            $month     = $currentMonth->copy()->addMonths($i);
            $monthName = $month->format('M Y');

            $monthlyTotal = $recurringExpenses->sum(function ($expense) use ($month) {
                if (!$expense->next_recurring_date) return 0;

                $nextDate  = Carbon::parse($expense->next_recurring_date);
                $frequency = $expense->recurring_frequency;

                return match ($frequency) {
                    'weekly'    => $expense->total_amount * 4.33,
                    'monthly'   => $expense->total_amount,
                    'quarterly' => $nextDate->diffInMonths($month) % 3 === 0 ? $expense->total_amount : 0,
                    'annually'  => $nextDate->format('m') === $month->format('m') ? $expense->total_amount : 0,
                    default     => 0,
                };
            });

            $monthlyProjection[] = [
                'month'           => $monthName,
                'projected_total' => $monthlyTotal,
                'expense_count'   => $recurringExpenses->count(),
            ];
        }

        // ── Filter options ──────────────────────────────────────────
        $categories = ExpenseCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $locationsQuery = \App\Models\Location::where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->orderBy('name');

        if (!$isAdmin) {
            $locationsQuery->whereIn('id', $userLocationIds);
        }

        $locations = $locationsQuery->get(['id', 'name']);

        $frequencies = [
            'weekly'    => 'Weekly',
            'monthly'   => 'Monthly',
            'quarterly' => 'Quarterly',
            'annually'  => 'Annually',
        ];

        // ── Summary ─────────────────────────────────────────────────
        $summary = [
            'total_recurring'     => $recurringExpenses->count(),
            'total_monthly_cost'  => (float) $recurringExpenses->sum('total_amount'),
            'total_annual_cost'   => (float) $annualProjection,
            'upcoming_30_days'    => $upcomingNext30Days->count(),
            'avg_per_expense'     => (float) ($recurringExpenses->avg('total_amount') ?? 0),
        ];

        return view('reports.expenses.recurring', compact(
            'recurringExpenses',
            'byFrequency',
            'upcomingNext30Days',
            'annualProjection',
            'monthlyProjection',
            'categories',
            'locations',
            'frequencies',
            'summary',
            'frequency',
            'categoryId',
            'status',
            'locationId'
        ));
    }
            
    public function trends(Request $request)
    {
        $user     = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('expense reports')) {
            abort(403, __('payments.not_authorized'));
        }
        if (!tenant_can('expense_reports')) {
            abort(403, __('payments.feature_not_available_in_plan'));
        }

        // ── Visibility rule ─────────────────────────────────────────
        $isAdmin         = $user->hasAnyRole(['super_admin', 'admin']);
        $userLocationIds = $user->locations()->pluck('locations.id')->toArray();

        // ── Filters ─────────────────────────────────────────────────
        $period     = $request->get('period', 'monthly'); // monthly, quarterly, yearly
        $year       = (int) $request->get('year', date('Y'));
        $categoryId = $request->get('category_id');
        $locationId = $request->get('location_id');

        $locationFilter = null;
        if (!empty($locationId) && is_numeric($locationId)) {
            $req = (int) $locationId;
            if ($isAdmin || in_array($req, $userLocationIds, true)) {
                $locationFilter = $req;
            }
        }

        $trendData       = [];
        $categoryTrends  = collect();
        $movingAverages  = [];
        $momGrowth       = [];

        if ($period === 'monthly') {
            $startDate = Carbon::create($year, 1, 1)->startOfYear();
            $endDate   = Carbon::create($year, 12, 31)->endOfYear();

            // Current year
            $expenses = Expense::with('category')
                ->where('tenant_id', $tenantId)
                ->whereBetween('date', [$startDate, $endDate])
                ->when($categoryId, fn($q) => $q->where('category_id', (int) $categoryId))
                ->when($locationFilter, fn($q) => $q->where('location_id', $locationFilter))
                ->get();

            $monthlyExpenses = $expenses->groupBy(fn($e) => $e->date->month);

            // Previous year (same filters)
            $prevYearStart = Carbon::create($year - 1, 1, 1)->startOfYear();
            $prevYearEnd   = Carbon::create($year - 1, 12, 31)->endOfYear();

            $prevYearExpenses = Expense::where('tenant_id', $tenantId)
                ->whereBetween('date', [$prevYearStart, $prevYearEnd])
                ->when($categoryId, fn($q) => $q->where('category_id', (int) $categoryId))
                ->when($locationFilter, fn($q) => $q->where('location_id', $locationFilter))
                ->get()
                ->groupBy(fn($e) => $e->date->month);

            for ($month = 1; $month <= 12; $month++) {
                $currentExpenses  = $monthlyExpenses->get($month, collect());
                $previousExpenses = $prevYearExpenses->get($month, collect());

                $currentTotal  = (float) $currentExpenses->sum('total_amount');
                $previousTotal = (float) $previousExpenses->sum('total_amount');

                $growth = $previousTotal > 0
                    ? (($currentTotal - $previousTotal) / $previousTotal) * 100
                    : 0;

                $trendData[$month] = [
                    'month'          => $month,
                    'month_name'     => Carbon::create()->month($month)->format('F'),
                    'current_year'   => $currentTotal,
                    'previous_year'  => $previousTotal,
                    'growth'         => $growth,
                    'expense_count'  => $currentExpenses->count(),
                    'tax_total'      => (float) $currentExpenses->sum('tax_amount'),
                ];
            }

            // Category trends (top 5 by total)
            $categoryTrends = $expenses->groupBy('category_id')
                ->map(function ($catExpenses) {
                    $category = $catExpenses->first()->category;

                    $monthlyData = [];
                    for ($m = 1; $m <= 12; $m++) {
                        $monthlyData[$m] = (float) $catExpenses
                            ->filter(fn($e) => $e->date->month === $m)
                            ->sum('total_amount');
                    }

                    return (object) [
                        'category_name' => $category->name ?? 'Uncategorized',
                        'monthly_data'  => $monthlyData,
                        'total'         => (float) $catExpenses->sum('total_amount'),
                    ];
                })
                ->sortByDesc('total')
                ->take(5)
                ->values();

            // Moving averages (3-month rolling)
            $monthlyTotals = collect($trendData)->pluck('current_year')->values()->all();
            for ($i = 2; $i < count($monthlyTotals); $i++) {
                $movingAverages[$i + 1] = (
                    ($monthlyTotals[$i - 2] ?? 0) +
                    ($monthlyTotals[$i - 1] ?? 0) +
                    ($monthlyTotals[$i] ?? 0)
                ) / 3;
            }

            // Month-over-month growth
            for ($i = 1; $i < count($monthlyTotals); $i++) {
                $prev = $monthlyTotals[$i - 1] ?? 0;
                $curr = $monthlyTotals[$i] ?? 0;
                $momGrowth[$i + 1] = $prev > 0
                    ? (($curr - $prev) / $prev) * 100
                    : ($curr > 0 ? 100 : 0);
            }

        } elseif ($period === 'quarterly') {
            $expenses = Expense::where('tenant_id', $tenantId)
                ->whereYear('date', $year)
                ->when($categoryId, fn($q) => $q->where('category_id', (int) $categoryId))
                ->when($locationFilter, fn($q) => $q->where('location_id', $locationFilter))
                ->get();

            for ($quarter = 1; $quarter <= 4; $quarter++) {
                $startMonth = ($quarter - 1) * 3 + 1;
                $endMonth   = $startMonth + 2;

                $quarterExpenses = $expenses->filter(function ($e) use ($startMonth, $endMonth) {
                    return $e->date->month >= $startMonth && $e->date->month <= $endMonth;
                });

                $trendData[$quarter] = [
                    'quarter'       => $quarter,
                    'quarter_name'  => "Q{$quarter}",
                    'total'         => (float) $quarterExpenses->sum('total_amount'),
                    'start_month'   => $startMonth,
                    'end_month'     => $endMonth,
                    'expense_count' => $quarterExpenses->count(),
                ];
            }

        } elseif ($period === 'yearly') {
            $currentYear = (int) date('Y');
            $years       = range($currentYear - 4, $currentYear);

            foreach ($years as $yearItem) {
                $yearExpenses = Expense::where('tenant_id', $tenantId)
                    ->whereYear('date', $yearItem)
                    ->when($categoryId, fn($q) => $q->where('category_id', (int) $categoryId))
                    ->when($locationFilter, fn($q) => $q->where('location_id', $locationFilter))
                    ->get();

                $yearTotal    = (float) $yearExpenses->sum('total_amount');
                $expenseCount = $yearExpenses->count();

                $trendData[$yearItem] = [
                    'year'          => $yearItem,
                    'total'         => $yearTotal,
                    'expense_count' => $expenseCount,
                    'average'       => $expenseCount > 0 ? $yearTotal / $expenseCount : 0,
                    'tax_total'     => (float) $yearExpenses->sum('tax_amount'),
                ];
            }
        }

        // ── Filter options ──────────────────────────────────────────
        $categories = ExpenseCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $locationsQuery = \App\Models\Location::where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->orderBy('name');

        if (!$isAdmin) {
            $locationsQuery->whereIn('id', $userLocationIds);
        }

        $locations = $locationsQuery->get(['id', 'name']);
        $years     = range(date('Y') - 5, date('Y'));

        return view('reports.expenses.trends', compact(
            'trendData',
            'period',
            'year',
            'categoryId',
            'categories',
            'locations',
            'years',
            'categoryTrends',
            'movingAverages',
            'momGrowth',
            'locationId'
        ));
    }
            
    public function taxReport(Request $request)
    {
        $user     = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('expense reports')) {
            abort(403, __('payments.not_authorized'));
        }
        if (!tenant_can('expense_reports')) {
            abort(403, __('payments.feature_not_available_in_plan'));
        }

        // ── Visibility rule ─────────────────────────────────────────
        $isAdmin         = $user->hasAnyRole(['super_admin', 'admin']);
        $userLocationIds = $user->locations()->pluck('locations.id')->toArray();

        // ── Date range ──────────────────────────────────────────────
        $startInput = $request->get('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endInput   = $request->get('end_date',   Carbon::now()->endOfMonth()->format('Y-m-d'));

        try {
            $startDate = Carbon::parse($startInput)->format('Y-m-d');
            $endDate   = Carbon::parse($endInput)->format('Y-m-d');
            if ($startDate > $endDate) {
                [$startDate, $endDate] = [$endDate, $startDate];
            }
        } catch (\Throwable $e) {
            $startDate = Carbon::now()->startOfYear()->format('Y-m-d');
            $endDate   = Carbon::now()->endOfMonth()->format('Y-m-d');
        }

        // ── Filters ─────────────────────────────────────────────────
        $categoryId = $request->get('category_id');
        $taxType    = $request->get('tax_type');   // 'all', 'taxable', 'non-taxable'
        $locationId = $request->get('location_id');

        $locationFilter = null;
        if (!empty($locationId) && is_numeric($locationId)) {
            $req = (int) $locationId;
            if ($isAdmin || in_array($req, $userLocationIds, true)) {
                $locationFilter = $req;
            }
        }

        // ── Base query ──────────────────────────────────────────────
        $baseQuery = Expense::query()
            ->where('tenant_id', $tenantId)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate);

        if ($categoryId && is_numeric($categoryId)) {
            $baseQuery->where('category_id', (int) $categoryId);
        }
        if ($taxType === 'taxable') {
            $baseQuery->where('tax_amount', '>', 0);
        } elseif ($taxType === 'non-taxable') {
            $baseQuery->where('tax_amount', '=', 0);
        }
        if ($locationFilter) {
            $baseQuery->where('location_id', $locationFilter);
        }

        // ── Data ────────────────────────────────────────────────────
        $allExpenses = (clone $baseQuery)
            ->with(['category', 'paymentMethod', 'location'])
            ->get();

        $taxableExpenses    = $allExpenses->where('tax_amount', '>', 0);
        $nonTaxableExpenses = $allExpenses->where('tax_amount', '=', 0);

        $totalGross = (float) $allExpenses->sum('gross_amount');
        $totalTax   = (float) $allExpenses->sum('tax_amount');
        $totalNet   = (float) $allExpenses->sum('net_amount');

        $taxSummary = [
            'total_expenses'          => $allExpenses->count(),
            'total_gross'             => $totalGross,
            'total_tax'               => $totalTax,
            'total_net'               => $totalNet,
            'taxable_expenses'        => $taxableExpenses->count(),
            'non_taxable_expenses'    => $nonTaxableExpenses->count(),
            'avg_tax_rate'            => $totalGross > 0 ? ($totalTax / $totalGross) * 100 : 0,
            'tax_percentage_of_gross' => $totalGross > 0 ? ($totalTax / $totalGross) * 100 : 0,
            'withholding_impact'      => $totalGross > 0 ? (($totalGross - $totalNet) / $totalGross) * 100 : 0,
        ];

        // Tax by category
        $taxByCategory = $allExpenses->groupBy('category_id')
            ->map(function ($expenses) {
                $category       = $expenses->first()->category;
                $taxableInCat   = $expenses->where('tax_amount', '>', 0);
                $grossAmount    = (float) $expenses->sum('gross_amount');
                $taxAmount      = (float) $expenses->sum('tax_amount');
                $netAmount      = (float) $expenses->sum('net_amount');

                return (object) [
                    'category_name'     => $category->name ?? 'Uncategorized',
                    'expense_count'     => $expenses->count(),
                    'gross_amount'      => $grossAmount,
                    'tax_amount'        => $taxAmount,
                    'net_amount'        => $netAmount,
                    'avg_tax_rate'      => $grossAmount > 0 ? ($taxAmount / $grossAmount) * 100 : 0,
                    'taxable_count'     => $taxableInCat->count(),
                    'non_taxable_count' => $expenses->count() - $taxableInCat->count(),
                ];
            })
            ->sortByDesc('tax_amount')
            ->values();

        // Monthly tax breakdown
        $monthlyTax = $allExpenses
            ->groupBy(fn($expense) => $expense->date->format('Y-m'))
            ->map(function ($expenses, $monthKey) {
                [$year, $month] = explode('-', $monthKey);
                $date        = Carbon::createFromDate((int) $year, (int) $month, 1);
                $grossAmount = (float) $expenses->sum('gross_amount');
                $taxAmount   = (float) $expenses->sum('tax_amount');
                $netAmount   = (float) $expenses->sum('net_amount');

                return (object) [
                    'year'          => (int) $year,
                    'month'         => (int) $month,
                    'month_name'    => $date->format('F Y'),
                    'expense_count' => $expenses->count(),
                    'gross_amount'  => $grossAmount,
                    'tax_amount'    => $taxAmount,
                    'net_amount'    => $netAmount,
                    'avg_tax_rate'  => $grossAmount > 0 ? ($taxAmount / $grossAmount) * 100 : 0,
                ];
            })
            ->sortByDesc(fn($item) => $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT))
            ->values();

        // Top tax expenses
        $topTaxExpenses = $allExpenses->where('tax_amount', '>', 0)
            ->sortByDesc('tax_amount')
            ->take(20)
            ->values()
            ->map(function ($expense) {
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
            ->groupBy(fn($expense) => round(($expense->tax_amount / $expense->gross_amount) * 100, 0))
            ->map(function ($expenses, $rate) {
                $totalTax = (float) $expenses->sum('tax_amount');

                return (object) [
                    'tax_rate_percent' => (int) $rate,
                    'expense_count'    => $expenses->count(),
                    'gross_amount'     => (float) $expenses->sum('gross_amount'),
                    'total_tax'        => $totalTax,
                    'percentage'       => 0, // recalculated in view
                ];
            })
            ->sortKeys()
            ->values();

        // ── Filter options ──────────────────────────────────────────
        $categories = ExpenseCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $locationsQuery = \App\Models\Location::where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->orderBy('name');

        if (!$isAdmin) {
            $locationsQuery->whereIn('id', $userLocationIds);
        }

        $locations = $locationsQuery->get(['id', 'name']);

        return view('reports.expenses.tax-report', compact(
            'taxSummary',
            'taxByCategory',
            'monthlyTax',
            'topTaxExpenses',
            'taxRateDistribution',
            'categories',
            'locations',
            'startDate',
            'endDate',
            'categoryId',
            'taxType',
            'locationId'
        ));
    }
        
    public function audit(Request $request)
    {
        $user     = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('expense reports')) {
            abort(403, __('payments.not_authorized'));
        }
        if (!tenant_can('expense_reports')) {
            abort(403, __('payments.feature_not_available_in_plan'));
        }

        // ── Visibility rule ─────────────────────────────────────────
        $isAdmin         = $user->hasAnyRole(['super_admin', 'admin']);
        $userLocationIds = $user->locations()->pluck('locations.id')->toArray();

        // ── Date range ──────────────────────────────────────────────
        $startInput = $request->get('start_date', Carbon::now()->subMonths(3)->format('Y-m-d'));
        $endInput   = $request->get('end_date',   Carbon::now()->format('Y-m-d'));

        try {
            $startDate = Carbon::parse($startInput)->format('Y-m-d');
            $endDate   = Carbon::parse($endInput)->format('Y-m-d');
            if ($startDate > $endDate) {
                [$startDate, $endDate] = [$endDate, $startDate];
            }
        } catch (\Throwable $e) {
            $startDate = Carbon::now()->subMonths(3)->format('Y-m-d');
            $endDate   = Carbon::now()->format('Y-m-d');
        }

        // ── Filters ─────────────────────────────────────────────────
        $auditType  = $request->get('audit_type', 'all');
        $employeeId = $request->get('employee_id');
        $threshold  = (float) $request->get('threshold', 1000);
        $locationId = $request->get('location_id');

        $locationFilter = null;
        if (!empty($locationId) && is_numeric($locationId)) {
            $req = (int) $locationId;
            if ($isAdmin || in_array($req, $userLocationIds, true)) {
                $locationFilter = $req;
            }
        }

        // ── Base query ──────────────────────────────────────────────
        $baseQuery = Expense::query()
            ->where('tenant_id', $tenantId)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate);

        if ($locationFilter) {
            $baseQuery->where('location_id', $locationFilter);
        }

        if ($employeeId && is_numeric($employeeId)) {
            $baseQuery->where('employee_id', (int) $employeeId);
        }

        // Audit-type filter
        $auditQuery = (clone $baseQuery);
        switch ($auditType) {
            case 'missing_receipts':
            case 'policy_violations':
                $auditQuery->whereHas('category', fn($q) => $q->where('requires_receipt', true))
                    ->where(function ($q) {
                        $q->whereNull('receipt_url')->orWhere('receipt_url', '');
                    });
                break;

            case 'unapproved':
                $auditQuery->whereHas('category', fn($q) => $q->where('requires_approval', true))
                    ->whereNull('approved_at');
                break;

            case 'high_value':
                $auditQuery->where('total_amount', '>=', $threshold);
                break;

            case 'late_submissions':
                $auditQuery->whereRaw('DATEDIFF(created_at, `date`) > 7');
                break;

            // 'all' → no additional filter
        }

        // ── Data ────────────────────────────────────────────────────
        $auditItems = (clone $auditQuery)
            ->with(['category', 'paymentMethod', 'employee', 'approver', 'location'])
            ->orderByDesc('date')
            ->get();

        // ── Stats ───────────────────────────────────────────────────
        $auditStats = [
            'total_items'      => $auditItems->count(),
            'total_amount'     => (float) $auditItems->sum('total_amount'),
            'missing_receipts' => $auditItems->filter(function ($item) {
                return $item->category
                    && $item->category->requires_receipt
                    && empty($item->receipt_url);
            })->count(),
            'unapproved'       => $auditItems->filter(function ($item) {
                return $item->category
                    && $item->category->requires_approval
                    && !$item->approved_at;
            })->count(),
            'high_value'       => $auditItems->filter(fn($item) => $item->total_amount >= $threshold)->count(),
            'average_age_days' => $auditItems->avg(function ($item) {
                return Carbon::parse($item->created_at)->diffInDays(Carbon::today());
            }) ?? 0,
        ];

        // ── Category breakdown ──────────────────────────────────────
        $byCategory = $auditItems->groupBy(fn($item) => $item->category?->name ?? 'Uncategorized')
            ->map(function ($items, $category) {
                return [
                    'category'         => $category,
                    'count'            => $items->count(),
                    'total_amount'     => (float) $items->sum('total_amount'),
                    'avg_amount'       => (float) ($items->avg('total_amount') ?? 0),
                    'missing_receipts' => $items->filter(function ($item) {
                        return $item->category
                            && $item->category->requires_receipt
                            && empty($item->receipt_url);
                    })->count(),
                    'unapproved'       => $items->filter(function ($item) {
                        return $item->category
                            && $item->category->requires_approval
                            && !$item->approved_at;
                    })->count(),
                ];
            })
            ->sortByDesc('count')
            ->values();

        // ── Monthly audit trend ─────────────────────────────────────
        // Always computed from the base query (ignore audit-type filter)
        // so the trend shows all activity, not just the filtered subset.
        $monthlyAudit = (clone $baseQuery)
            ->with('category')
            ->get()
            ->groupBy(fn($expense) => $expense->date->format('Y-m'))
            ->map(function ($expenses, $monthKey) {
                [$year, $month] = explode('-', $monthKey);

                $missingReceipts = $expenses->filter(function ($item) {
                    return $item->category
                        && $item->category->requires_receipt
                        && empty($item->receipt_url);
                })->count();

                $unapproved = $expenses->filter(function ($item) {
                    return $item->category
                        && $item->category->requires_approval
                        && !$item->approved_at;
                })->count();

                return (object) [
                    'year'             => (int) $year,
                    'month'            => (int) $month,
                    'total_expenses'   => $expenses->count(),
                    'missing_receipts' => $missingReceipts,
                    'unapproved'       => $unapproved,
                    'monthly_total'    => (float) $expenses->sum('total_amount'),
                ];
            })
            ->sortKeysDesc()
            ->values();

        // ── Employee compliance ─────────────────────────────────────
        $employeeCompliance = (clone $baseQuery)
            ->whereNotNull('employee_id')
            ->with(['employee', 'category'])
            ->get()
            ->groupBy('employee_id')
            ->map(function ($expenses, $empId) {
                $employee = $expenses->first()->employee;

                return (object) [
                    'id'               => $empId,
                    'first_name'       => $employee->first_name ?? '',
                    'last_name'        => $employee->last_name ?? '',
                    'email'            => $employee->email ?? '',
                    'employee_name'    => trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? '')),
                    'total_expenses'   => $expenses->count(),
                    'missing_receipts' => $expenses->filter(function ($item) {
                        return $item->category
                            && $item->category->requires_receipt
                            && empty($item->receipt_url);
                    })->count(),
                    'unapproved'       => $expenses->filter(function ($item) {
                        return $item->category
                            && $item->category->requires_approval
                            && !$item->approved_at;
                    })->count(),
                    'avg_expense'      => (float) ($expenses->avg('total_amount') ?? 0),
                ];
            })
            ->sortByDesc('missing_receipts')
            ->values();

        // ── Filter options ──────────────────────────────────────────
        $employees = Employee::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

        $locationsQuery = \App\Models\Location::where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->orderBy('name');

        if (!$isAdmin) {
            $locationsQuery->whereIn('id', $userLocationIds);
        }

        $locations = $locationsQuery->get(['id', 'name']);

        $auditTypes = [
            'all'               => __('accounting.all_items'),
            'missing_receipts'  => __('accounting.missing_receipts'),
            'unapproved'        => __('accounting.unapproved_expenses'),
            'high_value'        => __('accounting.high_value_expenses'),
            'late_submissions'  => __('accounting.late_submissions'),
            'policy_violations' => __('accounting.policy_violations'),
        ];

        return view('reports.expenses.audit', compact(
            'auditItems',
            'auditStats',
            'byCategory',
            'monthlyAudit',
            'employeeCompliance',
            'employees',
            'locations',
            'startDate',
            'endDate',
            'auditType',
            'employeeId',
            'locationId',
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
        if (!tenant_can('expense_reports')) {
            abort(403, __('payments.feature_not_available_in_plan'));
        }
        $data = $this->byCategory($request);
        // Export logic here
        return response()->streamDownload(function() use ($data) {
            // CSV export implementation
        }, 'expense-by-category-' . date('Y-m-d') . '.csv');
    }

}
