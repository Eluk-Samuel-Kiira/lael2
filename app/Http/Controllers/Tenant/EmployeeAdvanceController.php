<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\{ EmployeeAdvance, EmployeePayment, PaymentMethod };
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
        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;

            if (!$user->hasPermissionTo('view employee advance')) {
                abort(403, __('payments.not_authorized'));
            }

            $query = EmployeeAdvance::with(['employee', 'approver', 'payment'])
                ->where('tenant_id', $tenantId);

            // Filter by status
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            // Filter by employee
            if ($request->has('employee_id') && !empty($request->employee_id)) {
                $query->where('employee_id', $request->employee_id);
            }

            // Filter by date range
            if ($request->has('date_from') && !empty($request->date_from)) {
                $query->whereDate('advance_date', '>=', $request->date_from);
            }
            if ($request->has('date_to') && !empty($request->date_to)) {
                $query->whereDate('advance_date', '<=', $request->date_to);
            }

            // Search
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->whereHas('employee', function($emp) use ($search) {
                        $emp->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    })
                    ->orWhere('purpose', 'like', "%{$search}%")
                    ->orWhere('reason', 'like', "%{$search}%");
                });
            }

            // Sorting
            $sortField = $request->get('sort_field', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortField, $sortDirection);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $advances = $query->paginate($perPage);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'advances' => $advances,
                    'filters' => $request->all(),
                ]);
            }

            // Get statistics for the view
            $statistics = $this->getStatisticsData($tenantId);

            return view('department.employee-advance-index', [
                'advances' => $advances,
                'statistics' => $statistics,
                'filters' => $request->all(),
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching advances', ['error' => $e->getMessage()]);
            
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.error_fetching_advances'),
                ], 500);
            }

            return back()->with('error', __('payments.error_fetching_advances'));
        }
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
            $user = Auth::user();
            $tenantId = $user->tenant_id;

            if (!$user->hasPermissionTo('approve employee advance')) {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.not_authorized'),
                ]);
            }

            $advance = EmployeeAdvance::where('id', $id)
                ->where('tenant_id', $tenantId)
                ->firstOrFail();

            if ($advance->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.advance_already_processed'),
                ]);
            }

            // Validate payment method
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
                ]);
            }

            DB::beginTransaction();

            // 1. Update advance status
            $advance->update([
                'status' => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
                'approval_date' => now(),
            ]);

            // 2. RECORD WITHDRAWAL - Money LEAVES company as advance
            $transactionData = [
                'tenant_id' => $tenantId,
                'user_id' => $user->id,
                'payment_method_id' => $paymentMethod->id,
                'transaction_type' => 'WITHDRAWAL',
                'transaction_category' => 'ADVANCE',
                'amount' => $advance->advance_amount,
                'currency_id' => $paymentMethod->currency_id ?? \App\Models\Currency::default()->id,
                'reference_table' => 'employee_advances',
                'reference_id' => $advance->id,
                'description' => 'Salary Advance to ' . $advance->employee->first_name . ' ' . $advance->employee->last_name,
                'notes' => 'Advance approved for: ' . ($advance->purpose ?? 'No purpose specified'),
                'metadata' => [
                    'employee_id' => $advance->employee_id,
                    'employee_name' => $advance->employee->first_name . ' ' . $advance->employee->last_name,
                    'advance_date' => $advance->advance_date->format('Y-m-d'),
                    'purpose' => $advance->purpose,
                    'approved_by' => $user->name,
                    'approved_by_id' => $user->id,
                    'transaction_nature' => 'ADVANCE_PAYMENT',
                    'payment_method_name' => $paymentMethod->name,
                    'payment_method_type' => $paymentMethod->type,
                ],
            ];

            $transactionLog = app('payment-transaction')->recordTransaction($transactionData);

            // Store transaction ref on advance
            $advance->update([
                'disbursement_transaction_ref' => $transactionLog->transaction_ref,
            ]);

            DB::commit();

            // Send notification to employee
            $this->sendAdvanceApprovedNotification($advance);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __('payments.advance_approved'),
                    'advance' => $advance->fresh(),
                    'transaction' => [
                        'ref' => $transactionLog->transaction_ref,
                        'amount' => $advance->advance_amount,
                        'payment_method' => $paymentMethod->name,
                    ],
                ]);
            }

            return redirect()->route('employee.advances.index')
                ->with('success', __('payments.advance_approved'));

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error approving advance', [
                'error' => $e->getMessage(),
                'id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => __('payments.error_approving_advance') . ': ' . $e->getMessage(),
            ], 500);
        }
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