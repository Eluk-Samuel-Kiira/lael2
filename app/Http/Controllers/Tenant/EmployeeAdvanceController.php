<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\{ EmployeeAdvance, Currency, EmployeePayment, PaymentMethod, User };
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EmployeeAdvanceController extends Controller
{
    /**
     * Display a listing of employee advances.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasPermissionTo('view employee advance')) {
            abort(403, __('payments.not_authorized'));
        }

        return view('department.employee-advance-index', [
            'initialFilters' => [
                'search'   => (string) $request->get('search', ''),
                'per_page' => in_array((int) $request->get('per_page', 15), [15, 25, 50, 100], true)
                    ? (int) $request->get('per_page', 15)
                    : 15,
            ],
        ]);
    }

    public function data(Request $request)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('view employee advance')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }

        $perPage = (int) $request->get('per_page', 15);
        if (!in_array($perPage, [15, 25, 50, 100], true)) $perPage = 15;

        $query = EmployeeAdvance::with(['employee:id,first_name,last_name,department_id', 'employee.department:id,name'])
            ->where('tenant_id', $tenantId);

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->whereHas('employee', fn($e) => $e->where('first_name', 'like', "%{$s}%")
                                                    ->orWhere('last_name', 'like', "%{$s}%"))
                ->orWhere('purpose', 'like', "%{$s}%")
                ->orWhere('reason', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('employee_id')) {
            $query->where('employee_id', (int) $request->employee_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('advance_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('advance_date', '<=', $request->date_to);
        }

        // Summary over the filtered set
        $all = (clone $query)->get(['id', 'status', 'remaining_amount', 'advance_amount']);
        $summary = [
            'total'            => $all->count(),
            'pending'          => $all->where('status', 'pending')->count(),
            'active'           => $all->whereIn('status', ['approved', 'partially_paid'])->count(),
            'outstanding'      => (float) $all->whereIn('status', ['approved', 'partially_paid'])->sum('remaining_amount'),
            'total_advanced'   => (float) $all->sum('advance_amount'),
        ];
        $summary['recovery_rate'] = $summary['total_advanced'] > 0
            ? round((($summary['total_advanced'] - $summary['outstanding']) / $summary['total_advanced']) * 100)
            : 0;

        $paginator = $query->latest()->paginate($perPage);

        $rows = collect($paginator->items())->map(fn(EmployeeAdvance $a) => [
            'id'                       => $a->id,
            'employee_name'            => trim(($a->employee->first_name ?? '') . ' ' . ($a->employee->last_name ?? '')),
            'employee_initials'        => strtoupper(substr($a->employee->first_name ?? '', 0, 1) . substr($a->employee->last_name ?? '', 0, 1)),
            'department_name'          => $a->employee->department?->name ?? '—',
            'advance_amount'           => (float) $a->advance_amount,
            'remaining_amount'         => (float) $a->remaining_amount,
            'currency_symbol'          => currency_symbol(),
            'advance_date'             => optional($a->advance_date)->format('d M Y'),
            'advance_date_human'       => optional($a->advance_date)->diffForHumans(),
            'deduction_frequency'      => $a->deduction_frequency,
            'deduction_frequency_label'=> $a->deduction_frequency_label ?? ucfirst(str_replace('_', ' ', $a->deduction_frequency)),
            'deduction_day'            => $a->deduction_day,
            'installments'             => (int) $a->installments,
            'installments_paid'        => (int) $a->installments_paid,
            'progress'                 => (int) ($a->progress_percentage ?? 0),
            'status'                   => $a->status,
            'purpose'                  => $a->purpose,
        ]);

        return response()->json([
            'success'    => true,
            'data'       => $rows,
            'summary'    => $summary,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
                'has_more'     => $paginator->hasMorePages(),
            ],
        ]);
    }

    public function drawerData(Request $request, $id)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('view employee advance')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }

        $advance = EmployeeAdvance::with([
                'employee:id,first_name,last_name,email,department_id',
                'employee.department:id,name',
                'approver:id,name',
            ])
            ->where('tenant_id', $tenantId)
            ->find($id);

        if (!$advance) {
            return response()->json(['success' => false, 'message' => __('auth._not_found')], 404);
        }

        $can = [
            'can_edit'    => $user->can('edit employee advance')    && $advance->status === 'pending',
            'can_approve' => $user->can('approve employee advance') && $advance->status === 'pending',
            'can_reject'  => $user->can('reject employee advance')  && $advance->status === 'pending',
            'can_cancel'  => $user->can('cancel employee advance')  && in_array($advance->status, ['approved', 'partially_paid']),
            'can_delete'  => $user->can('delete employee advance')  && $advance->status === 'pending',
        ];

        return response()->json([
            'success' => true,
            'advance' => array_merge([
                'id'                        => $advance->id,
                'employee_id'               => $advance->employee_id,
                'employee_name'             => trim(($advance->employee->first_name ?? '') . ' ' . ($advance->employee->last_name ?? '')),
                'employee_initials'         => strtoupper(substr($advance->employee->first_name ?? '', 0, 1) . substr($advance->employee->last_name ?? '', 0, 1)),
                'employee_email'            => $advance->employee->email ?? '—',
                'department_name'           => $advance->employee->department?->name ?? '—',

                'advance_amount'            => (float) $advance->advance_amount,
                'remaining_amount'          => (float) $advance->remaining_amount,
                'recovered_amount'          => (float) ($advance->advance_amount - $advance->remaining_amount),
                'currency_symbol'           => currency_symbol(),

                'advance_date'              => optional($advance->advance_date)->format('Y-m-d'),
                'advance_date_label'        => optional($advance->advance_date)->format('d F Y'),
                'request_date'              => optional($advance->request_date)->format('d F Y'),
                'approval_date'             => optional($advance->approval_date)->format('d F Y'),

                'deduction_frequency'       => $advance->deduction_frequency,
                'deduction_frequency_label' => $advance->deduction_frequency_label ?? ucfirst(str_replace('_', ' ', $advance->deduction_frequency)),
                'deduction_day'             => $advance->deduction_day,
                'deduction_start_date'      => optional($advance->deduction_start_date)->format('Y-m-d'),
                'installments'              => (int) $advance->installments,
                'installments_paid'         => (int) $advance->installments_paid,
                'installment_amount'        => $advance->installment_amount ? (float) $advance->installment_amount : null,
                'progress'                  => (int) ($advance->progress_percentage ?? 0),

                'applicable_salary_types'   => $advance->applicable_salary_types ?? [],
                'purpose'                   => $advance->purpose,
                'reason'                    => $advance->reason,
                'notes'                     => $advance->notes,
                'status'                    => $advance->status,

                'approved_by_name'          => $advance->approver?->name,
                'rejection_reason'          => $advance->rejection_reason,
                'deduction_history'         => collect($advance->deduction_history ?? [])->map(fn($h) => [
                    'date'            => \Carbon\Carbon::parse($h['deducted_at'])->format('d M Y'),
                    'amount'          => (float) $h['deduction_amount'],
                    'remaining_after' => (float) $h['remaining_after'],
                    'payment_id'      => $h['payment_id'],
                ])->values(),
                'deduction_schedule'        => collect($advance->deduction_schedule ?? [])->map(fn($s) => [
                    'installment'    => $s['installment'],
                    'scheduled_date' => \Carbon\Carbon::parse($s['scheduled_date'])->format('d M Y'),
                    'amount'         => (float) $s['amount'],
                    'status'         => $s['status'],
                ])->values(),

                'created_at'                => optional($advance->created_at)->format('d M Y, H:i'),
                'updated_at'                => optional($advance->updated_at)->format('d M Y, H:i'),

                // for the edit modal
                'payment_methods'           => PaymentMethod::where('tenant_id', $tenantId)
                                                ->where('is_active', true)
                                                ->get(['id', 'name', 'type', 'is_default']),
            ], $can),
        ]);
    }

    /**
     * Show the form for creating a new advance.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created advance in storage.
     */
    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;

            if (!$user->hasPermissionTo('create employee advance')) {
                abort(403, __('payments.not_authorized'));
            }

            $validated = $request->validate([
                'employee_id' => [
                    'required',
                    Rule::exists('employees', 'id')->where(function ($query) use ($tenantId) {
                        $query->where('tenant_id', $tenantId);
                    })
                ],
                'advance_amount' => 'required|numeric|min:0.01',
                'advance_date' => 'required|date',
                'deduction_frequency' => 'required|in:one_time,weekly,monthly,yearly',
                'installments' => 'nullable|required_if:deduction_frequency,weekly,monthly,yearly|integer|min:2|max:12',
                'deduction_day' => 'nullable|integer|min:1|max:31',
                'deduction_start_date' => 'nullable|date|after_or_equal:advance_date',
                'applicable_salary_types' => 'nullable|array',
                'applicable_salary_types.*' => 'in:salary,allowance,bonus,overtime,other',
                'purpose' => 'nullable|string|max:255',
                'reason' => 'required|string|max:500',
                'notes' => 'nullable|string|max:1000',
            ]);

            DB::beginTransaction();

            // Calculate installment amount if applicable
            $installmentAmount = null;
            if (in_array($validated['deduction_frequency'], ['weekly', 'monthly', 'yearly']) && !empty($validated['installments'])) {
                $installmentAmount = $validated['advance_amount'] / $validated['installments'];
            }

            $advance = EmployeeAdvance::create([
                'employee_id' => $validated['employee_id'],
                'tenant_id' => $tenantId,
                'advance_amount' => $validated['advance_amount'],
                'remaining_amount' => $validated['advance_amount'],
                'advance_date' => $validated['advance_date'],
                'request_date' => now(),
                'deduction_frequency' => $validated['deduction_frequency'],
                'installments' => $validated['installments'] ?? 1,
                'installment_amount' => $installmentAmount,
                'deduction_day' => $validated['deduction_day'] ?? null,
                'deduction_start_date' => $validated['deduction_start_date'] ?? null,
                'applicable_salary_types' => $validated['applicable_salary_types'] ?? null,
                'purpose' => $validated['purpose'] ?? null,
                'reason' => $validated['reason'],
                'notes' => $validated['notes'] ?? null,
                'status' => 'pending',
                'created_by' => $user->id,
            ]);

            // Generate deduction schedule
            if (in_array($advance->deduction_frequency, ['weekly', 'monthly', 'yearly'])) {
                $advance->generateDeductionSchedule();
            }

            DB::commit();

            // Send notification to admins
            $this->sendAdvanceRequestNotification($advance);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __('payments.advance_request_submitted'),
                    'advance' => $advance->load('employee'),
                ]);
            }

            session()->flash('toast', [
                'type' => 'success',
                'message' => __('payments.advance_request_submitted'),
            ]);

            return redirect()->route('employee-advance.index')
                ->with('success', __('payments.advance_request_submitted'));

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating advance', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => __('payments.error_creating_advance') . ': ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified advance.
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified advance.
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified advance in storage.
     */
    public function update(Request $request, $id)
    {
        // \Log::info('yes');
        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;

            if (!$user->hasPermissionTo('edit employee advance')) {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.not_authorized'),
                ]);
            }

            $advance = EmployeeAdvance::where('id', $id)
                ->where('tenant_id', $tenantId)
                ->firstOrFail();

            // Only pending advances can be updated
            if ($advance->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.cannot_modify_processed_advance'),
                ]);
            }

            $validated = $request->validate([
                'advance_amount' => 'required|numeric|min:0.01',
                'advance_date' => 'required|date',
                'deduction_frequency' => 'required|in:one_time,weekly,monthly,yearly',
                'installments' => 'nullable|required_if:deduction_frequency,weekly,monthly,yearly|integer|min:2|max:12',
                'deduction_day' => 'nullable|integer|min:1|max:31',
                'deduction_start_date' => 'nullable|date|after_or_equal:advance_date',
                'applicable_salary_types' => 'nullable|array',
                'applicable_salary_types.*' => 'in:salary,allowance,bonus,overtime,other',
                'purpose' => 'nullable|string|max:255',
                'reason' => 'required|string|max:500',
                'notes' => 'nullable|string|max:1000',
            ]);

            DB::beginTransaction();

            // Recalculate installment amount
            $installmentAmount = null;
            if (in_array($validated['deduction_frequency'], ['weekly', 'monthly', 'yearly']) && !empty($validated['installments'])) {
                $installmentAmount = $validated['advance_amount'] / $validated['installments'];
            }

            $advance->update([
                'advance_amount' => $validated['advance_amount'],
                'remaining_amount' => $advance->status === 'pending' ? $validated['advance_amount'] : $advance->remaining_amount,
                'advance_date' => $validated['advance_date'],
                'deduction_frequency' => $validated['deduction_frequency'],
                'installments' => $validated['installments'] ?? 1,
                'installment_amount' => $installmentAmount,
                'deduction_day' => $validated['deduction_day'] ?? null,
                'deduction_start_date' => $validated['deduction_start_date'] ?? null,
                'applicable_salary_types' => $validated['applicable_salary_types'] ?? null,
                'purpose' => $validated['purpose'] ?? null,
                'reason' => $validated['reason'],
                'notes' => $validated['notes'] ?? null,
            ]);

            // Regenerate deduction schedule
            if (in_array($advance->deduction_frequency, ['weekly', 'monthly', 'yearly'])) {
                $advance->generateDeductionSchedule();
            }

            DB::commit();

            // if ($request->wantsJson()) {
            //     return response()->json([
            //         'success' => true,
            //         'message' => __('payments.advance_updated'),
            //         'advance' => $advance->fresh()->load('employee'),
            //     ]);
            // }

            session()->flash('toast', [
                'type' => 'success',
                'message' => __('payments.advance_updated'),
            ]);


            return response()->json([
                'success' => true,
                'reload' => false,
                // 'componentId' => 'reloadExpenseComponent',
                'refresh' => true,
                'redirect' => route('employee-advance.index'),
            ]);

            // return redirect()->route('employee.advances.index')
            //     ->with('success', __('payments.advance_updated'));

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating advance', ['error' => $e->getMessage(), 'id' => $id]);
            
            return response()->json([
                'success' => false,
                'message' => __('payments.error_updating_advance') . ': ' . $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Approve the specified advance.
     */
    public function approve(Request $request, $id)
    {
        try {
            $user     = Auth::user();
            $tenantId = $user->tenant_id;

            if (!$user->hasPermissionTo('approve employee advance')) {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.not_authorized'),
                ], 403);
            }

            $advance = EmployeeAdvance::with(['employee', 'employee.department'])
                ->where('id', $id)
                ->where('tenant_id', $tenantId)
                ->firstOrFail();

            if ($advance->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.advance_already_processed'),
                ], 409);
            }

            $request->validate([
                'payment_method_id' => 'required|exists:payment_methods,id',
            ]);

            $paymentMethod = PaymentMethod::where('id', $request->payment_method_id)
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->first();

            if (!$paymentMethod) {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.payment_method_not_found'),
                ], 404);
            }

            DB::beginTransaction();

            // ── 1. Flip the advance to approved ──
            $advance->update([
                'status'        => 'approved',
                'approved_by'   => $user->id,
                'approved_at'   => now(),
                'approval_date' => now(),
            ]);

            // ── 2. Build the human narrative ──
            // {actor} approved {amountStr} to {employee} as advance from {method} —
            // {recurrence}. Reason: {reason}. Disbursement leaves {method}; recovery expected via payroll.
            $actorName   = $this->actorLabel($user);
            $employeeName = trim(($advance->employee->first_name ?? '') . ' ' . ($advance->employee->last_name ?? ''));
            $deptName    = $advance->employee->department->name ?? null;

            $currency    = $paymentMethod->currency->code ?? 'UGX';
            $amountStr   = number_format((float) $advance->advance_amount, 2) . ' ' . $currency;
            $methodName  = $paymentMethod->name ?? "Method #{$paymentMethod->id}";

            $recovery = $this->advanceRecoveryLabel($advance);   // "a one-time deduction" / "5 monthly deductions of 40,000 UGX"
            $reason   = $advance->purpose ?: ($advance->reason ?: 'no stated reason');

            $narrative = "{$actorName} approved a salary advance of {$amountStr} to "
                . "{$employeeName}"
                . ($deptName ? " ({$deptName})" : '')
                . " from {$methodName}. "
                . "Disbursement leaves the company now; recovery will be via {$recovery}. "
                . "Reason: {$reason}.";

            // ── 3. Record the withdrawal (money leaves the company) ──
            $transactionData = [
                'tenant_id'            => $tenantId,
                'user_id'              => $user->id,
                'payment_method_id'    => $paymentMethod->id,
                'transaction_type'     => 'WITHDRAWAL',
                'transaction_category' => 'ADVANCE',
                'amount'               => $advance->advance_amount,
                'currency_id'          => $paymentMethod->currency_id ?? \App\Models\Currency::default()->id,
                'reference_table'      => 'employee_advances',
                'reference_id'         => $advance->id,
                'description'          => $narrative,
                'notes'                => "Salary advance to {$employeeName} — {$methodName}",
                'metadata' => [
                    'employee_id'          => $advance->employee_id,
                    'employee_name'        => $employeeName,
                    'department'           => $deptName,
                    'advance_id'           => $advance->id,
                    'advance_amount'       => (float) $advance->advance_amount,
                    'advance_date'         => $advance->advance_date?->format('Y-m-d'),
                    'deduction_frequency'  => $advance->deduction_frequency,
                    'installments'         => (int) $advance->installments,
                    'installment_amount'   => $advance->installment_amount ? (float) $advance->installment_amount : null,
                    'purpose'              => $advance->purpose,
                    'reason'               => $advance->reason,
                    'approved_by'          => $user->name,
                    'approved_by_id'       => $user->id,
                    'payment_method_name'  => $methodName,
                    'payment_method_type'  => $paymentMethod->type,
                    'transaction_nature'   => 'ADVANCE_DISBURSEMENT',
                    'narrative'            => $narrative,
                ],
            ];

            $transactionLog = app('payment-transaction')->recordTransaction($transactionData);

            // ── 4. Persist the transaction ref back on the advance ──
            $advance->update(['disbursement_transaction_ref' => $transactionLog->transaction_ref]);

            DB::commit();

            $this->sendAdvanceApprovedNotification($advance);

            if ($request->wantsJson()) {
                return response()->json([
                    'success'   => true,
                    'message'   => __('payments.advance_approved'),
                    'advance'   => $advance->fresh(),
                    'narrative' => $narrative,
                    'transaction' => [
                        'ref'            => $transactionLog->transaction_ref,
                        'amount'         => $advance->advance_amount,
                        'payment_method' => $paymentMethod->name,
                    ],
                ]);
            }

            return redirect()->route('employee-advance.index')
                ->with('success', __('payments.advance_approved'));

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error approving advance', [
                'error' => $e->getMessage(),
                'id'    => $id,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => __('payments.error_approving_advance') . ': ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Human-readable label for the acting user — mirrors the pattern used in
     * ProductionOrderController::actorLabel() so ledger narratives stay consistent
     * across modules.
     */
    private function actorLabel(?User $user): string
    {
        if (!$user) return 'System';
        return $user->name ?: "User #{$user->id}";
    }

    /**
     * Describe how the advance will be recovered, in prose.
     * e.g. "a one-time deduction"
     *      "5 monthly deductions of 40,000 UGX"
     *      "4 weekly deductions of 25,000 UGX, starting 15 Nov 2026"
     */
    private function advanceRecoveryLabel(EmployeeAdvance $advance): string
    {
        $freq = $advance->deduction_frequency;

        if ($freq === 'one_time' || (int) $advance->installments <= 1) {
            return 'a one-time deduction';
        }

        $currency = optional(optional(Currency::default())->code) ?: 'UGX';
        $instAmt  = $advance->installment_amount
            ? number_format((float) $advance->installment_amount, 2) . ' ' . $currency
            : null;

        $freqLabel = rtrim($freq, 's');   // weekly / monthly / yearly

        $start = $advance->deduction_start_date
            ? ', starting ' . $advance->deduction_start_date->format('d M Y')
            : '';

        return $instAmt
            ? "{$advance->installments} {$freqLabel} deductions of {$instAmt}{$start}"
            : "{$advance->installments} {$freqLabel} deductions{$start}";
    }

    /**
     * Reject the specified advance.
     */
    public function reject(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;

            if (!$user->hasPermissionTo('reject employee advance')) {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.not_authorized'),
                ]);
            }

            $request->validate([
                'rejection_reason' => 'required|string|max:500',
            ]);

            $advance = EmployeeAdvance::where('id', $id)
                ->where('tenant_id', $tenantId)
                ->firstOrFail();

            if ($advance->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.advance_already_processed'),
                ]);
            }

            DB::beginTransaction();

            $advance->update([
                'status' => 'rejected',
                'rejected_by' => $user->id,
                'rejected_at' => now(),
                'rejection_reason' => $request->rejection_reason,
            ]);

            DB::commit();

            // Send notification to employee
            $this->sendAdvanceRejectedNotification($advance);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __('payments.advance_rejected'),
                    'advance' => $advance->fresh(),
                ]);
            }

            return redirect()->route('employee.advances.index')
                ->with('success', __('payments.advance_rejected'));

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error rejecting advance', ['error' => $e->getMessage(), 'id' => $id]);
            
            return response()->json([
                'success' => false,
                'message' => __('payments.error_rejecting_advance') . ': ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel the specified advance.
     */
    public function cancel(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;

            if (!$user->hasPermissionTo('cancel employee advance')) {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.not_authorized'),
                ]);
            }

            $advance = EmployeeAdvance::where('id', $id)
                ->where('tenant_id', $tenantId)
                ->firstOrFail();

            // Can only cancel pending or approved but not yet deducted advances
            if (!in_array($advance->status, ['pending', 'approved'])) {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.cannot_cancel_advance'),
                ]);
            }

            DB::beginTransaction();

            $advance->update([
                'status' => 'cancelled',
                'notes' => $request->notes ?? $advance->notes,
            ]);

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __('payments.advance_cancelled'),
                    'advance' => $advance->fresh(),
                ]);
            }

            return redirect()->route('employee.advances.index')
                ->with('success', __('payments.advance_cancelled'));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error cancelling advance', ['error' => $e->getMessage(), 'id' => $id]);
            
            return response()->json([
                'success' => false,
                'message' => __('payments.error_cancelling_advance') . ': ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get advances for an employee - includes both active and previously selected for a payment
     */
    public function getActiveAdvances($employeeId, Request $request)
    {
        \Log::info($employeeId);
        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;
            $paymentType = $request->get('payment_type');
            $paymentId = $request->get('payment_id'); // Add this parameter for edit mode

            $employee = Employee::where('id', $employeeId)
                ->where('tenant_id', $tenantId)
                ->firstOrFail();

            // Base query - get all advances for this employee
            $query = EmployeeAdvance::where('employee_id', $employeeId)
                ->where('tenant_id', $tenantId);

            // For edit mode, we need to include advances that might be fully paid
            // but were part of this payment
            if ($paymentId) {
                // Get the payment to see which advances were used
                $payment = EmployeePayment::find($paymentId);
                $usedAdvanceIds = [];
                
                if ($payment && $payment->advance_deductions) {
                    $deductions = is_string($payment->advance_deductions) 
                        ? json_decode($payment->advance_deductions, true) 
                        : $payment->advance_deductions;
                    
                    $usedAdvanceIds = collect($deductions)->pluck('advance_id')->toArray();
                }
                
                // Include advances that are either:
                // 1. Active (have remaining balance), OR
                // 2. Were used in this payment (even if fully paid)
                $query->where(function($q) use ($usedAdvanceIds) {
                    $q->whereIn('status', ['approved', 'partially_paid']) // active advances
                    ->orWhereIn('id', $usedAdvanceIds); // advances used in this payment
                });
            } else {
                // For create mode, only show active advances
                $query->active();
            }

            // Filter by applicable salary types if payment type is provided
            if ($paymentType) {
                $query->where(function($q) use ($paymentType) {
                    $q->whereJsonContains('applicable_salary_types', $paymentType)
                    ->orWhereNull('applicable_salary_types');
                });
            }

            $advances = $query->orderBy('advance_date', 'desc')->get();

            $formattedAdvances = $advances->map(function($advance) {
                return [
                    'id' => $advance->id,
                    'advance_amount' => $advance->advance_amount,
                    'remaining_amount' => $advance->remaining_amount,
                    'advance_date' => $advance->advance_date->format('Y-m-d'),
                    'deduction_frequency' => $advance->deduction_frequency,
                    'deduction_frequency_label' => $advance->deduction_frequency_label,
                    'installments' => $advance->installments,
                    'installments_paid' => $advance->installments_paid,
                    'installment_amount' => $advance->installment_amount,
                    'progress' => $advance->progress_percentage,
                    'purpose' => $advance->purpose,
                    'status' => $advance->status,
                    'is_fully_paid' => $advance->isFullyPaid(),
                ];
            });

            $note = $paymentType ? __('payments.showing_advances_for') . ' ' . __("payments.{$paymentType}") : '';

            return response()->json([
                'success' => true,
                'advances' => $formattedAdvances,
                'note' => $note,
                'count' => $formattedAdvances->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching advances', ['error' => $e->getMessage(), 'employee_id' => $employeeId]);
            
            return response()->json([
                'success' => false,
                'message' => __('payments.error_fetching_advances'),
            ], 500);
        }
    }

    /**
     * Get advance statistics for dashboard.
     */
    public function getStatistics(Request $request)
    {
        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;

            $statistics = $this->getStatisticsData($tenantId);

            return response()->json([
                'success' => true,
                'stats' => $statistics,
            ]);

        } catch (\Exception $e) {
            Log::error('Error loading advance statistics', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => __('payments.error_loading_statistics'),
            ], 500);
        }
    }

    /**
     * Get advance statistics data.
     */
    private function getStatisticsData($tenantId)
    {
        $activeAdvances = EmployeeAdvance::where('tenant_id', $tenantId)
            ->whereIn('status', ['approved', 'partially_paid'])
            ->get();

        $pendingCount = EmployeeAdvance::where('tenant_id', $tenantId)
            ->where('status', 'pending')
            ->count();

        $totalOutstanding = $activeAdvances->sum('remaining_amount');
        
        $totalAdvanced = EmployeeAdvance::where('tenant_id', $tenantId)
            ->whereIn('status', ['approved', 'partially_paid', 'fully_paid'])
            ->sum('advance_amount');
            
        $totalRecovered = EmployeeAdvance::where('tenant_id', $tenantId)
            ->whereIn('status', ['partially_paid', 'fully_paid'])
            ->get()
            ->sum(function($advance) {
                return $advance->advance_amount - $advance->remaining_amount;
            });

        $recoveryRate = $totalAdvanced > 0 ? round(($totalRecovered / $totalAdvanced) * 100) : 0;

        return [
            'total_outstanding' => $totalOutstanding,
            'pending_count' => $pendingCount,
            'active_count' => $activeAdvances->count(),
            'recovery_rate' => $recoveryRate,
            'total_advanced' => $totalAdvanced,
            'total_recovered' => $totalRecovered,
        ];
    }

    /**
     * Remove the specified advance from storage.
     */
    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;

            if (!$user->hasPermissionTo('delete employee advance')) {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.not_authorized'),
                ]);
            }

            $advance = EmployeeAdvance::where('id', $id)
                ->where('tenant_id', $tenantId)
                ->firstOrFail();

            // Only pending advances can be deleted
            if ($advance->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.cannot_delete_processed_advance'),
                ]);
            }

            $advance->delete();

            if (request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __('payments.advance_deleted'),
                ]);
            }

            return redirect()->route('employee.advances.index')
                ->with('success', __('payments.advance_deleted'));

        } catch (\Exception $e) {
            Log::error('Error deleting advance', ['error' => $e->getMessage(), 'id' => $id]);
            
            return response()->json([
                'success' => false,
                'message' => __('payments.error_deleting_advance') . ': ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export advances to CSV/Excel.
     */
    public function export(Request $request)
    {
        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;

            if (!$user->hasPermissionTo('export employee advance')) {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.not_authorized'),
                ]);
            }

            $query = EmployeeAdvance::with(['employee', 'approver'])
                ->where('tenant_id', $tenantId);

            // Apply filters
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }
            if ($request->has('date_from') && !empty($request->date_from)) {
                $query->whereDate('advance_date', '>=', $request->date_from);
            }
            if ($request->has('date_to') && !empty($request->date_to)) {
                $query->whereDate('advance_date', '<=', $request->date_to);
            }

            $advances = $query->get();

            // Generate CSV
            $filename = 'advances_' . now()->format('Y-m-d_His') . '.csv';
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
            ];

            $callback = function() use ($advances) {
                $file = fopen('php://output', 'w');
                
                // Headers
                fputcsv($file, [
                    'ID',
                    'Employee',
                    'Advance Amount',
                    'Remaining',
                    'Advance Date',
                    'Deduction Frequency',
                    'Installments',
                    'Installments Paid',
                    'Status',
                    'Purpose',
                    'Request Date',
                    'Approval Date',
                ]);

                // Data
                foreach ($advances as $advance) {
                    fputcsv($file, [
                        $advance->id,
                        $advance->employee->full_name,
                        $advance->advance_amount,
                        $advance->remaining_amount,
                        $advance->advance_date->format('Y-m-d'),
                        $advance->deduction_frequency_label,
                        $advance->installments,
                        $advance->installments_paid,
                        $advance->status,
                        $advance->purpose,
                        $advance->request_date->format('Y-m-d'),
                        $advance->approval_date ? $advance->approval_date->format('Y-m-d') : '',
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Error exporting advances', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => __('payments.error_exporting_advances'),
            ], 500);
        }
    }

    /**
     * Send notification for new advance request.
     */
    private function sendAdvanceRequestNotification($advance)
    {
        // Implement notification logic here
        // You can use Laravel notifications, email, etc.
    }

    /**
     * Send notification for approved advance.
     */
    private function sendAdvanceApprovedNotification($advance)
    {
        // Implement notification logic here
    }

    /**
     * Send notification for rejected advance.
     */
    private function sendAdvanceRejectedNotification($advance)
    {
        // Implement notification logic here
    }
}