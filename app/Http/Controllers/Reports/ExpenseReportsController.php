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

        return view('reports.expenses.summary', [
            'initialFilters' => [
                'start_date'       => $request->get('start_date', now()->startOfMonth()->format('Y-m-d')),
                'end_date'         => $request->get('end_date',   now()->endOfMonth()->format('Y-m-d')),
                'category_id'      => $request->get('category_id', ''),
                'vendor_name'      => $request->get('vendor_name', ''),
                'payment_method_id'=> $request->get('payment_method_id', ''),
                'payment_status'   => $request->get('payment_status', ''),
                'employee_id'      => $request->get('employee_id', ''),
                'requires_receipt' => $request->get('requires_receipt', ''),
                'is_recurring'     => $request->get('is_recurring', ''),
                'location_id'      => $request->get('location_id', ''),
            ],
        ]);
    }
    

    public function summaryData(Request $request)
    {
        $user     = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('expense reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('expense_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }

        // ── Visibility rule ──────────────────────────────────────
        $isAdmin         = $user->hasAnyRole(['super_admin', 'admin']);
        $userLocationIds = $user->locations()->pluck('locations.id')->toArray();

        // ── Date range ───────────────────────────────────────────
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

        // ── Filters ──────────────────────────────────────────────
        $categoryId      = $request->input('category_id');
        $vendorName      = trim((string) $request->input('vendor_name', ''));
        $paymentMethodId = $request->input('payment_method_id');
        $paymentStatus   = $request->input('payment_status');
        $employeeId      = $request->input('employee_id');
        $requiresReceipt = $request->input('requires_receipt');
        $isRecurring     = $request->input('is_recurring');
        $locationId      = $request->input('location_id');

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
        if (!empty($locationId) && is_numeric($locationId)) {
            $requested = (int) $locationId;
            if ($isAdmin || in_array($requested, $userLocationIds, true)) {
                $baseQuery->where('location_id', $requested);
            }
        }

        // ── Data ────────────────────────────────────────────────
        $expenses = (clone $baseQuery)
            ->with([
                'category:id,name',
                'paymentMethod:id,name,is_default',
                'employee:id,first_name,last_name',          // ← drop employee_id
                'supplier:id,name',
                'location:id,name',
            ])
            ->get();

        $totalAmount = (float) $expenses->sum('total_amount');
        $totalTax    = (float) $expenses->sum('tax_amount');

        $summary = [
            'total_expenses' => $expenses->count(),
            'total_amount'   => $totalAmount,
            'total_tax'      => $totalTax,
            'avg_expense'    => $expenses->count() > 0 ? $totalAmount / $expenses->count() : 0,
            'max_expense'    => (float) ($expenses->max('total_amount') ?? 0),
            'min_expense'    => (float) ($expenses->min('total_amount') ?? 0),
        ];

        // Daily
        $dailyBreakdown = $expenses
            ->groupBy(fn($e) => $e->date->format('Y-m-d'))
            ->map(fn($group, $date) => [
                'date'    => $date,
                'count'   => $group->count(),
                'total'   => (float) $group->sum('total_amount'),
                'tax'     => (float) $group->sum('tax_amount'),
                'average' => $group->count() > 0 ? (float) $group->sum('total_amount') / $group->count() : 0,
            ])
            ->sortByDesc('date')
            ->values();

        // Top 10
        $topExpenses = $expenses
            ->sortByDesc('total_amount')
            ->take(10)
            ->values()
            ->map(fn($e) => $this->expenseRowPayload($e));

        return response()->json([
            'success'    => true,
            'summary'    => $summary,
            'daily'      => $dailyBreakdown,
            'top'        => $topExpenses,
            'currency'   => currency_symbol(),
            'meta'       => [
                'start_date' => $startDate,
                'end_date'   => $endDate,
            ],
        ]);
    }

    public function summaryMeta(Request $request)
    {
        $user     = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('expense reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('expense_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }

        $isAdmin         = $user->hasAnyRole(['super_admin', 'admin']);
        $userLocationIds = $user->locations()->pluck('locations.id')->toArray();

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

        $locationsQuery = Location::where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->orderBy('name');

        if (!$isAdmin) {
            $locationsQuery->whereIn('id', $userLocationIds);
        }
        $locations = $locationsQuery->get(['id', 'name']);

        return response()->json([
            'success'         => true,
            'categories'      => $categories,
            'payment_methods' => $paymentMethods,
            'employees'       => $employees,
            'locations'       => $locations,
            'currency'        => currency_symbol(),
        ]);
    }

    /**
     * Compact per-expense payload for the detail modal.
     */
    private function expenseRowPayload(Expense $e): array
    {
        return [
            'id'                  => $e->id,
            'expense_number'      => $e->expense_number,
            'date'                => $e->date?->format('Y-m-d'),
            'description'         => $e->description,
            'vendor_name'         => $e->vendor_name,
            'category'            => $e->category?->name,
            'amount'              => (float) $e->total_amount,
            'gross_amount'        => (float) $e->gross_amount,
            'tax_amount'          => (float) $e->tax_amount,
            'net_amount'          => (float) $e->net_amount,
            'status'              => $e->payment_status,
            'paid_date'           => $e->paid_date?->format('Y-m-d'),
            'payment_method'      => $e->paymentMethod?->name,
            'employee'            => $e->employee
                ? trim($e->employee->first_name . ' ' . $e->employee->last_name)
                : null,
            'employee_code'       => $e->employee?->employee_id,
            'is_recurring'        => (bool) $e->is_recurring,
            'recurring_frequency' => $e->recurring_frequency,
            'receipt_url'         => $e->receipt_url,
        ];
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

        return view('reports.expenses.by-category', [
            'initialFilters' => [
                'start_date'  => $request->get('start_date', Carbon::now()->startOfYear()->format('Y-m-d')),
                'end_date'    => $request->get('end_date',   Carbon::now()->endOfMonth()->format('Y-m-d')),
                'category_id' => $request->get('category_id', ''),
                'location_id' => $request->get('location_id', ''),
            ],
        ]);
    }

    public function byCategoryData(Request $request)
    {
        $user     = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('expense reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('expense_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }

        $isAdmin         = $user->hasAnyRole(['super_admin', 'admin']);
        $userLocationIds = $user->locations()->pluck('locations.id')->toArray();

        $startInput = $request->get('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endInput   = $request->get('end_date',   Carbon::now()->endOfMonth()->format('Y-m-d'));

        try {
            $startDate = Carbon::parse($startInput)->format('Y-m-d');
            $endDate   = Carbon::parse($endInput)->format('Y-m-d');
            if ($startDate > $endDate) [$startDate, $endDate] = [$endDate, $startDate];
        } catch (\Throwable $e) {
            $startDate = Carbon::now()->startOfYear()->format('Y-m-d');
            $endDate   = Carbon::now()->endOfMonth()->format('Y-m-d');
        }

        $categoryId = $request->get('category_id');
        $locationId = $request->get('location_id');

        $locationFilter = null;
        if (!empty($locationId) && is_numeric($locationId)) {
            $req = (int) $locationId;
            if ($isAdmin || in_array($req, $userLocationIds, true)) $locationFilter = $req;
        }

        $baseQuery = Expense::query()
            ->where('tenant_id', $tenantId)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->when($categoryId && is_numeric($categoryId), fn($q) => $q->where('category_id', (int) $categoryId))
            ->when($locationFilter, fn($q) => $q->where('location_id', $locationFilter));

        // ── Category breakdown ─────────────────────────────────────
        $expenses = (clone $baseQuery)->with('category:id,name,code')->get();

        $categoryBreakdown = $expenses
            ->groupBy('category_id')
            ->map(function ($group) {
                $category   = $group->first()->category;
                $count      = $group->count();
                $grandTotal = (float) $group->sum('total_amount');

                return [
                    'category_name'  => $category->name ?? 'Uncategorized',
                    'category_code'  => $category->code ?? 'N/A',
                    'expense_count'  => $count,
                    'total_amount'   => $grandTotal,
                    'total_tax'      => (float) $group->sum('tax_amount'),
                    'grand_total'    => $grandTotal,
                    'average_amount' => $count > 0 ? $grandTotal / $count : 0,
                    'max_amount'     => (float) ($group->max('total_amount') ?? 0),
                    'min_amount'     => (float) ($group->min('total_amount') ?? 0),
                ];
            })
            ->sortByDesc('grand_total')
            ->values();

        $totalExpenses = (float) $categoryBreakdown->sum('grand_total');

        // ── Monthly trend by category ──────────────────────────────
        $monthlyData = $expenses->groupBy(fn($e) => $e->date->format('Y-m'));

        $monthlyTrend = collect();
        foreach ($monthlyData as $month => $expensesInMonth) {
            foreach ($expensesInMonth->groupBy('category_id') as $catId => $categoryExpenses) {
                $category = $categoryExpenses->first()->category;
                $monthlyTrend->push([
                    'year'          => (int) date('Y', strtotime($month)),
                    'month'         => (int) date('n', strtotime($month)),
                    'category_name' => $category->name ?? 'Uncategorized',
                    'monthly_total' => (float) $categoryExpenses->sum('total_amount'),
                ]);
            }
        }

        $monthlyTrendGrouped = $monthlyTrend->groupBy('category_name');

        // Unique months sorted
        $uniqueMonths = $monthlyTrend
            ->groupBy(fn($item) => $item['year'] . '-' . str_pad($item['month'], 2, '0', STR_PAD_LEFT))
            ->keys()
            ->sort()
            ->map(fn($key) => [
                'year'  => (int) substr($key, 0, 4),
                'month' => (int) substr($key, 5, 2),
                'label' => date('M Y', strtotime($key . '-01')),
                'key'   => $key,
            ])
            ->values();

        // Matrix + totals
        $monthlyDataMatrix = [];
        foreach ($monthlyTrendGrouped as $categoryName => $categoryData) {
            foreach ($categoryData as $item) {
                $monthKey = $item['year'] . '-' . str_pad($item['month'], 2, '0', STR_PAD_LEFT);
                $monthlyDataMatrix[$monthKey][$categoryName] = $item['monthly_total'];
            }
        }

        $categoryTotals = [];
        foreach ($categoryBreakdown as $c) {
            $categoryTotals[$c['category_name']] = $c['grand_total'];
        }

        // Summary cards values
        $summary = [
            'category_count' => $categoryBreakdown->count(),
            'total_expenses' => $categoryBreakdown->sum('expense_count'),
            'grand_total'    => $totalExpenses,
            'total_tax'      => $categoryBreakdown->sum('total_tax'),
            'avg_amount'     => $categoryBreakdown->avg('average_amount') ?? 0,
            'top_category'   => $categoryBreakdown->first()['category_name'] ?? 'N/A',
        ];

        return response()->json([
            'success'             => true,
            'currency'            => currency_symbol(),
            'summary'             => $summary,
            'categories'          => $categoryBreakdown,
            'chart'               => [
                'category_names' => $monthlyTrendGrouped->keys()->values(),
                'months'         => $uniqueMonths,
                'matrix'         => $monthlyDataMatrix,
                'totals'         => $categoryTotals,
            ],
            'meta' => [
                'start_date' => $startDate,
                'end_date'   => $endDate,
            ],
        ]);
    }

    public function byCategoryMeta(Request $request)
    {
        $user     = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('expense reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('expense_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }

        $isAdmin         = $user->hasAnyRole(['super_admin', 'admin']);
        $userLocationIds = $user->locations()->pluck('locations.id')->toArray();

        $categories = ExpenseCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $locationsQuery = Location::where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->orderBy('name');
        if (!$isAdmin) $locationsQuery->whereIn('id', $userLocationIds);
        $locations = $locationsQuery->get(['id', 'name']);

        return response()->json([
            'success'    => true,
            'categories' => $categories,
            'locations'  => $locations,
            'currency'   => currency_symbol(),
        ]);
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

        return view('reports.expenses.by-vendor', [
            'initialFilters' => [
                'start_date'       => $request->get('start_date', Carbon::now()->startOfYear()->format('Y-m-d')),
                'end_date'         => $request->get('end_date',   Carbon::now()->endOfMonth()->format('Y-m-d')),
                'vendor_name'      => $request->get('vendor_name', ''),
                'category_id'      => $request->get('category_id', ''),
                'payment_method_id'=> $request->get('payment_method_id', ''),
                'location_id'      => $request->get('location_id', ''),
                'min_amount'       => $request->get('min_amount', ''),
                'max_amount'       => $request->get('max_amount', ''),
            ],
        ]);
    }
        
    public function byVendorData(Request $request)
    {
        $user     = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('expense reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('expense_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }

        $isAdmin         = $user->hasAnyRole(['super_admin', 'admin']);
        $userLocationIds = $user->locations()->pluck('locations.id')->toArray();

        $startInput = $request->get('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endInput   = $request->get('end_date',   Carbon::now()->endOfMonth()->format('Y-m-d'));

        try {
            $startDate = Carbon::parse($startInput)->format('Y-m-d');
            $endDate   = Carbon::parse($endInput)->format('Y-m-d');
            if ($startDate > $endDate) [$startDate, $endDate] = [$endDate, $startDate];
        } catch (\Throwable $e) {
            $startDate = Carbon::now()->startOfYear()->format('Y-m-d');
            $endDate   = Carbon::now()->endOfMonth()->format('Y-m-d');
        }

        $vendorName      = $request->get('vendor_name');
        $categoryId      = $request->get('category_id');
        $paymentMethodId = $request->get('payment_method_id');
        $minAmount       = $request->get('min_amount');
        $maxAmount       = $request->get('max_amount');
        $locationId      = $request->get('location_id');

        $locationFilter = null;
        if (!empty($locationId) && is_numeric($locationId)) {
            $req = (int) $locationId;
            if ($isAdmin || in_array($req, $userLocationIds, true)) $locationFilter = $req;
        }

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

        $allExpenses = (clone $baseQuery)
            ->with(['category:id,name', 'paymentMethod:id,name,type', 'location:id,name'])
            ->get();

        // Vendor breakdown
        $vendorBreakdown = $allExpenses->groupBy('vendor_name')
            ->map(function ($expenses, $vendorName) {
                $count      = $expenses->count();
                $grandTotal = (float) $expenses->sum('total_amount');

                return [
                    'vendor_name'          => $vendorName,
                    'transaction_count'    => $count,
                    'total_amount'         => $grandTotal,
                    'total_tax'            => (float) $expenses->sum('tax_amount'),
                    'grand_total'          => $grandTotal,
                    'average_transaction'  => $count > 0 ? $grandTotal / $count : 0,
                    'largest_transaction'  => (float) ($expenses->max('total_amount') ?? 0),
                    'smallest_transaction' => (float) ($expenses->min('total_amount') ?? 0),
                    'categories_used'      => $expenses->pluck('category_id')->unique()->count(),
                ];
            })
            ->sortByDesc('grand_total')
            ->values();

        // Vendor × payment method
        $vendorPaymentMethods = $allExpenses->groupBy('vendor_name')
            ->map(function ($expenses, $vendorName) {
                return $expenses->groupBy(fn($e) => $e->paymentMethod->name ?? 'Unknown')
                    ->map(function ($grouped, $methodName) use ($vendorName) {
                        return [
                            'vendor_name'    => $vendorName,
                            'payment_method' => $methodName,
                            'payment_type'   => $grouped->first()->paymentMethod->type ?? 'unknown',
                            'count'          => $grouped->count(),
                            'total'          => (float) $grouped->sum('total_amount'),
                        ];
                    })
                    ->values();
            })
            ->filter()
            ->flatMap(fn($items) => $items)
            ->values();

        // Monthly activity
        $monthlyVendorActivity = $allExpenses->groupBy(fn($e) => $e->date->format('Y-m'))
            ->flatMap(function ($expensesInMonth, $monthKey) {
                [$year, $month] = explode('-', $monthKey);
                return $expensesInMonth->groupBy('vendor_name')
                    ->map(function ($vendorExpenses, $vendorName) use ($year, $month) {
                        return [
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
            ->sortByDesc(fn($item) => $item['year'] . '-' . str_pad($item['month'], 2, '0', STR_PAD_LEFT))
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
                ->where('is_active', true)->count(),
        ];

        // Chart data — top 5 vendors × month matrix (chronological)
        $topVendors = $vendorBreakdown->take(5)->pluck('vendor_name')->toArray();

        $monthKeys = $monthlyVendorActivity
            ->map(fn($r) => $r['year'] . '-' . str_pad($r['month'], 2, '0', STR_PAD_LEFT))
            ->unique()
            ->sort()
            ->values();

        $chartMonths = $monthKeys->map(function ($key) {
            [$y, $m] = explode('-', $key);
            return [
                'key'   => $key,
                'year'  => (int) $y,
                'month' => (int) $m,
                'label' => date('M y', strtotime($key . '-01')),
            ];
        });

        $chartMatrix = [];
        foreach ($monthlyVendorActivity as $row) {
            $key = $row['year'] . '-' . str_pad($row['month'], 2, '0', STR_PAD_LEFT);
            $chartMatrix[$key][$row['vendor_name']] = $row['monthly_total'];
        }

        // Monthly top vendors (first per month in the sorted list)
        $monthlyTopVendors = $monthlyVendorActivity
            ->groupBy(fn($r) => $r['year'] . '-' . str_pad($r['month'], 2, '0', STR_PAD_LEFT))
            ->take(12)
            ->map(function ($group, $key) {
                $top = $group->first();
                return [
                    'month_key'         => $key,
                    'label'             => date('M Y', strtotime($key . '-01')),
                    'vendor_name'       => $top['vendor_name'],
                    'transaction_count' => $top['transaction_count'],
                    'monthly_total'     => $top['monthly_total'],
                    'monthly_average'   => $top['monthly_average'],
                ];
            })
            ->values();

        return response()->json([
            'success'                => true,
            'currency'               => currency_symbol(),
            'summary'                => $summary,
            'vendors'                => $vendorBreakdown,
            'vendor_payment_methods' => $vendorPaymentMethods,
            'monthly_top'            => $monthlyTopVendors,
            'chart'                  => [
                'vendors' => $topVendors,
                'months'  => $chartMonths,
                'matrix'  => $chartMatrix,
            ],
            'meta' => [
                'start_date' => $startDate,
                'end_date'   => $endDate,
            ],
        ]);
    }

    public function byVendorMeta(Request $request)
    {
        $user     = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('expense reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('expense_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }

        $isAdmin         = $user->hasAnyRole(['super_admin', 'admin']);
        $userLocationIds = $user->locations()->pluck('locations.id')->toArray();

        $categories = ExpenseCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)->orderBy('name')
            ->get(['id', 'name']);

        $paymentMethods = PaymentMethod::where('tenant_id', $tenantId)
            ->where('is_active', true)->orderBy('name')
            ->get(['id', 'name', 'is_default', 'type']);

        $locationsQuery = Location::where('tenant_id', $tenantId)
            ->where('is_active', 1)->orderBy('name');
        if (!$isAdmin) $locationsQuery->whereIn('id', $userLocationIds);
        $locations = $locationsQuery->get(['id', 'name']);

        return response()->json([
            'success'         => true,
            'categories'      => $categories,
            'payment_methods' => $paymentMethods,
            'locations'       => $locations,
            'currency'        => currency_symbol(),
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

        return view('reports.expenses.by-employee', [
            'initialFilters' => [
                'start_date'        => $request->get('start_date', Carbon::now()->startOfYear()->format('Y-m-d')),
                'end_date'          => $request->get('end_date',   Carbon::now()->endOfMonth()->format('Y-m-d')),
                'employee_id'       => $request->get('employee_id', ''),
                'location_id'       => $request->get('location_id', ''),
                'requires_approval' => $request->get('requires_approval', ''),
            ],
        ]);
    }

    public function byEmployeeData(Request $request)
    {
        $user     = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('expense reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('expense_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }

        $isAdmin         = $user->hasAnyRole(['super_admin', 'admin']);
        $userLocationIds = $user->locations()->pluck('locations.id')->toArray();

        $startInput = $request->get('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endInput   = $request->get('end_date',   Carbon::now()->endOfMonth()->format('Y-m-d'));

        try {
            $startDate = Carbon::parse($startInput)->format('Y-m-d');
            $endDate   = Carbon::parse($endInput)->format('Y-m-d');
            if ($startDate > $endDate) [$startDate, $endDate] = [$endDate, $startDate];
        } catch (\Throwable $e) {
            $startDate = Carbon::now()->startOfYear()->format('Y-m-d');
            $endDate   = Carbon::now()->endOfMonth()->format('Y-m-d');
        }

        $employeeId       = $request->get('employee_id');
        $requiresApproval = $request->get('requires_approval');
        $locationId       = $request->get('location_id');

        $locationFilter = null;
        if (!empty($locationId) && is_numeric($locationId)) {
            $req = (int) $locationId;
            if ($isAdmin || in_array($req, $userLocationIds, true)) $locationFilter = $req;
        }

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

        $allExpenses = (clone $baseQuery)
            ->with(['employee:id,first_name,last_name,department_id', 'employee.department:id,name', 'category:id,name'])
            ->get();

        // ── Employee breakdown ────────────────────────────────────
        $employeeBreakdown = $allExpenses->groupBy('employee_id')
            ->map(function ($expenses, $empId) {
                $employee   = $expenses->first()->employee;
                $count      = $expenses->count();
                $grandTotal = (float) $expenses->sum('total_amount');

                return [
                    'employee_id'      => $empId,
                    'first_name'       => $employee->first_name ?? 'Unknown',
                    'last_name'        => $employee->last_name  ?? '',
                    'employee_name'    => trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? '')),
                    'department'       => $employee->department->name ?? 'No Department',
                    'expense_count'    => $count,
                    'total_amount'     => $grandTotal,
                    'total_tax'        => (float) $expenses->sum('tax_amount'),
                    'grand_total'      => $grandTotal,
                    'average_expense'  => $count > 0 ? $grandTotal / $count : 0,
                    'max_expense'      => (float) ($expenses->max('total_amount') ?? 0),
                    'pending_count'    => $expenses->where('payment_status', 'pending')->count(),
                    'paid_count'       => $expenses->where('payment_status', 'paid')->count(),
                    'reimbursed_count' => $expenses->where('payment_status', 'reimbursed')->count(),
                ];
            })
            ->sortByDesc('grand_total')
            ->values();

        // ── Monthly spending matrix ───────────────────────────────
        $monthlyRaw = $allExpenses
            ->groupBy(fn($e) => $e->date->format('Y-m'))
            ->flatMap(function ($expensesInMonth, $monthKey) {
                [$year, $month] = explode('-', $monthKey);
                return $expensesInMonth->groupBy('employee_id')
                    ->map(function ($empExpenses) use ($year, $month) {
                        $employee = $empExpenses->first()->employee;
                        return [
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

        // ── Categories by employee ────────────────────────────────
        $employeeCategoriesRaw = $allExpenses->groupBy('employee_id')
            ->flatMap(function ($empExpenses) {
                $employee     = $empExpenses->first()->employee;
                $employeeName = trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? ''));
                return $empExpenses->groupBy('category_id')
                    ->map(function ($catExpenses) use ($employeeName) {
                        $category = $catExpenses->first()->category;
                        return [
                            'employee_name' => $employeeName,
                            'category_name' => $category->name ?? 'Uncategorized',
                            'count'         => $catExpenses->count(),
                            'total'         => (float) $catExpenses->sum('total_amount'),
                        ];
                    })
                    ->values();
            })
            ->values();

        // ── Summary ───────────────────────────────────────────────
        $summary = [
            'total_employees'  => $employeeBreakdown->count(),
            'total_expenses'   => $allExpenses->count(),
            'total_amount'     => (float) $allExpenses->sum('total_amount'),
            'total_tax'        => (float) $allExpenses->sum('tax_amount'),
            'avg_per_employee' => $employeeBreakdown->count() > 0
                ? (float) $employeeBreakdown->sum('grand_total') / $employeeBreakdown->count()
                : 0,
            'largest_expense'  => (float) ($allExpenses->max('total_amount') ?? 0),
            'top_employee'     => $employeeBreakdown->first()['employee_name'] ?? 'N/A',
        ];

        // Totals for table footer
        $totals = [
            'grand_total' => (float) $employeeBreakdown->sum('grand_total'),
            'expense_count' => (int) $employeeBreakdown->sum('expense_count'),
            'average'     => $employeeBreakdown->sum('expense_count') > 0
                ? $employeeBreakdown->sum('grand_total') / $employeeBreakdown->sum('expense_count')
                : 0,
            'max_expense' => (float) ($employeeBreakdown->max('max_expense') ?? 0),
        ];

        // ── Chart matrix (top 5 employees) ────────────────────────
        $topEmployees = $employeeBreakdown->take(5)->pluck('employee_name')->toArray();

        $monthKeys = $monthlyRaw
            ->map(fn($r) => $r['year'] . '-' . str_pad($r['month'], 2, '0', STR_PAD_LEFT))
            ->unique()
            ->sort()
            ->values();

        $chartMonths = $monthKeys->map(function ($key) {
            [$y, $m] = explode('-', $key);
            return [
                'key'   => $key,
                'year'  => (int) $y,
                'month' => (int) $m,
                'label' => date('M y', strtotime($key . '-01')),
            ];
        });

        $chartMatrix = [];
        foreach ($monthlyRaw as $row) {
            $key = $row['year'] . '-' . str_pad($row['month'], 2, '0', STR_PAD_LEFT);
            $chartMatrix[$key][$row['employee_name']] = $row['monthly_total'];
        }

        return response()->json([
            'success'             => true,
            'currency'            => currency_symbol(),
            'summary'             => $summary,
            'totals'              => $totals,
            'employees'           => $employeeBreakdown,
            'employee_categories' => $employeeCategoriesRaw,
            'chart' => [
                'employees' => $topEmployees,
                'months'    => $chartMonths,
                'matrix'    => $chartMatrix,
            ],
            'meta' => [
                'start_date' => $startDate,
                'end_date'   => $endDate,
            ],
        ]);
    }

    public function byEmployeeMeta(Request $request)
    {
        $user     = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('expense reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('expense_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }

        $isAdmin         = $user->hasAnyRole(['super_admin', 'admin']);
        $userLocationIds = $user->locations()->pluck('locations.id')->toArray();

        $employees = Employee::where('tenant_id', $tenantId)
            ->where('is_active', true)->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

        $locationsQuery = Location::where('tenant_id', $tenantId)
            ->where('is_active', 1)->orderBy('name');
        if (!$isAdmin) $locationsQuery->whereIn('id', $userLocationIds);
        $locations = $locationsQuery->get(['id', 'name']);

        return response()->json([
            'success'   => true,
            'employees' => $employees,
            'locations' => $locations,
            'currency'  => currency_symbol(),
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

        return view('reports.expenses.by-payment-method', [
            'initialFilters' => [
                'start_date'        => $request->get('start_date', Carbon::now()->startOfYear()->format('Y-m-d')),
                'end_date'          => $request->get('end_date',   Carbon::now()->endOfMonth()->format('Y-m-d')),
                'payment_method_id' => $request->get('payment_method_id', ''),
                'location_id'       => $request->get('location_id', ''),
            ],
        ]);
    }

    public function byPaymentMethodData(Request $request)
    {
        $user     = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('expense reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('expense_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }

        $isAdmin         = $user->hasAnyRole(['super_admin', 'admin']);
        $userLocationIds = $user->locations()->pluck('locations.id')->toArray();

        $startInput = $request->get('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endInput   = $request->get('end_date',   Carbon::now()->endOfMonth()->format('Y-m-d'));

        try {
            $startDate = Carbon::parse($startInput)->format('Y-m-d');
            $endDate   = Carbon::parse($endInput)->format('Y-m-d');
            if ($startDate > $endDate) [$startDate, $endDate] = [$endDate, $startDate];
        } catch (\Throwable $e) {
            $startDate = Carbon::now()->startOfYear()->format('Y-m-d');
            $endDate   = Carbon::now()->endOfMonth()->format('Y-m-d');
        }

        $paymentMethodId = $request->get('payment_method_id');
        $locationId      = $request->get('location_id');

        $locationFilter = null;
        if (!empty($locationId) && is_numeric($locationId)) {
            $req = (int) $locationId;
            if ($isAdmin || in_array($req, $userLocationIds, true)) $locationFilter = $req;
        }

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

        $allExpenses = (clone $baseQuery)
            ->with(['paymentMethod:id,name,type,is_active', 'category:id,name', 'location:id,name'])
            ->get();

        // ── Method breakdown ──────────────────────────────────────
        $methodBreakdown = $allExpenses->groupBy('payment_method_id')
            ->map(function ($expenses, $methodId) {
                $pm         = $expenses->first()->paymentMethod;
                $count      = $expenses->count();
                $grandTotal = (float) $expenses->sum('total_amount');

                return [
                    'id'                  => $methodId,
                    'method_name'         => $pm->name ?? 'Unknown',
                    'method_type'         => $pm->type ?? 'unknown',
                    'is_active'           => (bool) ($pm->is_active ?? false),
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

        // ── Monthly trend ─────────────────────────────────────────
        $monthlyRaw = $allExpenses
            ->groupBy(fn($e) => $e->date->format('Y-m'))
            ->flatMap(function ($expensesInMonth, $monthKey) {
                [$year, $month] = explode('-', $monthKey);
                return $expensesInMonth->groupBy('payment_method_id')
                    ->map(function ($methodExpenses) use ($year, $month) {
                        $pm = $methodExpenses->first()->paymentMethod;
                        return [
                            'year'              => (int) $year,
                            'month'             => (int) $month,
                            'method_name'       => $pm->name ?? 'Unknown',
                            'transaction_count' => $methodExpenses->count(),
                            'monthly_total'     => (float) $methodExpenses->sum('total_amount'),
                        ];
                    })
                    ->values();
            })
            ->values();

        // ── Method × category ─────────────────────────────────────
        $methodByCategoryRaw = $allExpenses->groupBy('payment_method_id')
            ->flatMap(function ($expenses) {
                $pm         = $expenses->first()->paymentMethod;
                $methodName = $pm->name ?? 'Unknown';

                return $expenses->groupBy('category_id')
                    ->map(function ($catExpenses) use ($methodName) {
                        $category = $catExpenses->first()->category;
                        return [
                            'method_name'       => $methodName,
                            'category_name'     => $category->name ?? 'Uncategorized',
                            'transaction_count' => $catExpenses->count(),
                            'total_amount'      => (float) $catExpenses->sum('total_amount'),
                        ];
                    })
                    ->values();
            })
            ->values();

        // Group by method for the cards
        $methodByCategory = $methodByCategoryRaw
            ->groupBy('method_name')
            ->map(fn($rows) => $rows->values())
            ->toArray();

        // ── Summary ───────────────────────────────────────────────
        $totalExpenses = (float) $methodBreakdown->sum('grand_total');
        $totalTransactions = (int) $methodBreakdown->sum('transaction_count');

        $summary = [
            'total_methods'      => $methodBreakdown->count(),
            'total_transactions' => $totalTransactions,
            'total_amount'       => $totalExpenses,
            'total_tax'          => (float) $methodBreakdown->sum('total_tax'),
            'avg_transaction'    => $totalTransactions > 0 ? $totalExpenses / $totalTransactions : 0,
            'most_used_method'   => $methodBreakdown->first()['method_name'] ?? 'N/A',
        ];

        // ── Chart matrix (top 5 methods) ──────────────────────────
        $topMethods = $methodBreakdown->take(5)->pluck('method_name')->toArray();

        $monthKeys = $monthlyRaw
            ->map(fn($r) => $r['year'] . '-' . str_pad($r['month'], 2, '0', STR_PAD_LEFT))
            ->unique()
            ->sort()
            ->values();

        $chartMonths = $monthKeys->map(function ($key) {
            [$y, $m] = explode('-', $key);
            return [
                'key'   => $key,
                'year'  => (int) $y,
                'month' => (int) $m,
                'label' => date('M y', strtotime($key . '-01')),
            ];
        });

        $chartMatrix = [];
        foreach ($monthlyRaw as $row) {
            $key = $row['year'] . '-' . str_pad($row['month'], 2, '0', STR_PAD_LEFT);
            $chartMatrix[$key][$row['method_name']] = $row['monthly_total'];
        }

        return response()->json([
            'success'            => true,
            'currency'           => currency_symbol(),
            'summary'            => $summary,
            'methods'            => $methodBreakdown,
            'method_by_category' => $methodByCategory,
            'chart' => [
                'methods' => $topMethods,
                'months'  => $chartMonths,
                'matrix'  => $chartMatrix,
            ],
            'meta' => [
                'start_date' => $startDate,
                'end_date'   => $endDate,
            ],
        ]);
    }

    public function byPaymentMethodMeta(Request $request)
    {
        $user     = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('expense reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('expense_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }

        $isAdmin         = $user->hasAnyRole(['super_admin', 'admin']);
        $userLocationIds = $user->locations()->pluck('locations.id')->toArray();

        $paymentMethods = PaymentMethod::where('tenant_id', $tenantId)
            ->where('is_active', true)->orderBy('name')
            ->get(['id', 'name', 'type', 'is_default']);

        $locationsQuery = Location::where('tenant_id', $tenantId)
            ->where('is_active', 1)->orderBy('name');
        if (!$isAdmin) $locationsQuery->whereIn('id', $userLocationIds);
        $locations = $locationsQuery->get(['id', 'name']);

        return response()->json([
            'success'         => true,
            'payment_methods' => $paymentMethods,
            'locations'       => $locations,
            'currency'        => currency_symbol(),
        ]);
    }

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

        return view('reports.expenses.recurring', [
            'initialFilters' => [
                'frequency'   => $request->get('frequency', ''),
                'category_id' => $request->get('category_id', ''),
                'status'      => $request->get('status', 'active'),
                'location_id' => $request->get('location_id', ''),
            ],
        ]);
    }

    public function recurringData(Request $request)
    {
        $user     = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('expense reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('expense_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }

        $isAdmin         = $user->hasAnyRole(['super_admin', 'admin']);
        $userLocationIds = $user->locations()->pluck('locations.id')->toArray();

        $frequency  = $request->get('frequency');
        $categoryId = $request->get('category_id');
        $status     = $request->get('status', 'active');
        $locationId = $request->get('location_id');

        $locationFilter = null;
        if (!empty($locationId) && is_numeric($locationId)) {
            $req = (int) $locationId;
            if ($isAdmin || in_array($req, $userLocationIds, true)) $locationFilter = $req;
        }

        $baseQuery = Expense::query()
            ->where('tenant_id', $tenantId)
            ->where('is_recurring', true);

        if ($frequency) $baseQuery->where('recurring_frequency', $frequency);
        if ($categoryId && is_numeric($categoryId)) $baseQuery->where('category_id', (int) $categoryId);
        if ($locationFilter) $baseQuery->where('location_id', $locationFilter);

        $today = Carbon::today();

        if ($status === 'active') {
            $baseQuery->where(function ($q) use ($today) {
                $q->where('next_recurring_date', '>=', $today)->orWhereNull('next_recurring_date');
            });
        } elseif ($status === 'upcoming') {
            $nextWeek = $today->copy()->addWeek();
            $baseQuery->whereBetween('next_recurring_date', [$today, $nextWeek]);
        } elseif ($status === 'overdue') {
            $baseQuery->where('next_recurring_date', '<', $today);
        }

        $recurringExpenses = (clone $baseQuery)
            ->with(['category:id,name', 'paymentMethod:id,name', 'location:id,name'])
            ->orderBy('next_recurring_date', 'asc')
            ->get();

        // ── Frequency breakdown ────────────────────────────────────
        $byFrequency = $recurringExpenses->groupBy('recurring_frequency')
            ->map(function ($items, $freq) {
                $multiplier = match ($freq) {
                    'weekly' => 52, 'monthly' => 12, 'quarterly' => 4, 'annually' => 1, default => 12,
                };
                $monthlyTotal = (float) $items->sum('total_amount');
                return [
                    'count'         => $items->count(),
                    'total_monthly' => $monthlyTotal,
                    'total_annual'  => (float) $items->sum(fn($item) => $item->total_amount * $multiplier),
                ];
            })
            ->toArray();

        // ── Upcoming 30 days ───────────────────────────────────────
        $upcomingNext30Days = $recurringExpenses->filter(function ($expense) use ($today) {
            if (!$expense->next_recurring_date) return false;
            $next = Carbon::parse($expense->next_recurring_date);
            return $next->between($today, $today->copy()->addDays(30));
        })->values();

        // ── Annual projection ─────────────────────────────────────
        $annualProjection = (float) $recurringExpenses->sum(function ($expense) {
            $m = match ($expense->recurring_frequency) {
                'weekly' => 52, 'monthly' => 12, 'quarterly' => 4, 'annually' => 1, default => 12,
            };
            return (float) $expense->total_amount * $m;
        });

        // ── Monthly projection ────────────────────────────────────
        $monthlyProjection = [];
        $currentMonth = Carbon::now()->startOfMonth();

        for ($i = 0; $i < 12; $i++) {
            $month     = $currentMonth->copy()->addMonths($i);
            $monthName = $month->format('M Y');

            $monthlyTotal = $recurringExpenses->sum(function ($expense) use ($month) {
                if (!$expense->next_recurring_date) return 0;
                $next      = Carbon::parse($expense->next_recurring_date);
                $frequency = $expense->recurring_frequency;

                return match ($frequency) {
                    'weekly'    => (float) $expense->total_amount * 4.33,
                    'monthly'   => (float) $expense->total_amount,
                    'quarterly' => $next->diffInMonths($month) % 3 === 0 ? (float) $expense->total_amount : 0,
                    'annually'  => $next->format('m') === $month->format('m') ? (float) $expense->total_amount : 0,
                    default     => 0,
                };
            });

            $monthlyProjection[] = [
                'month'           => $monthName,
                'projected_total' => $monthlyTotal,
                'expense_count'   => $recurringExpenses->count(),
            ];
        }

        // ── Summary ───────────────────────────────────────────────
        $summary = [
            'total_recurring'    => $recurringExpenses->count(),
            'total_monthly_cost' => (float) $recurringExpenses->sum('total_amount'),
            'total_annual_cost'  => $annualProjection,
            'upcoming_30_days'   => $upcomingNext30Days->count(),
            'upcoming_amount'    => (float) $upcomingNext30Days->sum('total_amount'),
            'avg_per_expense'    => (float) ($recurringExpenses->avg('total_amount') ?? 0),
        ];

        return response()->json([
            'success'             => true,
            'currency'            => currency_symbol(),
            'summary'             => $auditStats,
            'items'               => $itemsPayload,
            'by_category'         => $byCategory,
            'monthly'             => $monthlyAudit,
            'employee_compliance' => $employeeCompliance,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
                'has_more'     => $paginator->hasMorePages(),
            ],
            'meta' => [
                'start_date' => $startDate,
                'end_date'   => $endDate,
                'audit_type' => $auditType,
                'threshold'  => $threshold,
            ],
        ]);
    }

    public function recurringMeta(Request $request)
    {
        $user     = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('expense reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('expense_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }

        $isAdmin         = $user->hasAnyRole(['super_admin', 'admin']);
        $userLocationIds = $user->locations()->pluck('locations.id')->toArray();

        $categories = ExpenseCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)->orderBy('name')
            ->get(['id', 'name', 'code']);

        $locationsQuery = Location::where('tenant_id', $tenantId)
            ->where('is_active', 1)->orderBy('name');
        if (!$isAdmin) $locationsQuery->whereIn('id', $userLocationIds);
        $locations = $locationsQuery->get(['id', 'name']);

        return response()->json([
            'success'     => true,
            'categories'  => $categories,
            'locations'   => $locations,
            'frequencies' => [
                'weekly'    => 'Weekly',
                'monthly'   => 'Monthly',
                'quarterly' => 'Quarterly',
                'annually'  => 'Annually',
            ],
            'currency'    => currency_symbol(),
        ]);
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

        return view('reports.expenses.budget-vs-actual', [
            'initialFilters' => [
                'year'        => (int) $request->get('year', date('Y')),
                'month'       => (int) $request->get('month', date('m')),
                'location_id' => $request->get('location_id', ''),
            ],
        ]);
    }

    public function budgetVsActualData(Request $request)
    {
        $user     = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('expense reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('expense_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }

        $isAdmin         = $user->hasAnyRole(['super_admin', 'admin']);
        $userLocationIds = $user->locations()->pluck('locations.id')->toArray();

        $year       = (int) $request->get('year', date('Y'));
        $month      = (int) $request->get('month', date('m'));
        $locationId = $request->get('location_id');

        $locationFilter = null;
        if (!empty($locationId) && is_numeric($locationId)) {
            $req = (int) $locationId;
            if ($isAdmin || in_array($req, $userLocationIds, true)) $locationFilter = $req;
        }

        $budgetedCategories = ExpenseCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNotNull('budget_monthly')->orWhereNotNull('budget_annual');
            })
            ->orderBy('name')
            ->get();

        $baseQuery = Expense::query()
            ->where('tenant_id', $tenantId)
            ->whereYear('date', $year)
            ->when($locationFilter, fn($q) => $q->where('location_id', $locationFilter));

        $expensesByCategory = (clone $baseQuery)->get()->groupBy('category_id');

        $budgetData = [];
        $totalBudgetMonthly = 0;
        $totalBudgetAnnual  = 0;
        $totalActualMonthly = 0;
        $totalActualAnnual  = 0;

        foreach ($budgetedCategories as $category) {
            $categoryExpenses = $expensesByCategory->get($category->id, collect());

            $monthlyActual = (float) $categoryExpenses
                ->filter(fn($e) => $e->date->month === $month)
                ->sum('total_amount');
            $annualActual = (float) $categoryExpenses->sum('total_amount');

            $budgetMonthly = (float) ($category->budget_monthly ?? 0);
            $budgetAnnual  = (float) ($category->budget_annual ?? ($budgetMonthly * 12));

            $varianceMonthly = $budgetMonthly - $monthlyActual;
            $varianceAnnual  = $budgetAnnual - $annualActual;

            $budgetData[] = [
                'category_id'                => $category->id,
                'category_name'              => $category->name,
                'category_code'              => $category->code,
                'category_description'       => $category->description,
                'budget_monthly'             => $budgetMonthly,
                'actual_monthly'             => $monthlyActual,
                'variance_monthly'           => $varianceMonthly,
                'variance_pct_monthly'       => $budgetMonthly > 0 ? ($varianceMonthly / $budgetMonthly) * 100 : 0,
                'budget_annual'              => $budgetAnnual,
                'actual_annual'              => $annualActual,
                'variance_annual'            => $varianceAnnual,
                'variance_pct_annual'        => $budgetAnnual > 0 ? ($varianceAnnual / $budgetAnnual) * 100 : 0,
                'progress_pct'               => $budgetMonthly > 0 ? min(($monthlyActual / $budgetMonthly) * 100, 999) : 0,
            ];

            $totalBudgetMonthly += $budgetMonthly;
            $totalBudgetAnnual  += $budgetAnnual;
            $totalActualMonthly += $monthlyActual;
            $totalActualAnnual  += $annualActual;
        }

        // Summary
        $totalVarianceMonthly = $totalBudgetMonthly - $totalActualMonthly;
        $totalVarianceAnnual  = $totalBudgetAnnual - $totalActualAnnual;

        $summary = [
            'total_budget_monthly'   => $totalBudgetMonthly,
            'total_actual_monthly'   => $totalActualMonthly,
            'total_variance_monthly' => $totalVarianceMonthly,
            'variance_pct_monthly'   => $totalBudgetMonthly > 0 ? ($totalVarianceMonthly / $totalBudgetMonthly) * 100 : 0,
            'total_budget_annual'    => $totalBudgetAnnual,
            'total_actual_annual'    => $totalActualAnnual,
            'total_variance_annual'  => $totalVarianceAnnual,
            'variance_pct_annual'    => $totalBudgetAnnual > 0 ? ($totalVarianceAnnual / $totalBudgetAnnual) * 100 : 0,
            'under_budget_count'     => collect($budgetData)->where('variance_monthly', '>', 0)->count(),
            'over_budget_count'      => collect($budgetData)->where('variance_monthly', '<', 0)->count(),
            'on_budget_count'        => collect($budgetData)->where('variance_monthly', '==', 0)->count(),
            'total_categories'       => count($budgetData),
        ];

        // ── Monthly trends per category (for chart) ───────────────
        $monthlyTrends = [];
        foreach ($budgetedCategories as $category) {
            $categoryExpenses = $expensesByCategory->get($category->id, collect());
            $monthlyData = $categoryExpenses
                ->groupBy(fn($e) => $e->date->month)
                ->map(fn($group) => (float) $group->sum('total_amount'));

            $trend = [];
            for ($m = 1; $m <= 12; $m++) {
                $trend[$m] = [
                    'month'    => $m,
                    'budget'   => (float) ($category->budget_monthly ?? 0),
                    'actual'   => $monthlyData[$m] ?? 0,
                ];
            }
            $monthlyTrends[$category->id] = $trend;
        }

        // ── Chart series (top 5 by budget_monthly) ───────────────
        $topForChart = collect($budgetData)
            ->sortByDesc('budget_monthly')
            ->take(5)
            ->values()
            ->map(function ($row) use ($monthlyTrends) {
                $trend    = $monthlyTrends[$row['category_id']] ?? [];
                $budget   = [];
                $actual   = [];
                for ($m = 1; $m <= 12; $m++) {
                    $budget[] = $trend[$m]['budget'] ?? 0;
                    $actual[] = $trend[$m]['actual'] ?? 0;
                }
                return [
                    'name'   => $row['category_name'],
                    'budget' => $budget,
                    'actual' => $actual,
                ];
            });

        // ── Top performing categories (sorted by variance_pct_monthly) ──
        $topPerforming = collect($budgetData)
            ->sortBy('variance_pct_monthly')
            ->take(5)
            ->values();

        return response()->json([
            'success'    => true,
            'currency'   => currency_symbol(),
            'year'       => $year,
            'month'      => $month,
            'summary'    => $summary,
            'budget'     => $budgetData,
            'chart'      => $topForChart,
            'top'        => $topPerforming,
        ]);
    }

    public function budgetVsActualMeta(Request $request)
    {
        $user     = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('expense reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('expense_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }

        $isAdmin         = $user->hasAnyRole(['super_admin', 'admin']);
        $userLocationIds = $user->locations()->pluck('locations.id')->toArray();

        $locationsQuery = Location::where('tenant_id', $tenantId)
            ->where('is_active', 1)->orderBy('name');
        if (!$isAdmin) $locationsQuery->whereIn('id', $userLocationIds);
        $locations = $locationsQuery->get(['id', 'name']);

        return response()->json([
            'success'   => true,
            'locations' => $locations,
            'years'     => range(date('Y') - 5, date('Y') + 1),
            'months'    => [
                1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
            ],
            'currency'  => currency_symbol(),
        ]);
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

        return view('reports.expenses.trends', [
            'initialFilters' => [
                'period'      => $request->get('period', 'monthly'),
                'year'        => (int) $request->get('year', date('Y')),
                'category_id' => $request->get('category_id', ''),
                'location_id' => $request->get('location_id', ''),
            ],
        ]);
    }

    public function trendsData(Request $request)
    {
        $user     = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('expense reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('expense_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }

        $isAdmin         = $user->hasAnyRole(['super_admin', 'admin']);
        $userLocationIds = $user->locations()->pluck('locations.id')->toArray();

        $period     = $request->get('period', 'monthly');
        $year       = (int) $request->get('year', date('Y'));
        $categoryId = $request->get('category_id');
        $locationId = $request->get('location_id');

        $locationFilter = null;
        if (!empty($locationId) && is_numeric($locationId)) {
            $req = (int) $locationId;
            if ($isAdmin || in_array($req, $userLocationIds, true)) $locationFilter = $req;
        }

        $trendData       = [];
        $categoryTrends  = [];
        $movingAverages  = [];
        $momGrowth       = [];
        $monthNames      = [
            1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',
            7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December',
        ];

        if ($period === 'monthly') {
            $startDate = Carbon::create($year, 1, 1)->startOfYear();
            $endDate   = Carbon::create($year, 12, 31)->endOfYear();

            $expenses = Expense::with('category:id,name')
                ->where('tenant_id', $tenantId)
                ->whereBetween('date', [$startDate, $endDate])
                ->when($categoryId, fn($q) => $q->where('category_id', (int) $categoryId))
                ->when($locationFilter, fn($q) => $q->where('location_id', $locationFilter))
                ->get();

            $monthlyExpenses = $expenses->groupBy(fn($e) => $e->date->month);

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

                $growth = $previousTotal > 0 ? (($currentTotal - $previousTotal) / $previousTotal) * 100 : 0;

                $trendData[$month] = [
                    'month'         => $month,
                    'month_name'    => $monthNames[$month],
                    'current_year'  => $currentTotal,
                    'previous_year' => $previousTotal,
                    'growth'        => $growth,
                    'expense_count' => $currentExpenses->count(),
                    'tax_total'     => (float) $currentExpenses->sum('tax_amount'),
                ];
            }

            // Category trends
            $categoryTrends = $expenses->groupBy('category_id')
                ->map(function ($catExpenses) {
                    $category = $catExpenses->first()->category;
                    $monthlyData = [];
                    for ($m = 1; $m <= 12; $m++) {
                        $monthlyData[$m] = (float) $catExpenses
                            ->filter(fn($e) => $e->date->month === $m)
                            ->sum('total_amount');
                    }
                    return [
                        'category_name' => $category->name ?? 'Uncategorized',
                        'monthly_data'  => $monthlyData,
                        'total'         => (float) $catExpenses->sum('total_amount'),
                    ];
                })
                ->sortByDesc('total')
                ->take(5)
                ->values()
                ->toArray();

            // Moving averages (3-month rolling)
            $monthlyTotals = collect($trendData)->pluck('current_year')->values()->all();
            for ($i = 2; $i < count($monthlyTotals); $i++) {
                $movingAverages[$i + 1] = (
                    ($monthlyTotals[$i - 2] ?? 0) +
                    ($monthlyTotals[$i - 1] ?? 0) +
                    ($monthlyTotals[$i] ?? 0)
                ) / 3;
            }

            // MoM growth
            for ($i = 1; $i < count($monthlyTotals); $i++) {
                $prev = $monthlyTotals[$i - 1] ?? 0;
                $curr = $monthlyTotals[$i] ?? 0;
                $momGrowth[$i + 1] = $prev > 0 ? (($curr - $prev) / $prev) * 100 : ($curr > 0 ? 100 : 0);
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

                $quarterExpenses = $expenses->filter(fn($e) =>
                    $e->date->month >= $startMonth && $e->date->month <= $endMonth
                );

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

        // ── Summary ────────────────────────────────────────────
        $currentTotal  = (float) collect($trendData)->sum('current_year') ?: (float) collect($trendData)->sum('total');
        $previousTotal = (float) collect($trendData)->sum('previous_year');
        $totalExpenses = (int) collect($trendData)->sum('expense_count');
        $averageAmount = count($trendData) > 0 ? $currentTotal / count($trendData) : 0;
        $growthRate    = $previousTotal > 0 ? (($currentTotal - $previousTotal) / $previousTotal) * 100 : 0;

        $summary = [
            'total_expenses'      => $currentTotal,
            'growth_rate'         => $growthRate,
            'expense_count'       => $totalExpenses,
            'average_amount'      => $averageAmount,
            'previous_year_total' => $previousTotal,
            'variance'            => abs($currentTotal - $previousTotal),
            'growth_direction'    => $growthRate >= 0 ? 'up' : 'down',
            'variance_direction'  => $currentTotal >= $previousTotal ? 'up' : 'down',
        ];

        return response()->json([
            'success'          => true,
            'currency'         => currency_symbol(),
            'period'           => $period,
            'year'             => $year,
            'trend'            => array_values($trendData),   // indexed array for JS
            'summary'          => $summary,
            'category_trends'  => $categoryTrends,
            'moving_averages'  => (object) $movingAverages,
            'mom_growth'       => (object) $momGrowth,
        ]);
    }

    public function trendsMeta(Request $request)
    {
        $user     = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('expense reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('expense_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }

        $isAdmin         = $user->hasAnyRole(['super_admin', 'admin']);
        $userLocationIds = $user->locations()->pluck('locations.id')->toArray();

        $categories = ExpenseCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)->orderBy('name')
            ->get(['id', 'name', 'code']);

        $locationsQuery = Location::where('tenant_id', $tenantId)
            ->where('is_active', 1)->orderBy('name');
        if (!$isAdmin) $locationsQuery->whereIn('id', $userLocationIds);
        $locations = $locationsQuery->get(['id', 'name']);

        return response()->json([
            'success'    => true,
            'categories' => $categories,
            'locations'  => $locations,
            'years'      => range(date('Y') - 5, date('Y')),
            'currency'   => currency_symbol(),
        ]);
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

        return view('reports.expenses.tax-report', [
            'initialFilters' => [
                'start_date'  => $request->get('start_date', Carbon::now()->startOfYear()->format('Y-m-d')),
                'end_date'    => $request->get('end_date',   Carbon::now()->endOfMonth()->format('Y-m-d')),
                'category_id' => $request->get('category_id', ''),
                'tax_type'    => $request->get('tax_type', 'all'),
                'location_id' => $request->get('location_id', ''),
            ],
        ]);
    }

    public function taxReportData(Request $request)
    {
        $user     = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('expense reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('expense_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }

        $isAdmin         = $user->hasAnyRole(['super_admin', 'admin']);
        $userLocationIds = $user->locations()->pluck('locations.id')->toArray();

        $startInput = $request->get('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endInput   = $request->get('end_date',   Carbon::now()->endOfMonth()->format('Y-m-d'));

        try {
            $startDate = Carbon::parse($startInput)->format('Y-m-d');
            $endDate   = Carbon::parse($endInput)->format('Y-m-d');
            if ($startDate > $endDate) [$startDate, $endDate] = [$endDate, $startDate];
        } catch (\Throwable $e) {
            $startDate = Carbon::now()->startOfYear()->format('Y-m-d');
            $endDate   = Carbon::now()->endOfMonth()->format('Y-m-d');
        }

        $categoryId = $request->get('category_id');
        $taxType    = $request->get('tax_type', 'all');
        $locationId = $request->get('location_id');

        $locationFilter = null;
        if (!empty($locationId) && is_numeric($locationId)) {
            $req = (int) $locationId;
            if ($isAdmin || in_array($req, $userLocationIds, true)) $locationFilter = $req;
        }

        $baseQuery = Expense::query()
            ->where('tenant_id', $tenantId)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate);

        if ($categoryId && is_numeric($categoryId)) $baseQuery->where('category_id', (int) $categoryId);
        if ($taxType === 'taxable')     $baseQuery->where('tax_amount', '>', 0);
        elseif ($taxType === 'non-taxable') $baseQuery->where('tax_amount', '=', 0);
        if ($locationFilter) $baseQuery->where('location_id', $locationFilter);

        $allExpenses = (clone $baseQuery)
            ->with(['category:id,name', 'paymentMethod:id,name', 'location:id,name'])
            ->get();

        $taxableExpenses    = $allExpenses->where('tax_amount', '>', 0);
        $nonTaxableExpenses = $allExpenses->where('tax_amount', '=', 0);

        $totalGross = (float) $allExpenses->sum('gross_amount');
        $totalTax   = (float) $allExpenses->sum('tax_amount');
        $totalNet   = (float) $allExpenses->sum('net_amount');

        $summary = [
            'total_expenses'       => $allExpenses->count(),
            'total_gross'          => $totalGross,
            'total_tax'            => $totalTax,
            'total_net'            => $totalNet,
            'taxable_expenses'     => $taxableExpenses->count(),
            'non_taxable_expenses' => $nonTaxableExpenses->count(),
            'avg_tax_rate'         => $totalGross > 0 ? ($totalTax / $totalGross) * 100 : 0,
            'withholding_impact'   => $totalGross > 0 ? (($totalGross - $totalNet) / $totalGross) * 100 : 0,
        ];

        // By category
        $taxByCategory = $allExpenses->groupBy('category_id')->map(function ($expenses) {
            $category   = $expenses->first()->category;
            $grossAmount = (float) $expenses->sum('gross_amount');
            $taxAmount   = (float) $expenses->sum('tax_amount');
            $taxable     = $expenses->where('tax_amount', '>', 0)->count();

            return [
                'category_name'     => $category->name ?? 'Uncategorized',
                'expense_count'     => $expenses->count(),
                'gross_amount'      => $grossAmount,
                'tax_amount'        => $taxAmount,
                'net_amount'        => (float) $expenses->sum('net_amount'),
                'avg_tax_rate'      => $grossAmount > 0 ? ($taxAmount / $grossAmount) * 100 : 0,
                'taxable_count'     => $taxable,
                'non_taxable_count' => $expenses->count() - $taxable,
            ];
        })->sortByDesc('tax_amount')->values();

        // Monthly
        $monthlyTax = $allExpenses
            ->groupBy(fn($e) => $e->date->format('Y-m'))
            ->map(function ($expenses, $key) {
                [$year, $month] = explode('-', $key);
                $date = Carbon::createFromDate((int) $year, (int) $month, 1);
                $grossAmount = (float) $expenses->sum('gross_amount');
                $taxAmount   = (float) $expenses->sum('tax_amount');

                return [
                    'year'          => (int) $year,
                    'month'         => (int) $month,
                    'month_name'    => $date->format('F Y'),
                    'expense_count' => $expenses->count(),
                    'gross_amount'  => $grossAmount,
                    'tax_amount'    => $taxAmount,
                    'net_amount'    => (float) $expenses->sum('net_amount'),
                    'avg_tax_rate'  => $grossAmount > 0 ? ($taxAmount / $grossAmount) * 100 : 0,
                ];
            })
            ->sortByDesc(fn($r) => $r['year'] . '-' . str_pad($r['month'], 2, '0', STR_PAD_LEFT))
            ->values();

        // Rate distribution
        $taxRateDistribution = $allExpenses->where('tax_amount', '>', 0)
            ->where('gross_amount', '>', 0)
            ->groupBy(fn($e) => round(($e->tax_amount / $e->gross_amount) * 100, 0))
            ->map(function ($expenses, $rate) {
                return [
                    'tax_rate_percent' => (int) $rate,
                    'expense_count'    => $expenses->count(),
                    'gross_amount'     => (float) $expenses->sum('gross_amount'),
                    'total_tax'        => (float) $expenses->sum('tax_amount'),
                ];
            })
            ->sortKeys()
            ->values();

        return response()->json([
            'success'   => true,
            'currency'  => currency_symbol(),
            'summary'   => $summary,
            'by_category' => $taxByCategory,
            'monthly'   => $monthlyTax,
            'rate_distribution' => $taxRateDistribution,
            'meta' => [
                'start_date' => $startDate,
                'end_date'   => $endDate,
            ],
        ]);
    }

    public function taxReportMeta(Request $request)
    {
        $user     = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('expense reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('expense_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }

        $isAdmin         = $user->hasAnyRole(['super_admin', 'admin']);
        $userLocationIds = $user->locations()->pluck('locations.id')->toArray();

        $categories = ExpenseCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);

        $locationsQuery = Location::where('tenant_id', $tenantId)->where('is_active', 1)->orderBy('name');
        if (!$isAdmin) $locationsQuery->whereIn('id', $userLocationIds);
        $locations = $locationsQuery->get(['id', 'name']);

        return response()->json([
            'success'    => true,
            'categories' => $categories,
            'locations'  => $locations,
            'currency'   => currency_symbol(),
        ]);
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

        return view('reports.expenses.audit', [
            'initialFilters' => [
                'start_date'  => $request->get('start_date', Carbon::now()->subMonths(3)->format('Y-m-d')),
                'end_date'    => $request->get('end_date',   Carbon::now()->format('Y-m-d')),
                'audit_type'  => $request->get('audit_type', 'all'),
                'employee_id' => $request->get('employee_id', ''),
                'threshold'   => (float) $request->get('threshold', 1000),
                'location_id' => $request->get('location_id', ''),
                'per_page'    => 15,
                'page'        => 1,
            ],
        ]);
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

    public function auditData(Request $request)
    {
        $user     = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('expense reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('expense_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }

        $isAdmin         = $user->hasAnyRole(['super_admin', 'admin']);
        $userLocationIds = $user->locations()->pluck('locations.id')->toArray();

        $startInput = $request->get('start_date', Carbon::now()->subMonths(3)->format('Y-m-d'));
        $endInput   = $request->get('end_date',   Carbon::now()->format('Y-m-d'));

        try {
            $startDate = Carbon::parse($startInput)->format('Y-m-d');
            $endDate   = Carbon::parse($endInput)->format('Y-m-d');
            if ($startDate > $endDate) [$startDate, $endDate] = [$endDate, $startDate];
        } catch (\Throwable $e) {
            $startDate = Carbon::now()->subMonths(3)->format('Y-m-d');
            $endDate   = Carbon::now()->format('Y-m-d');
        }

        $auditType  = $request->get('audit_type', 'all');
        $employeeId = $request->get('employee_id');
        $threshold  = (float) $request->get('threshold', 1000);
        $locationId = $request->get('location_id');
        $page       = max(1, (int) $request->get('page', 1));
        $perPage    = (int) $request->get('per_page', 15);
        if (!in_array($perPage, [15, 25, 50, 100], true)) $perPage = 15;

        $locationFilter = null;
        if (!empty($locationId) && is_numeric($locationId)) {
            $req = (int) $locationId;
            if ($isAdmin || in_array($req, $userLocationIds, true)) $locationFilter = $req;
        }

        $baseQuery = Expense::query()
            ->where('tenant_id', $tenantId)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate);

        if ($locationFilter) $baseQuery->where('location_id', $locationFilter);
        if ($employeeId && is_numeric($employeeId)) $baseQuery->where('employee_id', (int) $employeeId);

        // Audit-type filter
        $auditQuery = (clone $baseQuery);
        switch ($auditType) {
            case 'missing_receipts':
            case 'policy_violations':
                $auditQuery->whereHas('category', fn($q) => $q->where('requires_receipt', true))
                    ->where(fn($q) => $q->whereNull('receipt_url')->orWhere('receipt_url', ''));
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
        }

        $with = ['category:id,name,requires_receipt,requires_approval', 'paymentMethod:id,name', 'employee:id,first_name,last_name', 'approver:id,name', 'location:id,name'];

        // ── FULL SET — used for summary, category, employee compliance ──
        $allAuditItems = (clone $auditQuery)->with($with)->orderByDesc('date')->get();

        // ── PAGINATED — table only ──────────────────────────────────────
        $paginator = (clone $auditQuery)
            ->with($with)
            ->orderByDesc('date')
            ->paginate($perPage, ['*'], 'page', $page);

        // ── Summary ─────────────────────────────────────────────────────
        $auditStats = [
            'total_items'      => $allAuditItems->count(),
            'total_amount'     => (float) $allAuditItems->sum('total_amount'),
            'missing_receipts' => $allAuditItems->filter(fn($i) => $i->category?->requires_receipt && empty($i->receipt_url))->count(),
            'unapproved'       => $allAuditItems->filter(fn($i) => $i->category?->requires_approval && !$i->approved_at)->count(),
            'high_value'       => $allAuditItems->filter(fn($i) => $i->total_amount >= $threshold)->count(),
            'average_age_days' => $allAuditItems->count() > 0
                ? $allAuditItems->avg(fn($i) => max(0, (int) round(Carbon::parse($i->created_at)->diffInDays(Carbon::today(), false))))
                : 0,
        ];

        $auditStats['compliance_rate'] = $auditStats['total_items'] > 0
            ? (($auditStats['total_items'] - $auditStats['missing_receipts'] - $auditStats['unapproved']) / $auditStats['total_items']) * 100
            : 100;

        $auditStats['avg_item_amount'] = $auditStats['total_items'] > 0
            ? $auditStats['total_amount'] / $auditStats['total_items']
            : 0;

        // ── Items on THIS page ─────────────────────────────────────────
        $itemsPayload = collect($paginator->items())->map(function ($expense) use ($threshold) {
            $rawAge   = Carbon::parse($expense->created_at)->diffInDays(Carbon::today(), false);
            $ageDays  = max(0, (int) round($rawAge));  // clamp negatives (future-dated) to 0

            $hasReceipt = !empty($expense->receipt_url);
            $isApproved = !empty($expense->approved_at);
            $requiresReceipt  = (bool) ($expense->category?->requires_receipt ?? false);
            $requiresApproval = (bool) ($expense->category?->requires_approval ?? false);

            $flags = [];
            if ($requiresReceipt && !$hasReceipt)     $flags[] = ['text' => __('accounting.missing_receipt'),  'color' => 'danger'];
            if ($requiresApproval && !$isApproved)    $flags[] = ['text' => __('accounting.pending_approval'), 'color' => 'warning'];
            if ($ageDays > 30)                        $flags[] = ['text' => __('accounting.old_expense'),      'color' => 'secondary'];
            if ($expense->total_amount >= $threshold) $flags[] = ['text' => __('accounting.high_value'),       'color' => 'info'];

            return [
                'id'               => $expense->id,
                'date'             => $expense->date?->format('Y-m-d'),
                'date_day'         => $expense->date?->format('D'),
                'description'      => $expense->description,
                'vendor_name'      => $expense->vendor_name,
                'expense_number'   => $expense->expense_number,
                'category_name'    => $expense->category?->name ?? 'N/A',
                'requires_receipt' => $requiresReceipt,
                'requires_approval'=> $requiresApproval,
                'employee_name'    => $expense->employee
                    ? trim(($expense->employee->first_name ?? '') . ' ' . ($expense->employee->last_name ?? ''))
                    : null,
                'employee_id'      => $expense->employee?->id,
                'amount'           => (float) $expense->total_amount,
                'tax_amount'       => (float) $expense->tax_amount,
                'payment_status'   => $expense->payment_status,
                'receipt_url'      => $expense->receipt_url,
                'has_receipt'      => $hasReceipt,
                'is_approved'      => $isApproved,
                'approver_name'    => $expense->approver?->name,
                'age_days'         => $ageDays,
                'flags'            => $flags,
            ];
        })->values();

        // ── By category (full set) ─────────────────────────────────────
        $byCategory = $allAuditItems->groupBy(fn($i) => $i->category?->name ?? 'Uncategorized')
            ->map(function ($items, $category) {
                return [
                    'category'         => $category,
                    'count'            => $items->count(),
                    'total_amount'     => (float) $items->sum('total_amount'),
                    'avg_amount'       => (float) ($items->avg('total_amount') ?? 0),
                    'missing_receipts' => $items->filter(fn($i) => $i->category?->requires_receipt && empty($i->receipt_url))->count(),
                    'unapproved'       => $items->filter(fn($i) => $i->category?->requires_approval && !$i->approved_at)->count(),
                ];
            })
            ->sortByDesc('count')
            ->values();

        // ── Monthly trend (base query, unfiltered by audit type) ───────
        $monthlyAudit = (clone $baseQuery)
            ->with('category:id,name,requires_receipt,requires_approval')
            ->get()
            ->groupBy(fn($e) => $e->date->format('Y-m'))
            ->map(function ($expenses, $monthKey) {
                [$year, $month] = explode('-', $monthKey);
                return [
                    'year'             => (int) $year,
                    'month'            => (int) $month,
                    'label'            => date('M Y', strtotime($monthKey . '-01')),
                    'total_expenses'   => $expenses->count(),
                    'missing_receipts' => $expenses->filter(fn($i) => $i->category?->requires_receipt && empty($i->receipt_url))->count(),
                    'unapproved'       => $expenses->filter(fn($i) => $i->category?->requires_approval && !$i->approved_at)->count(),
                    'monthly_total'    => (float) $expenses->sum('total_amount'),
                ];
            })
            ->sortByDesc(fn($r) => $r['year'] . '-' . str_pad($r['month'], 2, '0', STR_PAD_LEFT))
            ->values();

        // ── Employee compliance (full set) ─────────────────────────────
        $employeeCompliance = (clone $baseQuery)
            ->whereNotNull('employee_id')
            ->with(['employee:id,first_name,last_name,email', 'category:id,requires_receipt,requires_approval'])
            ->get()
            ->groupBy('employee_id')
            ->map(function ($expenses, $empId) {
                $employee = $expenses->first()->employee;
                return [
                    'id'               => (int) $empId,
                    'employee_name'    => trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? '')),
                    'email'            => $employee->email ?? '',
                    'total_expenses'   => $expenses->count(),
                    'missing_receipts' => $expenses->filter(fn($i) => $i->category?->requires_receipt && empty($i->receipt_url))->count(),
                    'unapproved'       => $expenses->filter(fn($i) => $i->category?->requires_approval && !$i->approved_at)->count(),
                    'avg_expense'      => (float) ($expenses->avg('total_amount') ?? 0),
                ];
            })
            ->sortByDesc('missing_receipts')
            ->values();

        return response()->json([
            'success'             => true,
            'currency'            => currency_symbol(),
            'summary'             => $auditStats,
            'items'               => $itemsPayload,
            'by_category'         => $byCategory,
            'monthly'             => $monthlyAudit,
            'employee_compliance' => $employeeCompliance,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
                'has_more'     => $paginator->hasMorePages(),
            ],
            'meta' => [
                'start_date' => $startDate,
                'end_date'   => $endDate,
                'audit_type' => $auditType,
                'threshold'  => $threshold,
            ],
        ]);
    }

    public function auditMeta(Request $request)
    {
        $user     = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('expense reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('expense_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }

        $isAdmin         = $user->hasAnyRole(['super_admin', 'admin']);
        $userLocationIds = $user->locations()->pluck('locations.id')->toArray();

        $employees = Employee::where('tenant_id', $tenantId)
            ->where('is_active', true)->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

        $locationsQuery = Location::where('tenant_id', $tenantId)->where('is_active', 1)->orderBy('name');
        if (!$isAdmin) $locationsQuery->whereIn('id', $userLocationIds);
        $locations = $locationsQuery->get(['id', 'name']);

        return response()->json([
            'success'     => true,
            'employees'   => $employees,
            'locations'   => $locations,
            'audit_types' => [
                'all'               => __('accounting.all_items'),
                'missing_receipts'  => __('accounting.missing_receipts'),
                'unapproved'        => __('accounting.unapproved_expenses'),
                'high_value'        => __('accounting.high_value_expenses'),
                'late_submissions'  => __('accounting.late_submissions'),
                'policy_violations' => __('accounting.policy_violations'),
            ],
            'currency'    => currency_symbol(),
        ]);
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
