<?php
// app/Http/Controllers/Procurement/ExpenseTemplateController.php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Models\{ Expense, ExpenseTemplate, Tax, ExpenseCategory, Supplier, Location, Department,
    PaymentMethod,  };
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{ Auth, DB };

class ExpenseTemplateController extends Controller
{

    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasPermissionTo('view expense')) {
            abort(403);
        }

        $tenantId        = $user->tenant_id;
        $isAdmin         = $user->hasAnyRole(['super_admin', 'admin']);
        $userLocationIds = $user->locations()->pluck('locations.id')->toArray();

        // ── Per-page (validated) ───────────────────────────────────
        $perPage        = (int) $request->input('per_page', 15);
        $allowedPerPage = [15, 25, 50, 100];
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 15;
        }

        // ── Base query ─────────────────────────────────────────────
        $query = ExpenseTemplate::with(['category', 'supplier', 'department', 'location'])
            ->where('tenant_id', $tenantId)
            ->where('is_active', true);

        if (!$isAdmin) {
            $query->where(function ($q) use ($userLocationIds) {
                $q->whereNull('location_id')
                ->orWhereIn('location_id', $userLocationIds);
            });
        }

        // ── Filters ────────────────────────────────────────────────
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                ->orWhere('code', 'like', "%{$s}%")
                ->orWhere('description', 'like', "%{$s}%");
            });
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('frequency')) {
            $query->where('frequency', $request->frequency);
        }
        if ($request->filled('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        // ── All templates (paginated) ──────────────────────────────
        $templates = (clone $query)
            ->orderByDesc('last_used_at')
            ->orderBy('name')
            ->paginate($perPage);

        // ★ Preserve filters AND per_page across page links
        $templates->appends([
            'search'      => $request->search,
            'category_id' => $request->category_id,
            'frequency'   => $request->frequency,
            'location_id' => $request->location_id,
            'per_page'    => $perPage,
        ]);

        // ── Quick Log ──────────────────────────────────────────────
        $quickLog = (clone $query)
            ->whereNotNull('last_used_at')
            ->orderByDesc('usage_count')
            ->orderByDesc('last_used_at')
            ->limit(6)
            ->get();

        // ── Likely Due ─────────────────────────────────────────────
        $likelyDue = $this->computeLikelyDue($tenantId, $userLocationIds, $isAdmin);

        // ── Filter dropdowns ───────────────────────────────────────
        $categories = ExpenseCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $locations = Location::where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->when(!$isAdmin, fn($q) => $q->whereIn('id', $userLocationIds))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('procurement.expense-template.index', compact(
            'templates', 'quickLog', 'likelyDue',
            'categories', 'locations', 'perPage'
        ));
    }

    /**
     * Templates whose expected interval has elapsed since last use.
     */
    private function computeLikelyDue(int $tenantId, array $userLocationIds, bool $isAdmin)
    {
        $template = ExpenseTemplate::with(['category', 'location'])
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereNotNull('last_used_at')
            ->whereIn('frequency', ['daily', 'weekly', 'biweekly', 'monthly', 'quarterly', 'annually']);

        if (!$isAdmin) {
            $template->where(function ($q) use ($userLocationIds) {
                $q->whereNull('location_id')
                ->orWhereIn('location_id', $userLocationIds);
            });
        }

        return $template->get()->filter(function ($t) {
            $days = match ($t->frequency) {
                'daily'     => 1,
                'weekly'    => 7,
                'biweekly'  => 14,
                'monthly'   => 30,
                'quarterly' => 90,
                'annually'  => 365,
                default     => 0,
            };
            return $days > 0 && $t->last_used_at->diffInDays(now()) >= $days;
        })->values();
    }

    public function logForm(ExpenseTemplate $template)
    {
        $user = Auth::user();
        abort_unless($template->tenant_id === $user->tenant_id, 403);
        abort_unless($user->hasPermissionTo('create expense'), 403);

        $tenantId = $user->tenant_id;
        $isAdmin = $user->hasAnyRole(['super_admin', 'admin']);
        $userLocationIds = $user->locations()->pluck('locations.id')->toArray();

        // Payment methods — scoped for non-admins just like the expense index
        $paymentMethods = PaymentMethod::where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->when(!$isAdmin, function ($q) use ($userLocationIds) {
                $q->where(function ($sub) use ($userLocationIds) {
                    $sub->whereNull('location_id');
                    foreach ($userLocationIds as $lid) {
                        $sub->orWhereRaw('JSON_CONTAINS(location_id, ?)', [json_encode((string) $lid)]);
                    }
                });
            })
            ->orderBy('name')
            ->get(['id', 'name', 'is_default']);

        // Locations the user can pick for this occurrence
        $locations = Location::where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->when(!$isAdmin, fn($q) => $q->whereIn('id', $userLocationIds))
            ->orderBy('name')
            ->get(['id', 'name']);

        // Departments — if the template has a location, limit to it;
        // otherwise show all departments the user can access.
        $departmentQuery = Department::where('tenant_id', $tenantId)
            ->where('isActive', 1);

        if ($template->location_id) {
            $departmentQuery->where('location_id', $template->location_id);
        }

        $departments = $departmentQuery->orderBy('name')->get(['id', 'name', 'location_id']);

        // Tax defaults, resolved to live rows
        $defaultTaxes = collect();
        if (!empty($template->default_tax_ids)) {
            $defaultTaxes = Tax::whereIn('id', $template->default_tax_ids)
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->get(['id', 'name', 'rate', 'type', 'is_withholding_tax']);
        }

        return response()->json([
            'template' => [
                'id'                  => $template->id,
                'name'                => $template->name,
                'description'         => $template->description,
                'category_id'         => $template->category_id,
                'category_name'       => $template->category->name ?? null,
                'supplier_id'         => $template->supplier_id,
                'supplier_name'       => $template->supplier->name ?? null,
                'department_id'       => $template->department_id,
                'department_name'     => $template->department->name ?? null,
                'location_id'         => $template->location_id,
                'location_name'       => $template->location->name ?? null,
                'employee_id'         => $template->employee_id,
                'default_amount'      => $template->default_amount,
                'last_amount'         => $template->last_amount,
                'suggested_amount'    => $template->suggestedAmount(),
                'requires_receipt'    => (bool) $template->requires_receipt,
                'requires_approval'   => (bool) $template->requires_approval,
                'usage_count'         => $template->usage_count,
                'last_used_at'        => optional($template->last_used_at)->format('Y-m-d'),
            ],
            'payment_methods' => $paymentMethods,
            'locations'       => $locations,
            'departments'     => $departments,
            'default_taxes'   => $defaultTaxes,
        ]);
    }

    /**
     * Show the "Log Again" modal for a specific template.
     */
    public function showLogForm(ExpenseTemplate $template)
    {
        $user = Auth::user();
        abort_unless($template->tenant_id === $user->tenant_id, 403);

        return response()->json([
            'template' => [
                'id'              => $template->id,
                'name'            => $template->name,
                'category_name'   => $template->category->name ?? null,
                'supplier_name'   => $template->supplier->name ?? null,
                'department_name' => $template->department->name ?? null,
                'location_name'   => $template->location->name ?? null,
                'suggested_amount'=> $template->suggestedAmount(),
                'last_used_at'    => optional($template->last_used_at)->format('Y-m-d'),
                'usage_count'     => $template->usage_count,
            ],
        ]);
    }

    /**
     * Create an expense from a template. Only amount + date are required.
     */
    public function logOccurrence(Request $request, ExpenseTemplate $template)
    {
        $user = Auth::user();
        abort_unless($template->tenant_id === $user->tenant_id, 403);

        if (!$user->hasPermissionTo('create expense')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ], 403);
        }

        $validated = $request->validate([
            'gross_amount'      => 'required|numeric|min:0.01',
            'date'              => 'required|date',
            'paid_date'         => 'nullable|date|after_or_equal:date',
            'payment_status'    => 'required|in:pending,paid,reimbursed',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'location_id'       => 'nullable|exists:locations,id',
            'department_id'     => 'nullable|exists:departments,id',
            'notes'             => 'nullable|string|max:255',
        ]);

        $tenantId = $template->tenant_id;

        // Verify the payment method belongs to this tenant
        $paymentMethod = PaymentMethod::where('id', $validated['payment_method_id'])
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->first();

        if (!$paymentMethod) {
            return response()->json([
                'success' => false,
                'message' => __('pagination.payment_method_not_found'),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $taxIds = collect($template->default_tax_ids ?? [])->filter()->toArray();

            $grossAmount    = (float) $validated['gross_amount'];
            $additiveTax    = 0;
            $withholdingTax = 0;
            $taxBreakdown   = [];

            if (!empty($taxIds)) {
                $taxes = Tax::whereIn('id', $taxIds)
                    ->where('tenant_id', $tenantId)
                    ->where('is_active', true)
                    ->get();

                foreach ($taxes as $tax) {
                    $taxAmount = $tax->type === Tax::TYPE_PERCENTAGE
                        ? $grossAmount * ($tax->rate / 100)
                        : $tax->rate;

                    if ($tax->is_withholding_tax) {
                        $withholdingTax += $taxAmount;
                    } else {
                        $additiveTax += $taxAmount;
                    }

                    $taxBreakdown[] = [
                        'tax_id'             => $tax->id,
                        'tax_name'           => $tax->name,
                        'tax_code'           => $tax->code,
                        'rate'               => $tax->rate,
                        'type'               => $tax->type,
                        'amount'             => $taxAmount,
                        'is_withholding_tax' => $tax->is_withholding_tax,
                    ];
                }
            }

            $totalTax    = $additiveTax + $withholdingTax;
            $netAmount   = $grossAmount + $additiveTax - $withholdingTax;
            $totalAmount = $grossAmount + $additiveTax;

            // ─── Retry loop for expense number collision ─────────────
            $expense = null;
            $maxAttempts = 5;

            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                try {
                    $expenseNumber = $this->generateExpenseNumber($tenantId);

                    $expense = Expense::create([
                        'tenant_id'         => $tenantId,
                        'template_id'       => $template->id,
                        'expense_number'    => $expenseNumber,
                        'description'       => $template->description ?? $template->name,
                        'gross_amount'      => $grossAmount,
                        'tax_amount'        => $totalTax,
                        'net_amount'        => $netAmount,
                        'total_amount'      => $totalAmount,
                        'supplier_id'       => $template->supplier_id,
                        'vendor_name'       => $template->supplier?->name,
                        'category_id'       => $template->category_id,
                        'department_id'     => $validated['department_id'] ?? $template->department_id,
                        'location_id'       => $validated['location_id']   ?? $template->location_id,
                        'employee_id'       => $template->employee_id,
                        'date'              => $validated['date'],
                        'paid_date'         => $validated['paid_date'] ?? null,
                        'payment_method_id' => $paymentMethod->id,
                        'payment_status'    => $validated['payment_status'],
                        'receipt_url'       => null,
                        'tax_breakdown'     => json_encode($taxBreakdown),
                        'created_by'        => $user->id,
                    ]);

                    break; // success — exit the loop

                } catch (\Illuminate\Database\QueryException $e) {
                    // MySQL duplicate key = 1062
                    $isDuplicate = ($e->errorInfo[1] ?? null) === 1062;

                    if (! $isDuplicate || $attempt === $maxAttempts) {
                        throw $e;   // real error or out of retries
                    }

                    // Tiny backoff — gives the other transaction time to commit
                    usleep(50_000 * $attempt);   // 50ms, 100ms, 150ms, 200ms
                    continue;
                }
            }

            if (! $expense) {
                throw new \RuntimeException('Could not generate a unique expense number after retries.');
            }

            $template->markUsed($grossAmount);

            DB::commit();

            return response()->json([
                'success'        => true,
                'message'        => __('auth.expense_created'),
                'expense_id'     => $expense->id,
                'expense_number' => $expense->expense_number,
                'reload'         => true,
                'componentId'    => 'reloadExpenseComponent',
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Failed to log template occurrence', [
                'template_id' => $template->id,
                'error'       => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => __('auth.error_occurred') . ': ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a template from an existing expense (bulk import path).
     */
    public function storeFromExpense(Request $request, Expense $expense)
    {
        $user = Auth::user();
        abort_unless($expense->tenant_id === $user->tenant_id, 403);

        $template = ExpenseTemplate::create([
            'tenant_id'        => $expense->tenant_id,
            'name'             => $expense->description,
            'category_id'      => $expense->category_id,
            'supplier_id'      => $expense->supplier_id,
            'department_id'    => $expense->department_id,
            'location_id'      => $expense->location_id,
            'employee_id'      => $expense->employee_id,
            'payment_method_id'=> $expense->payment_method_id,
            'default_amount'   => $expense->gross_amount,
            'last_amount'      => $expense->gross_amount,
            'frequency'        => $request->input('frequency', 'random'),
            'default_tax_ids'  => $expense->tax_breakdown
                ? collect(json_decode($expense->tax_breakdown, true))->pluck('tax_id')->filter()->values()->all()
                : null,
            'usage_count'      => 1,
            'last_used_at'     => $expense->created_at,
            'created_by'       => $user->id,
        ]);

        return response()->json([
            'success'  => true,
            'template' => $template,
            'message'  => __('auth._created'),
        ]);
    }

    private function generateExpenseNumber($tenantId): string
    {
        $prefix = 'EXP-' . date('ym');

        // Get the highest numeric suffix currently in the DB for this tenant + prefix
        $lastNumber = DB::table('expenses')
            ->where('tenant_id', $tenantId)
            ->where('expense_number', 'like', $prefix . '-%')
            ->selectRaw("MAX(CAST(SUBSTRING_INDEX(expense_number, '-', -1) AS UNSIGNED)) as max_num")
            ->value('max_num');

        $next = ((int) $lastNumber) + 1;

        return $prefix . '-' . str_pad($next, 5, '0', STR_PAD_LEFT);
    }

        /**
     * Return the list of templates the current user can bulk-log.
     * Used by the Bulk Log modal's template picker (fetched via AJAX).
     */
    public function availableTemplates(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasPermissionTo('create expense')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ], 403);
        }

        $tenantId        = $user->tenant_id;
        $isAdmin         = $user->hasAnyRole(['super_admin', 'admin']);
        $userLocationIds = $user->locations()->pluck('locations.id')->toArray();

        $query = ExpenseTemplate::with(['category', 'supplier', 'location', 'department'])
            ->where('tenant_id', $tenantId)
            ->where('is_active', true);

        if (!$isAdmin) {
            $query->where(function ($q) use ($userLocationIds) {
                $q->whereNull('location_id')
                  ->orWhereIn('location_id', $userLocationIds);
            });
        }

        $templates = $query->orderBy('name')->get()->map(function ($t) {
            return [
                'id'              => $t->id,
                'name'            => $t->name,
                'category_name'   => $t->category->name   ?? '',
                'supplier_name'   => $t->supplier->name   ?? '',
                'location_name'   => $t->location->name   ?? '',
                'department_name' => $t->department->name ?? '',
                'last_amount'     => $t->last_amount,
                'default_amount'  => $t->default_amount,
                'suggested_amount'=> $t->suggestedAmount(),
            ];
        });

        return response()->json([
            'success'   => true,
            'templates' => $templates,
        ]);
    }

    /**
     * Create multiple expenses at once from templates.
     * Accepts a single shared date + payment method, plus an array of
     * { template_id, gross_amount, notes } items (max 10).
     */
    public function bulkLog(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasPermissionTo('create expense')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ], 403);
        }

        $tenantId = $user->tenant_id;

        $validated = $request->validate([
            'date'                => 'required|date',
            'payment_method_id'   => 'required|exists:payment_methods,id',
            'items'               => 'required|array|min:1|max:10',
            'items.*.template_id' => 'required|exists:expense_templates,id',
            'items.*.gross_amount'=> 'required|numeric|min:0.01',
            'items.*.notes'       => 'nullable|string|max:255',
        ]);

        // Verify the payment method belongs to this tenant
        $paymentMethod = PaymentMethod::where('id', $validated['payment_method_id'])
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->first();

        if (!$paymentMethod) {
            return response()->json([
                'success' => false,
                'message' => __('pagination.payment_method_not_found'),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $createdNumbers = [];
            $createdIds     = [];

            foreach ($validated['items'] as $item) {
                $template = ExpenseTemplate::where('id', $item['template_id'])
                    ->where('tenant_id', $tenantId)
                    ->first();

                if (!$template) {
                    throw new \RuntimeException("Template #{$item['template_id']} not found.");
                }

                $grossAmount = (float) $item['gross_amount'];

                // ── Tax calculation (same cascade as logOccurrence) ──
                $taxIds         = collect($template->default_tax_ids ?? [])->filter()->toArray();
                $additiveTax    = 0;
                $withholdingTax = 0;
                $taxBreakdown   = [];

                if (!empty($taxIds)) {
                    $taxes = Tax::whereIn('id', $taxIds)
                        ->where('tenant_id', $tenantId)
                        ->where('is_active', true)
                        ->get();

                    foreach ($taxes as $tax) {
                        $taxAmount = $tax->type === Tax::TYPE_PERCENTAGE
                            ? $grossAmount * ($tax->rate / 100)
                            : $tax->rate;

                        if ($tax->is_withholding_tax) {
                            $withholdingTax += $taxAmount;
                        } else {
                            $additiveTax += $taxAmount;
                        }

                        $taxBreakdown[] = [
                            'tax_id'             => $tax->id,
                            'tax_name'           => $tax->name,
                            'tax_code'           => $tax->code,
                            'rate'               => $tax->rate,
                            'type'               => $tax->type,
                            'amount'             => $taxAmount,
                            'is_withholding_tax' => $tax->is_withholding_tax,
                        ];
                    }
                }

                $totalTax    = $additiveTax + $withholdingTax;
                $netAmount   = $grossAmount + $additiveTax - $withholdingTax;
                $totalAmount = $grossAmount + $additiveTax;

                // ── Retry loop for expense_number collision ──
                $expense = null;
                $maxAttempts = 5;

                for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                    try {
                        $expenseNumber = $this->generateExpenseNumber($tenantId);

                        $expense = Expense::create([
                            'tenant_id'         => $tenantId,
                            'template_id'       => $template->id,
                            'expense_number'    => $expenseNumber,
                            'description'       => $item['notes']
                                                    ? $template->name . ' — ' . $item['notes']
                                                    : ($template->description ?? $template->name),
                            'gross_amount'      => $grossAmount,
                            'tax_amount'        => $totalTax,
                            'net_amount'        => $netAmount,
                            'total_amount'      => $totalAmount,
                            'supplier_id'       => $template->supplier_id,
                            'vendor_name'       => $template->supplier?->name,
                            'category_id'       => $template->category_id,
                            'department_id'     => $template->department_id,
                            'location_id'       => $template->location_id,
                            'employee_id'       => $template->employee_id,
                            'date'              => $validated['date'],
                            'paid_date'         => null,
                            'payment_method_id' => $paymentMethod->id,
                            'payment_status'    => 'pending',
                            'receipt_url'       => null,
                            'tax_breakdown'     => json_encode($taxBreakdown),
                            'created_by'        => $user->id,
                        ]);
                        break;
                    } catch (\Illuminate\Database\QueryException $e) {
                        $isDuplicate = ($e->errorInfo[1] ?? null) === 1062;

                        if (! $isDuplicate || $attempt === $maxAttempts) {
                            throw $e;
                        }

                        usleep(50_000 * $attempt);
                    }
                }

                if (!$expense) {
                    throw new \RuntimeException("Could not create expense for template #{$template->id}");
                }

                $template->markUsed($grossAmount);

                $createdNumbers[] = $expense->expense_number;
                $createdIds[]     = $expense->id;
            }

            DB::commit();

            $count = count($createdNumbers);

            return response()->json([
                'success'         => true,
                'message'         => trans_choice(
                    'auth.bulk_log_success_message',
                    $count,
                    ['count' => $count]
                ),
                'created'         => $count,
                'expense_ids'     => $createdIds,
                'expense_numbers' => $createdNumbers,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            \Log::error('Bulk log failed', [
                'tenant_id' => $tenantId,
                'user_id'   => $user->id,
                'error'     => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('auth.error_occurred') . ': ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the bulk-create modal form.
     */
    public function create()
    {
        $user = Auth::user();
        if (!$user->hasPermissionTo('create expense')) {
            abort(403);
        }
        $tenantId = $user->tenant_id;

        $categories  = ExpenseCategory::where('tenant_id', $tenantId)
                            ->where('is_active', true)->orderBy('name')
                            ->get(['id', 'name', 'code']);

        $suppliers   = Supplier::where('tenant_id', $tenantId)
                            ->orderBy('name')->get(['id', 'name']);

        $locations   = Location::where('tenant_id', $tenantId)
                            ->where('is_active', 1)->orderBy('name')->get(['id', 'name']);

        $departments = Department::where('tenant_id', $tenantId)
                            ->where('isActive', 1)->orderBy('name')->get(['id', 'name']);

        // ✅ $paymentMethods is NOT passed — see below for why
        return view('procurement.expense-template.bulk-create', compact(
            'categories', 'suppliers', 'locations', 'departments'
        ));
    }

    /**
     * Bulk-create templates from a comma/newline-separated list of names.
     */
    public function bulkStore(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasPermissionTo('create expense')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ], 403);
        }

        $tenantId = $user->tenant_id;

        $validated = $request->validate([
            // The textarea — accept both commas and newlines
            'names_raw'         => 'required|string|max:20000',

            // Shared defaults (all optional except category)
            'category_id'       => 'required|exists:expense_categories,id',
            'supplier_id'       => 'nullable|exists:suppliers,id',
            // 'payment_method_id' => 'nullable|exists:payment_methods,id',

            // Location / department defaults (used to pre-fill Log Again)
            'location_id'       => 'nullable|exists:locations,id',
            'department_id'     => 'nullable|exists:departments,id',
            'employee_id'       => 'nullable|exists:employees,id',

            // Frequency default applied to all
            'frequency'         => 'required|in:once,daily,weekly,biweekly,monthly,quarterly,annually,random',

            // Amount hint (optional)
            'default_amount'    => 'nullable|numeric|min:0',

            // Behaviour flags
            'requires_receipt'  => 'boolean',
            'requires_approval' => 'boolean',
        ]);

        // ─── 1. Parse the names ─────────────────────────────────────
        $raw = $validated['names_raw'];

        // Split on commas, newlines, and semicolons
        $names = preg_split('/[\r\n,;]+/', $raw);

        // Clean each name
        $names = collect($names)
            ->map(fn($n) => trim($n))
            ->filter(fn($n) => $n !== '' && mb_strlen($n) >= 2)
            ->unique(fn($n) => mb_strtolower($n))       // dedupe case-insensitively
            ->values()
            ->all();

        if (empty($names)) {
            return response()->json([
                'success' => false,
                'message' => __('auth.no_valid_names_found'),
            ], 422);
        }

        // ─── 2. Verify the referenced IDs belong to this tenant ────
        $category = ExpenseCategory::where('id', $validated['category_id'])
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => __('auth.category_not_found'),
            ], 422);
        }

        if (!empty($validated['supplier_id'])) {
            $ok = Supplier::where('id', $validated['supplier_id'])
                ->where('tenant_id', $tenantId)->exists();
            if (!$ok) {
                return response()->json([
                    'success' => false,
                    'message' => __('auth.supplier_not_found'),
                ], 422);
            }
        }

        if (!empty($validated['location_id'])) {
            $ok = Location::where('id', $validated['location_id'])
                ->where('tenant_id', $tenantId)->exists();
            if (!$ok) {
                return response()->json([
                    'success' => false,
                    'message' => __('auth.location_not_found'),
                ], 422);
            }
        }

        if (!empty($validated['department_id'])) {
            $ok = Department::where('id', $validated['department_id'])
                ->where('tenant_id', $tenantId)->exists();
            if (!$ok) {
                return response()->json([
                    'success' => false,
                    'message' => __('auth.department_not_found'),
                ], 422);
            }
        }

        // ─── 3. Insert in one transaction ───────────────────────────
        $created = 0;
        $skipped = 0;
        $failed  = [];

        DB::beginTransaction();
        try {
            foreach ($names as $name) {
                // Skip if a template with this name already exists in this tenant
                if (ExpenseTemplate::where('tenant_id', $tenantId)
                        ->where('name', $name)
                        ->exists()) {
                    $skipped++;
                    continue;
                }

                try {
                    ExpenseTemplate::create([
                        'tenant_id'          => $tenantId,
                        'name'               => $name,
                        'description'        => null,
                        'category_id'        => $validated['category_id'],
                        'supplier_id'        => $validated['supplier_id']       ?? null,
                        'department_id'      => $validated['department_id']     ?? null,
                        'location_id'        => $validated['location_id']       ?? null,
                        'employee_id'        => $validated['employee_id']       ?? null,
                        'payment_method_id'  => $validated['payment_method_id'] ?? null,
                        'default_amount'     => $validated['default_amount']    ?? null,
                        'frequency'          => $validated['frequency'],
                        'requires_receipt'   => $validated['requires_receipt']  ?? true,
                        'requires_approval'  => $validated['requires_approval'] ?? false,
                        'default_tax_ids'    => null,
                        'usage_count'        => 0,
                        'is_active'          => true,
                        'created_by'         => $user->id,
                    ]);
                    $created++;
                } catch (\Throwable $e) {
                    $failed[] = "{$name}: {$e->getMessage()}";
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Bulk template creation failed', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => __('auth.error_occurred') . ': ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'created' => $created,
            'skipped' => $skipped,
            'failed'  => $failed,
            'message' => trans_choice(
                'auth.templates_created_message',
                $created,
                ['count' => $created, 'skipped' => $skipped]
            ),
            'reload'      => true,
            'componentId' => 'reloadExpenseTemplateComponent',
        ]);
    }


}