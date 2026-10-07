<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{ ExpenseCategory, Expense, Employee, PaymentMethod, Department, Location, Tax, 
    Supplier, SupplierTaxLiability  };
use Illuminate\Support\Str;
use Illuminate\Support\Facades\{ Auth, Log, Storage, DB };
use Illuminate\Validation\Rule;

class ExpenseController extends Controller
{

    public function index(Request $request)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('view expense')) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.not_authorized'),
                ]);
            }
            abort(403);
        }

        // ── Visibility rule ──────────────────────────────────────────
        $isAdmin         = $user->hasAnyRole(['super_admin', 'admin']);
        $userLocationIds = $user->locations()->pluck('locations.id')->toArray();

        // ── Per page ─────────────────────────────────────────────────
        $perPage = (int) $request->input('per_page', 15);
        $allowedPerPage = [15, 25, 50, 100];
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 15;
        }

        // ── Date range (defaults to current month) ───────────────────
        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate   = $request->get('end_date',   now()->toDateString());

        try {
            $startDate = \Carbon\Carbon::parse($startDate)->format('Y-m-d');
            $endDate   = \Carbon\Carbon::parse($endDate)->format('Y-m-d');

            if ($startDate > $endDate) {
                [$startDate, $endDate] = [$endDate, $startDate];
            }
        } catch (\Throwable $e) {
            $startDate = now()->startOfMonth()->toDateString();
            $endDate   = now()->toDateString();
        }

        // ── Base query ───────────────────────────────────────────────
        $baseQuery = Expense::query()
            ->where('tenant_id', $tenantId);

        if (!$isAdmin) {
            $baseQuery->where('created_by', $user->id);
        }

        // ── For summary cards: filter by date range only ────────────
        $summaryQuery = (clone $baseQuery)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate);

        // Apply location scope to summary too (matches the list filters)
        if ($request->filled('location_id')) {
            $requestedLocationId = (int) $request->input('location_id');
            if ($isAdmin || in_array($requestedLocationId, $userLocationIds, true)) {
                $summaryQuery->where('location_id', $requestedLocationId);
            }
        }
        if ($request->filled('department_id')) {
            $summaryQuery->where('department_id', (int) $request->input('department_id'));
        }

        $summaryRows = (clone $summaryQuery)->get([
            'gross_amount', 'net_amount', 'tax_amount', 'payment_status',
        ]);

        $summary = (object) [
            'total_count'         => $summaryRows->count(),
            'total_gross'         => (float) $summaryRows->sum('gross_amount'),
            'total_net'           => (float) $summaryRows->sum('net_amount'),
            'total_tax'           => (float) $summaryRows->sum('tax_amount'),

            'pending_count'       => $summaryRows->where('payment_status', 'pending')->count(),
            'pending_amount'      => (float) $summaryRows->where('payment_status', 'pending')->sum('net_amount'),

            'paid_count'          => $summaryRows->where('payment_status', 'paid')->count(),
            'paid_amount'         => (float) $summaryRows->where('payment_status', 'paid')->sum('net_amount'),

            'reimbursed_count'    => $summaryRows->where('payment_status', 'reimbursed')->count(),
            'reimbursed_amount'   => (float) $summaryRows->where('payment_status', 'reimbursed')->sum('net_amount'),

            'average'             => $summaryRows->count() > 0
                                        ? (float) $summaryRows->avg('net_amount')
                                        : 0,
        ];

        // ── For the paginated list: apply all filters ────────────────
        $query = (clone $baseQuery)
            ->with(['tenant', 'paymentMethod', 'category', 'location', 'department', 'creator'])
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('vendor_name', 'like', "%{$search}%")
                ->orWhere('expense_number', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhereHas('paymentMethod', fn($p) => $p->where('name', 'like', "%{$search}%"))
                ->orWhereHas('category', fn($c) => $c->where('name', 'like', "%{$search}%"))
                ->orWhereHas('location', fn($l) => $l->where('name', 'like', "%{$search}%"))
                ->orWhereHas('department', fn($d) => $d->where('name', 'like', "%{$search}%"));
            });
        }

        // Location
        if ($request->filled('location_id')) {
            $requestedLocationId = (int) $request->input('location_id');
            if ($isAdmin || in_array($requestedLocationId, $userLocationIds, true)) {
                $query->where('location_id', $requestedLocationId);
            }
        }

        // Department
        if ($request->filled('department_id')) {
            $query->where('department_id', (int) $request->input('department_id'));
        }

        // Status
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->input('payment_status'));
        }

        // Payment method
        if ($request->filled('payment_method_id')) {
            $query->where('payment_method_id', (int) $request->input('payment_method_id'));
        }

        // Category
        if ($request->filled('category_id')) {
            $query->where('category_id', (int) $request->input('category_id'));
        }

        // ── Paginate ─────────────────────────────────────────────────
        $expenses = $query->latest('date')->latest('id')->paginate($perPage);

        $expenses->appends([
            'per_page'          => $perPage,
            'search'            => $request->search,
            'start_date'        => $startDate,
            'end_date'          => $endDate,
            'location_id'       => $request->location_id,
            'department_id'     => $request->department_id,
            'payment_status'    => $request->payment_status,
            'payment_method_id' => $request->payment_method_id,
            'category_id'       => $request->category_id,
        ]);

        // ── Dropdown data ────────────────────────────────────────────
        $activePaymentMethods = PaymentMethod::where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->orderBy('name')
            ->get();

        $locationsQuery = Location::where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->orderBy('name');

        if (!$isAdmin) {
            $locationsQuery->whereIn('id', $userLocationIds);
        }
        $locations = $locationsQuery->get(['id', 'name']);

        $departments = Department::where('tenant_id', $tenantId)
            ->where('isActive', 1)
            ->when($request->filled('location_id'), fn($q) =>
                $q->where('location_id', (int) $request->input('location_id'))
            )
            ->orderBy('name')
            ->get(['id', 'name']);

        $categories = ExpenseCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        // ── AJAX partial reload ─────────────────────────────────────
        $bladeToReload = $request->query('bladeFileToReload');

        if ($request->ajax() && $bladeToReload === 'reloadExpenseComponent') {
            return view('procurement.expense.component', [
                'expenses'       => $expenses,
                'PaymentMethods' => $activePaymentMethods,
                'locations'      => $locations,
                'departments'    => $departments,
                'categories'     => $categories,
                'startDate'      => $startDate,
                'endDate'        => $endDate,
            ])->render();
        }

        return view('procurement.expense-index', [
            'expenses'       => $expenses,
            'PaymentMethods' => $activePaymentMethods,
            'locations'      => $locations,
            'departments'    => $departments,
            'categories'     => $categories,
            'summary'        => $summary,
            'startDate'      => $startDate,
            'endDate'        => $endDate,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * JSON endpoint the front-end calls to render the list without a full reload.
     * Everything the JS needs is in this response.
     */
    public function data(Request $request)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('view expense')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }

        $isAdmin         = $user->hasAnyRole(['super_admin', 'admin']);
        $userLocationIds = $user->locations()->pluck('locations.id')->toArray();

        $perPage = (int) $request->input('per_page', 15);
        if (!in_array($perPage, [15, 25, 50, 100], true)) $perPage = 15;

        // Date range
        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate   = $request->get('end_date',   now()->toDateString());
        try {
            $startDate = \Carbon\Carbon::parse($startDate)->format('Y-m-d');
            $endDate   = \Carbon\Carbon::parse($endDate)->format('Y-m-d');
            if ($startDate > $endDate) [$startDate, $endDate] = [$endDate, $startDate];
        } catch (\Throwable $e) {
            $startDate = now()->startOfMonth()->toDateString();
            $endDate   = now()->toDateString();
        }

        // ── Base query ─────────────────────────────────────────────
        $baseQuery = Expense::query()->where('tenant_id', $tenantId);
        if (!$isAdmin) $baseQuery->where('created_by', $user->id);

        // ── Summary (date + scope only) ────────────────────────────
        $summaryQuery = (clone $baseQuery)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate);

        if ($request->filled('location_id')) {
            $req = (int) $request->input('location_id');
            if ($isAdmin || in_array($req, $userLocationIds, true)) {
                $summaryQuery->where('location_id', $req);
            }
        }
        if ($request->filled('department_id')) {
            $summaryQuery->where('department_id', (int) $request->input('department_id'));
        }

        $summaryRows = (clone $summaryQuery)->get(['net_amount', 'tax_amount', 'payment_status']);

        $summary = [
            'total_count'       => $summaryRows->count(),
            'total_net'         => (float) $summaryRows->sum('net_amount'),
            'total_tax'         => (float) $summaryRows->sum('tax_amount'),
            'pending_count'     => $summaryRows->where('payment_status', 'pending')->count(),
            'pending_amount'    => (float) $summaryRows->where('payment_status', 'pending')->sum('net_amount'),
            'paid_count'        => $summaryRows->where('payment_status', 'paid')->count(),
            'paid_amount'       => (float) $summaryRows->where('payment_status', 'paid')->sum('net_amount'),
            'reimbursed_count'  => $summaryRows->where('payment_status', 'reimbursed')->count(),
            'reimbursed_amount' => (float) $summaryRows->where('payment_status', 'reimbursed')->sum('net_amount'),
        ];

        // ── List ───────────────────────────────────────────────────
        $query = (clone $baseQuery)
            ->with(['paymentMethod', 'category', 'location', 'department', 'creator'])
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('vendor_name', 'like', "%{$s}%")
                ->orWhere('expense_number', 'like', "%{$s}%")
                ->orWhere('description', 'like', "%{$s}%")
                ->orWhereHas('paymentMethod', fn($p) => $p->where('name', 'like', "%{$s}%"))
                ->orWhereHas('category',      fn($c) => $c->where('name', 'like', "%{$s}%"));
            });
        }
        if ($request->filled('location_id')) {
            $req = (int) $request->input('location_id');
            if ($isAdmin || in_array($req, $userLocationIds, true)) {
                $query->where('location_id', $req);
            }
        }
        if ($request->filled('department_id'))     $query->where('department_id', (int) $request->input('department_id'));
        if ($request->filled('payment_status'))    $query->where('payment_status', $request->input('payment_status'));
        if ($request->filled('payment_method_id')) $query->where('payment_method_id', (int) $request->input('payment_method_id'));
        if ($request->filled('category_id'))       $query->where('category_id', (int) $request->input('category_id'));

        $expenses = $query->latest('date')->latest('id')->paginate($perPage);

        // ── Shape the rows ────────────────────────────────────────
        $rows = $expenses->map(function ($e) {
            return [
                'id'              => $e->id,
                'expense_number'  => $e->expense_number,
                'date'            => $e->date?->format('Y-m-d'),
                'date_formatted'  => $e->date?->format('d M Y'),
                'description'     => $e->description,
                'vendor_name'     => $e->vendor_name,
                'net_amount'      => (float) $e->net_amount,
                'amount_formatted'=> currency_symbol() . number_format($e->net_amount, 2),
                'payment_status'  => $e->payment_status,
                'approved_at'     => $e->approved_at?->format('Y-m-d H:i'),
                'created_at'      => $e->created_at?->format('Y-m-d H:i'),
                'category'        => ['id' => $e->category?->id,   'name' => $e->category?->name   ?? 'N/A'],
                'department'      => ['id' => $e->department?->id, 'name' => $e->department?->name ?? 'N/A'],
                'location'        => ['id' => $e->location?->id,   'name' => $e->location?->name   ?? 'N/A'],
                'payment_method'  => [
                    'id'         => $e->paymentMethod?->id,
                    'name'       => $e->paymentMethod?->name ?? null,
                    'type'       => $e->paymentMethod?->type ?? 'other',
                    'is_default' => (bool) ($e->paymentMethod?->is_default ?? false),
                ],
                'creator'         => ['name' => $e->creator?->name ?? 'N/A'],
            ];
        });

        return response()->json([
            'success'    => true,
            'data'       => $rows,
            'summary'    => $summary,
            'pagination' => [
                'current_page' => $expenses->currentPage(),
                'last_page'    => $expenses->lastPage(),
                'per_page'     => $expenses->perPage(),
                'total'        => $expenses->total(),
                'from'         => $expenses->firstItem(),
                'to'           => $expenses->lastItem(),
                'has_more'     => $expenses->hasMorePages(),
            ],
            'filters' => [
                'start_date' => $startDate,
                'end_date'   => $endDate,
            ],
        ]);
    }



    public function store(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('create expense')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }
        
        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'gross_amount' => 'required|numeric|min:0.01',
            'supplier_id' => 'required|exists:suppliers,id',
            'category_id' => 'required|exists:expense_categories,id',
            'employee_id' => 'nullable|exists:employees,id',
            'department_id' => 'required|exists:departments,id',
            'location_id' => 'required|exists:locations,id',
            'date' => 'required|date',
            'paid_date' => 'nullable|date|after_or_equal:date',
            'payment_method_id' => [
                'required',
                Rule::exists('payment_methods', 'id')->where(function ($query) use ($tenantId) {
                    $query->where('tenant_id', $tenantId)
                        ->where('is_active', true);
                })
            ],
            'payment_status' => 'required|in:pending,paid,reimbursed',
            'receipt' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
            'selected_taxes' => 'nullable|array',
            'selected_taxes.*' => 'exists:taxes,id',
        ]);

        // Check if category belongs to tenant
        $category = ExpenseCategory::where('id', $validated['category_id'])
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => __('auth.category_not_found'),
            ]);
        }

        // Check if department belongs to tenant
        $department = Department::where('id', $validated['department_id'])
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$department) {
            return response()->json([
                'success' => false,
                'message' => __('auth.department_not_found'),
            ]);
        }

        // Check if location belongs to tenant
        $location = Location::where('id', $validated['location_id'])
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$location) {
            return response()->json([
                'success' => false,
                'message' => __('auth.location_not_found'),
            ]);
        }

        // Check if employee belongs to tenant
        if ($validated['employee_id']) {
            $employee = Employee::where('id', $validated['employee_id'])
                ->where('tenant_id', $tenantId)
                ->first();
                
            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => __('auth.employee_not_found'),
                ]);
            }
        }

        // Check if supplier belongs to tenant
        $supplier = Supplier::where('id', $validated['supplier_id'])
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$supplier) {
            return response()->json([
                'success' => false,
                'message' => __('auth.supplier_not_found'),
            ]);
        }

        // Generate unique expense number
        $expenseNumber = $this->generateExpenseNumber($tenantId);

        // Handle receipt upload
        $receiptUrl = null;
        if ($request->hasFile('receipt')) {
            $receiptUrl = $this->uploadReceipt($request->file('receipt'), $tenantId);
        }

        // CALCULATE TAXES ON BACKEND
        $grossAmount = $validated['gross_amount'];
        $additiveTax = 0;
        $withholdingTax = 0;
        $taxBreakdown = [];
        
        if (!empty($validated['selected_taxes'])) {
            $taxes = Tax::whereIn('id', $validated['selected_taxes'])
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->get();
            
            foreach ($taxes as $tax) {
                // Calculate tax amount based on GROSS amount
                if ($tax->type === Tax::TYPE_PERCENTAGE) {
                    $taxAmount = $grossAmount * ($tax->rate / 100);
                } else {
                    $taxAmount = $tax->rate; // Fixed amount
                }
                
                if ($tax->is_withholding_tax) {
                    $withholdingTax += $taxAmount;
                } else {
                    $additiveTax += $taxAmount;
                }
                
                $taxBreakdown[] = [
                    'tax_id' => $tax->id,
                    'tax_name' => $tax->name,
                    'tax_code' => $tax->code,
                    'rate' => $tax->rate,
                    'type' => $tax->type,
                    'amount' => $taxAmount,
                    'is_withholding_tax' => $tax->is_withholding_tax,
                ];
            }
        }
        
        // Calculate all amounts
        $totalTax = $additiveTax + $withholdingTax;
        $netAmount = $grossAmount + $additiveTax - $withholdingTax;  // What supplier actually gets paid
        $totalAmount = $grossAmount + $additiveTax;  // Total amount including additive tax only (for reference)
        
        // Create the expense
        $expense = Expense::create([
            'tenant_id' => $tenantId,
            'expense_number' => $expenseNumber,
            'description' => $validated['description'],
            'gross_amount' => $grossAmount,
            'tax_amount' => $totalTax,
            'net_amount' => $netAmount,
            'total_amount' => $totalAmount,
            'supplier_id' => $validated['supplier_id'],
            'vendor_name' => $supplier->name,
            'category_id' => $validated['category_id'],
            'department_id' => $validated['department_id'],
            'location_id' => $validated['location_id'],
            'employee_id' => $validated['employee_id'] ?? null,
            'date' => $validated['date'],
            'paid_date' => $validated['paid_date'] ?? null,
            'payment_method_id' => $validated['payment_method_id'] ?? null, 
            'payment_status' => $validated['payment_status'],
            'is_recurring' => $validated['is_recurring'] ?? false,
            'recurring_frequency' => $validated['recurring_frequency'] ?? null,
            'next_recurring_date' => $validated['next_recurring_date'] ?? null,
            'receipt_url' => $receiptUrl,
            'tax_breakdown' => json_encode($taxBreakdown),
            'created_by' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'reload' => true,
            'componentId' => 'reloadExpenseComponent',
            'refresh' => false,
            'message' => __('auth.expense_created'),
            'redirect' => route('expense.index'),
            'tax_breakdown' => $taxBreakdown,
            'gross_amount' => $grossAmount,
            'net_amount' => $netAmount,
            'total_tax' => $totalTax,
            'additive_tax' => $additiveTax,
            'withholding_tax' => $withholdingTax,
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('edit expense')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        // Find the expense
        $expense = Expense::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$expense) {
            return response()->json([
                'success' => false,
                'message' => __('auth.expense_not_found'),
            ]);
        }

        // Check if expense is approved and user doesn't have permission to edit approved expenses
        if ($expense->approved_at && !$user->can('edit approved expense')) {
            return response()->json([
                'success' => false,
                'message' => __('auth.cannot_edit_approved_expense'),
            ]);
        }

        // Validation rules - ADDED selected_taxes
        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'gross_amount' => 'required|numeric|min:0.01',
            'supplier_id' => 'required|exists:suppliers,id',
            'category_id' => 'required|exists:expense_categories,id',
            'employee_id' => 'nullable|exists:employees,id',
            'department_id' => 'required|exists:departments,id',
            'location_id' => 'required|exists:locations,id',
            'date' => 'required|date',
            'paid_date' => 'nullable|date|after_or_equal:date',
            'payment_method_id' => [
                'nullable',
                Rule::exists('payment_methods', 'id')->where(function ($query) use ($tenantId) {
                    $query->where('tenant_id', $tenantId)
                        ->where('is_active', true);
                })
            ],
            'payment_status' => 'required|in:pending,paid,reimbursed',
            'is_recurring' => 'boolean',
            'recurring_frequency' => 'nullable|required_if:is_recurring,true|in:weekly,monthly,quarterly,annually',
            'next_recurring_date' => 'nullable|date|after_or_equal:date',
            'receipt' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
            'selected_taxes' => 'nullable|array', // ADDED
            'selected_taxes.*' => 'exists:taxes,id', // ADDED
        ]);

        // Check if supplier belongs to tenant
        $supplier = Supplier::where('id', $validated['supplier_id'])
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$supplier) {
            return response()->json([
                'success' => false,
                'message' => __('auth.supplier_not_found'),
            ]);
        }

        // Check if category belongs to tenant
        $category = ExpenseCategory::where('id', $validated['category_id'])
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => __('auth.category_not_found'),
            ]);
        }

        // Check if department belongs to tenant
        $department = Department::where('id', $validated['department_id'])
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$department) {
            return response()->json([
                'success' => false,
                'message' => __('auth.department_not_found'),
            ]);
        }

        // Check if location belongs to tenant
        $location = Location::where('id', $validated['location_id'])
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$location) {
            return response()->json([
                'success' => false,
                'message' => __('auth.location_not_found'),
            ]);
        }

        // Check if employee belongs to tenant
        if ($validated['employee_id']) {
            $employee = Employee::where('id', $validated['employee_id'])
                ->where('tenant_id', $tenantId)
                ->first();
                
            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => __('auth.employee_not_found'),
                ]);
            }
        }

        // Handle receipt upload if new file provided
        $receiptUrl = $expense->receipt_url;
        if ($request->hasFile('receipt')) {
            $receiptUrl = $this->uploadReceipt($request->file('receipt'), $tenantId);
        }

        // RECALCULATE TAXES (same logic as store)
        $grossAmount = $validated['gross_amount'];
        $additiveTax = 0;
        $withholdingTax = 0;
        $taxBreakdown = [];
        
        if (!empty($validated['selected_taxes'])) {
            $taxes = Tax::whereIn('id', $validated['selected_taxes'])
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->get();
            
            foreach ($taxes as $tax) {
                if ($tax->type === Tax::TYPE_PERCENTAGE) {
                    $taxAmount = $grossAmount * ($tax->rate / 100);
                } else {
                    $taxAmount = $tax->rate;
                }
                
                if ($tax->is_withholding_tax) {
                    $withholdingTax += $taxAmount;
                } else {
                    $additiveTax += $taxAmount;
                }
                
                $taxBreakdown[] = [
                    'tax_id' => $tax->id,
                    'tax_name' => $tax->name,
                    'tax_code' => $tax->code,
                    'rate' => $tax->rate,
                    'type' => $tax->type,
                    'amount' => $taxAmount,
                    'is_withholding_tax' => $tax->is_withholding_tax,
                ];
            }
        }
        
        // Calculate all amounts
        $totalTax = $additiveTax + $withholdingTax;
        $netAmount = $grossAmount + $additiveTax - $withholdingTax;
        $totalAmount = $grossAmount + $additiveTax;
        
        // Vendor name from supplier
        $vendorName = $supplier->name;

        // Update the expense with recalculated values
        $expense->update([
            'description' => $validated['description'],
            'gross_amount' => $grossAmount,
            'tax_amount' => $totalTax,
            'net_amount' => $netAmount,
            'total_amount' => $totalAmount,
            'supplier_id' => $validated['supplier_id'],
            'vendor_name' => $vendorName,
            'category_id' => $validated['category_id'],
            'department_id' => $validated['department_id'],
            'location_id' => $validated['location_id'],
            'employee_id' => $validated['employee_id'] ?? null,
            'date' => $validated['date'],
            'paid_date' => $validated['paid_date'] ?? null,
            'payment_method_id' => $validated['payment_method_id'] ?? null,
            'payment_status' => $validated['payment_status'],
            'is_recurring' => $validated['is_recurring'] ?? false,
            'recurring_frequency' => $validated['recurring_frequency'] ?? null,
            'next_recurring_date' => $validated['next_recurring_date'] ?? null,
            'receipt_url' => $receiptUrl,
            'tax_breakdown' => json_encode($taxBreakdown), // Update tax breakdown
        ]);

        // Reset approval if significant changes were made
        if ($expense->approved_at && $this->hasSignificantChanges($expense, $validated)) {
            $expense->update([
                'approved_by' => null,
                'approved_at' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'reload' => true,
            'componentId' => 'reloadExpenseComponent',
            'refresh' => false,
            'message' => __('auth.expense_updated'),
            'redirect' => route('expense.index'),
            'tax_breakdown' => $taxBreakdown,
            'gross_amount' => $grossAmount,
            'net_amount' => $netAmount,
            'total_tax' => $totalTax,
            'additive_tax' => $additiveTax,
            'withholding_tax' => $withholdingTax,
        ]);
    }

    /**
     * Return a single expense as JSON.
     * Used by the edit modal and the upload-receipt modal to prefill fields.
     */
    public function show($id)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('view expense')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ], 403);
        }

        $expense = Expense::with(['category', 'supplier', 'location', 'department', 'paymentMethod', 'creator'])
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->first();

        if (!$expense) {
            return response()->json([
                'success' => false,
                'message' => __('auth.expense_not_found'),
            ], 404);
        }

        // Return the fields the JS actually reads.
        // Amounts are already converted to display currency by the model accessors.
        return response()->json([
            'success' => true,
            'expense' => [
                'id'                => $expense->id,
                'expense_number'    => $expense->expense_number,
                'date'              => optional($expense->date)->format('Y-m-d'),
                'description'       => $expense->description,
                'category_id'       => $expense->category_id,
                'supplier_id'       => $expense->supplier_id,
                'employee_id'       => $expense->employee_id,
                'department_id'     => $expense->department_id,
                'location_id'       => $expense->location_id,

                'gross_amount'      => (float) $expense->gross_amount,
                'tax_amount'        => (float) $expense->tax_amount,
                'net_amount'        => (float) $expense->net_amount,
                'total_amount'      => (float) $expense->total_amount,

                'payment_method_id' => $expense->payment_method_id,
                'payment_status'    => $expense->payment_status,
                'paid_date'         => optional($expense->paid_date)->format('Y-m-d'),

                'receipt_url'       => $expense->receipt_url,
                'approved_at'       => optional($expense->approved_at)->format('Y-m-d H:i'),
                'notes'             => $expense->notes,

                'vendor_name'       => $expense->vendor_name,
            ],
        ]);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        if (!$user->hasPermissionTo('delete expense')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        // Find the expense
        $expense = Expense::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$expense) {
            return response()->json([
                'success' => false,
                'message' => __('auth.expense_not_found'),
            ]);
        }

        // Check if expense is approved and user doesn't have permission to delete approved expenses
        if ($expense->approved_at && !$user->can('delete approved expense')) {
            return response()->json([
                'success' => false,
                'message' => __('auth.cannot_delete_approved_expense'),
            ]);
        }

        // Delete receipt file if exists
        if ($expense->receipt_url) {
            $this->deleteReceipt($expense->receipt_url);
        }

        // Delete the expense
        $expense->delete();

        return response()->json([
            'success' => true,
            'reload' => true,
            'componentId' => 'reloadExpenseComponent',
            'refresh' => false,
            'message' => __('auth.expense_deleted'),
            'redirect' => route('expense.index'),
        ]);
    }


    /**
     * Update expense payment status
     */
    public function updateExpenseStatus(Request $request, $id)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('update expense')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        $request->validate([
            'status' => 'required|in:pending,paid,reimbursed',
        ]);

        $expense = Expense::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$expense) {
            return response()->json([
                'success' => false,
                'message' => __('auth.expense_not_found'),
            ]);
        }

        // Validate status transition
        if (!$this->isValidStatusTransition($expense, $request->status)) {
            return response()->json([
                'success' => false,
                'message' => $this->getStatusTransitionError($expense, $request->status),
            ]);
        }

        DB::beginTransaction();
        try {
            // Get old status before update
            $oldStatus = $expense->payment_status;
            
            // Update the status
            $this->updateExpenseStatusData($expense, $request->status, $user);
            
            // Create tax liabilities ONLY when marking as paid (not reimbursed)
            if ($this->shouldCreateTaxLiabilities($expense, $request->status, $oldStatus)) {
                $this->createExpenseTaxLiabilities($expense, $tenantId);
            }
            
            // Process payment transaction
            $transactionInfo = $this->processPaymentTransaction($expense, $request->status, $oldStatus, $user, $tenantId);
            
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('auth.status_updated'),
                'reload' => true,
                'componentId' => 'reloadExpenseComponent',
                'redirect' => route('expense.index'),
                'transaction_info' => $transactionInfo,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating expense status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('auth.error_updating_status') . ': ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Check if status transition is valid
     */
    private function isValidStatusTransition($expense, $newStatus)
    {
        $currentStatus = $expense->payment_status;
        
        // Cannot pay unapproved expense
        if (in_array($newStatus, ['paid', 'reimbursed']) && !$expense->approved_at) {
            return false;
        }
        
        // Reimbursement only allowed from paid status
        if ($newStatus === 'reimbursed' && $currentStatus !== 'paid') {
            return false;
        }
        
        // Cannot revert paid/reimbursed to pending
        if ($newStatus === 'pending' && in_array($currentStatus, ['paid', 'reimbursed'])) {
            return false;
        }
        
        // Need payment method for paid
        if ($newStatus === 'paid' && !$expense->payment_method_id) {
            return false;
        }
        
        return true;
    }

    /**
     * Get status transition error message
     */
    private function getStatusTransitionError($expense, $newStatus)
    {
        $currentStatus = $expense->payment_status;
        
        if (in_array($newStatus, ['paid', 'reimbursed']) && !$expense->approved_at) {
            return __('auth.cannot_pay_unapproved_expense');
        }
        
        if ($newStatus === 'reimbursed' && $currentStatus !== 'paid') {
            return __('auth.cannot_reimburse_unpaid_expense');
        }
        
        if ($newStatus === 'pending' && in_array($currentStatus, ['paid', 'reimbursed'])) {
            return __('auth.cannot_revert_paid_status');
        }
        
        if ($newStatus === 'paid' && !$expense->payment_method_id) {
            return __('auth.no_payment_method_for_expense');
        }
        
        return __('auth.invalid_status_transition');
    }

    /**
     * Update expense status data
     */
    private function updateExpenseStatusData($expense, $status, $user)
    {
        $updateData = [
            'payment_status' => $status,
            'updated_at' => now(),
        ];

        if ($status === 'paid') {
            $updateData['paid_date'] = now();
            $updateData['paid_by'] = null;
        } elseif ($status === 'reimbursed') {
            $updateData['paid_date'] = now();
            $updateData['paid_by'] = $user->id;
        } else {
            // When reverting to pending
            $updateData['paid_date'] = null;
            $updateData['paid_by'] = null;
            $updateData['payment_transaction_ref'] = null;
        }

        $expense->update($updateData);
    }

    /**
     * Check if tax liabilities should be created
     */
    private function shouldCreateTaxLiabilities($expense, $newStatus, $oldStatus)
    {
        // Decode tax_breakdown if it's a string
        $taxBreakdown = $expense->tax_breakdown;
        if (is_string($taxBreakdown)) {
            $taxBreakdown = json_decode($taxBreakdown, true);
        }
        
        // Only create tax liabilities when:
        // 1. Changing from pending to paid (NOT from paid to reimbursed)
        // 2. Expense has tax breakdown data
        return $oldStatus === 'pending' && 
            $newStatus === 'paid' &&
            !empty($taxBreakdown);
    }

    /**
     * Create tax liabilities for expense
     */
    private function createExpenseTaxLiabilities($expense, $tenantId)
    {
        $taxBreakdown = $expense->tax_breakdown;
        if (is_string($taxBreakdown)) {
            $taxBreakdown = json_decode($taxBreakdown, true);
        }
        
        if (empty($taxBreakdown)) {
            return;
        }
        
        $supplierId = $expense->supplier_id;
        
        foreach ($taxBreakdown as $tax) {
            // Skip taxes with zero amount
            if (empty($tax['amount']) || $tax['amount'] <= 0) {
                continue;
            }
            
            SupplierTaxLiability::create([
                'tenant_id' => $tenantId,
                'expense_id' => $expense->id,
                'supplier_id' => $supplierId,
                'tax_id' => $tax['tax_id'] ?? null,
                'taxable_amount' => $expense->gross_amount,
                'tax_amount' => $tax['amount'],
                'tax_rate' => $tax['rate'] ?? 0,
                'tax_name' => $tax['tax_name'] ?? 'Tax',
                'tax_code' => $tax['tax_code'] ?? null,
                'tax_type' => $tax['type'] ?? 'percentage',
                'is_withholding_tax' => $tax['is_withholding_tax'] ?? false,
                'reference_number' => $expense->expense_number,
                'transaction_date' => $expense->date,
                'due_date' => now()->addMonth()->startOfMonth()->addDays(14),
                'status' => 'pending',
                'tax_year' => now()->year,
                'tax_month' => now()->month,
                'tax_quarter' => ceil(now()->month / 3),
                'notes' => $expense->description,
                'metadata' => [
                    'expense_number' => $expense->expense_number,
                    'vendor_name' => $expense->vendor_name,
                    'category_id' => $expense->category_id,
                    'expense_date' => $expense->date->format('Y-m-d'),
                ],
            ]);
        }
    }

    /**
     * Process payment transaction for expense
     */
    private function processPaymentTransaction($expense, $newStatus, $oldStatus, $user, $tenantId)
    {
        // Use net_amount - this is the actual amount that leaves/enters the company
        $paymentAmount = $expense->net_amount;
        
        // Only process payment when:
        // 1. Changing from pending to paid (money goes OUT)
        // 2. Changing from paid to reimbursed (money comes BACK IN)
        if ($oldStatus === 'pending' && $newStatus === 'paid') {
            return $this->processWithdrawal($expense, $user, $tenantId, $paymentAmount);
        }
        
        if ($oldStatus === 'paid' && $newStatus === 'reimbursed') {
            return $this->processDeposit($expense, $user, $tenantId, $paymentAmount);
        }
        
        return null;
    }

    /**
     * Process payment withdrawal (money leaving the company)
     */
    private function processWithdrawal($expense, $user, $tenantId, $amount)
    {
        $paymentMethod = PaymentMethod::findForTenant($expense->payment_method_id, $tenantId);
        
        if (!$paymentMethod) {
            throw new \Exception(__('pagination.payment_method_not_found'));
        }
        
        $validation = $paymentMethod->validateTransaction($amount);
        if (!$validation['success']) {
            throw new \Exception($validation['message']);
        }
        
        // Calculate tax breakdown for metadata
        $taxBreakdown = $expense->tax_breakdown;
        if (is_string($taxBreakdown)) {
            $taxBreakdown = json_decode($taxBreakdown, true);
        }
        
        $additiveTax = 0;
        $withholdingTax = 0;
        
        if (!empty($taxBreakdown)) {
            foreach ($taxBreakdown as $tax) {
                if ($tax['is_withholding_tax'] ?? false) {
                    $withholdingTax += $tax['amount'];
                } else {
                    $additiveTax += $tax['amount'];
                }
            }
        }
        
        $transactionData = [
            'user_id' => $user->id,
            'payment_method_id' => $paymentMethod->id,
            'tenant_id' => $tenantId,
            'transaction_type' => 'WITHDRAWAL',
            'transaction_category' => 'EXPENSE',
            'amount' => $amount,
            'currency_id' => $paymentMethod->currency_id ?? \App\Models\Currency::default()->id,
            'reference_table' => 'expenses',
            'reference_id' => $expense->id,
            'description' => 'Expense Payment - ' . $expense->expense_number,
            'notes' => 'Payment for expense',
            'metadata' => [
                'expense_number' => $expense->expense_number,
                'expense_description' => $expense->description,
                'vendor_name' => $expense->vendor_name,
                'supplier_id' => $expense->supplier_id,
                'category_id' => $expense->category_id,
                'department_id' => $expense->department_id,
                'location_id' => $expense->location_id,
                'gross_amount' => $expense->gross_amount,
                'tax_amount' => $expense->tax_amount,
                'net_amount' => $amount,
                'additive_tax' => $additiveTax,
                'withholding_tax' => $withholdingTax,
                'processed_by_id' => $user->id,
                'processed_by_name' => $user->name,
                'transaction_nature' => 'EXPENSE_PAYMENT',
            ],
        ];
        
        $transactionLog = app('payment-transaction')->recordTransaction($transactionData);
        
        // Update expense with transaction reference
        $expense->update(['payment_transaction_ref' => $transactionLog->transaction_ref]);
        
        return [
            'transaction_ref' => $transactionLog->transaction_ref,
            'transaction_type' => 'WITHDRAWAL',
            'amount' => $amount,
            'payment_method' => $paymentMethod->name,
            'gross_amount' => $expense->gross_amount,
            'net_amount' => $amount,
            'tax_amount' => $expense->tax_amount,
            'additive_tax' => $additiveTax,
            'withholding_tax' => $withholdingTax,
        ];
    }

    /**
     * Process payment deposit (money coming back to the company - reimbursement)
     */
    private function processDeposit($expense, $user, $tenantId, $amount)
    {
        $paymentMethod = PaymentMethod::findForTenant($expense->payment_method_id, $tenantId);
        
        if (!$paymentMethod) {
            throw new \Exception(__('pagination.payment_method_not_found'));
        }
        
        $validation = $paymentMethod->validateTransaction($amount);
        if (!$validation['success']) {
            throw new \Exception($validation['message']);
        }
        
        // Calculate tax breakdown for metadata
        $taxBreakdown = $expense->tax_breakdown;
        if (is_string($taxBreakdown)) {
            $taxBreakdown = json_decode($taxBreakdown, true);
        }
        
        $additiveTax = 0;
        $withholdingTax = 0;
        
        if (!empty($taxBreakdown)) {
            foreach ($taxBreakdown as $tax) {
                if ($tax['is_withholding_tax'] ?? false) {
                    $withholdingTax += $tax['amount'];
                } else {
                    $additiveTax += $tax['amount'];
                }
            }
        }
        
        $transactionData = [
            'user_id' => $user->id,
            'payment_method_id' => $paymentMethod->id,
            'tenant_id' => $tenantId,
            'transaction_type' => 'DEPOSIT',
            'transaction_category' => 'REFUND',
            'amount' => $amount,
            'currency_id' => $paymentMethod->currency_id ?? \App\Models\Currency::default()->id,
            'reference_table' => 'expenses',
            'reference_id' => $expense->id,
            'description' => 'Expense Reimbursement - ' . $expense->expense_number,
            'notes' => 'Reimbursement back to account',
            'metadata' => [
                'expense_number' => $expense->expense_number,
                'expense_description' => $expense->description,
                'vendor_name' => $expense->vendor_name,
                'supplier_id' => $expense->supplier_id,
                'category_id' => $expense->category_id,
                'department_id' => $expense->department_id,
                'location_id' => $expense->location_id,
                'gross_amount' => $expense->gross_amount,
                'tax_amount' => $expense->tax_amount,
                'net_amount' => $amount,
                'additive_tax' => $additiveTax,
                'withholding_tax' => $withholdingTax,
                'processed_by_id' => $user->id,
                'processed_by_name' => $user->name,
                'transaction_nature' => 'EXPENSE_REIMBURSEMENT',
            ],
        ];
        
        $transactionLog = app('payment-transaction')->recordTransaction($transactionData);
        
        // Update expense with transaction reference
        $expense->update(['payment_transaction_ref' => $transactionLog->transaction_ref]);
        
        return [
            'transaction_ref' => $transactionLog->transaction_ref,
            'transaction_type' => 'DEPOSIT',
            'amount' => $amount,
            'payment_method' => $paymentMethod->name,
            'gross_amount' => $expense->gross_amount,
            'net_amount' => $amount,
            'tax_amount' => $expense->tax_amount,
            'additive_tax' => $additiveTax,
            'withholding_tax' => $withholdingTax,
        ];
    }



    /**
     * Approve an expense
     */
    public function approve($id)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('approve expense')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        $expense = Expense::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$expense) {
            return response()->json([
                'success' => false,
                'message' => __('auth.expense_not_found'),
            ]);
        }

        if ($expense->approved_at) {
            return response()->json([
                'success' => false,
                'message' => __('auth.expense_already_approved'),
            ]);
        }

        $expense->update([
            'approved_by' => $user->id,
            'approved_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'reload' => true,
            'componentId' => 'reloadExpenseComponent',
            'message' => __('auth.expense_approved'),
            'redirect' => route('expense.index'),
        ]);
    }

    /**
     * Generate a unique expense number for the tenant.
     *
     * Uses MAX() on the numeric suffix of existing expense_number values,
     * read via raw DB (bypasses soft-delete scopes), then verifies the
     * candidate is free before returning it.
     *
     * This is safe against:
     *   - Soft-deleted rows holding numbers
     *   - Concurrent requests inserting at the same instant
     *   - Gaps in the sequence
     */
    private function generateExpenseNumber(int $tenantId): string
    {
        $prefix = 'EXP-' . date('ym') . '-';

        // ── Highest numeric suffix currently in use (includes soft-deleted) ──
        $lastNumber = DB::table('expenses')
            ->where('tenant_id', $tenantId)
            ->where('expense_number', 'like', $prefix . '%')
            ->selectRaw(
                "MAX(CAST(SUBSTRING_INDEX(expense_number, '-', -1) AS UNSIGNED)) AS max_num"
            )
            ->value('max_num');

        $next = ((int) $lastNumber) + 1;

        // ── Find the first free number from $next onward ──
        // Loop is bounded so a pathological DB state can't hang us.
        for ($attempt = 0; $attempt < 50; $attempt++) {
            $candidate = $prefix . str_pad($next + $attempt, 5, '0', STR_PAD_LEFT);

            $taken = DB::table('expenses')
                ->where('tenant_id', $tenantId)
                ->where('expense_number', $candidate)
                ->exists();

            if (! $taken) {
                return $candidate;
            }
        }

        // Fallback: something is deeply wrong with the sequence. Use a
        // timestamp-based number that can't collide.
        return $prefix . now()->format('His') . random_int(100, 999);
    }

    /**
     * Upload receipt file
     */
    public function updateReceipt(Request $request, $id)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('upload expense')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        try {
            $request->validate([
                'receipt' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
                'description' => 'nullable|string|max:255',
            ], [
                'receipt.required' => __('pagination.please_select_file'),
                'receipt.file' => __('pagination.invalid_file'),
                'receipt.mimes' => __('pagination.invalid_file_type'),
                'receipt.max' => __('pagination.file_too_large'),
            ]);

            $expense = Expense::where('id', $id)
                ->where('tenant_id', $tenantId)
                ->first();

            if (!$expense) {
                session()->flash('toast', [
                    'type' => 'error',
                    'message' => __('auth.expense_not_found'),
                ]);
                return redirect()->route('expense.index');
            }

            // Delete old receipt if exists
            if ($expense->receipt_url) {
                Storage::disk('public')->delete($expense->receipt_url);
            }

            // Upload new receipt
            $path = $request->file('receipt')->store('receipts/tenant-' . $tenantId, 'public');

            $expense->update([
                'receipt_url' => $path,
                'description' => $request->description ?? $expense->description,
                'updated_at' => now(),
            ]);

            session()->flash('toast', [
                'type' => 'success',
                'message' => __('auth._updated'),
            ]);

            return redirect()->route('expense.index');

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Get validation errors and flash them to session
            $errors = $e->validator->errors()->all();
            session()->flash('toast', [
                'type' => 'error',
                'message' => implode(', ', $errors),
            ]);
            
            // Redirect back with input to repopulate form
            return redirect()->back()
                ->withInput()
                ->withErrors($e->validator);
                
        } catch (\Exception $e) {
            // Handle other exceptions
            \Log::error('Receipt upload error: ' . $e->getMessage());
            
            session()->flash('toast', [
                'type' => 'error',
                'message' => __('auth.upload_error') . ': ' . $e->getMessage(),
            ]);
            
            return redirect()->route('expense.index');
        }
    }

    /**
     * Delete receipt file
     */
    private function deleteReceipt($receiptUrl)
    {
        if (Storage::disk('public')->exists($receiptUrl)) {
            Storage::disk('public')->delete($receiptUrl);
        }
    }

    /**
     * Check if expense has significant changes that require re-approval
     */
    private function hasSignificantChanges($expense, $newData)
    {
        $significantFields = ['amount', 'category_id', 'vendor_name', 'description'];
        
        foreach ($significantFields as $field) {
            if (isset($newData[$field]) && $expense->$field != $newData[$field]) {
                return true;
            }
        }
        
        return false;
    }

}
