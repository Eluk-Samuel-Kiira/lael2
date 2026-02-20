<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{ Employee, EmployeePayment, PaymentMethod };
use Illuminate\Support\Facades\{ Auth, Log, DB };
use Illuminate\Validation\Rule;
use App\Services\TaxCalculationService;

class EmployeePaymentController extends Controller
{
    protected $taxService;

    public function __construct(TaxCalculationService $taxService)
    {
        $this->taxService = $taxService;
    }
    
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        // Build the query
        $query = EmployeePayment::with(['employee', 'tenant', 'paymentMethod']);

        // If user is NOT super_admin, filter by tenant
                if (!$user->hasRole('super_admin')) {
            $query->where('tenant_id', $tenantId);
        }
        // Get data for the create modal and edit modals
        $taxService = app(TaxCalculationService::class);
        $availableTaxes = $taxService->getAvailableTaxes($tenantId);
        
        $payments = $query->latest()->get();

        // Get data for the create modal
        // $availableTaxes = $this->taxService->getAvailableTaxes($tenantId);

        $bladeToReload = $request->query('bladeFileToReload');
        switch ($bladeToReload) {
            case 'reloadPaymentComponent':
                return view('department.employee-payment.component', [
                    'payments' => $payments,
                    'taxes' => $availableTaxes,
                ]);
            default:
                return view('department.employee-payment-index', [
                    'payments' => $payments,
                    'taxes' => $availableTaxes,
                ]);
        }
    }
    
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'payment_date' => 'required|date',
            'payment_type' => 'required|in:salary,allowance,bonus,overtime,advance,other',
            'description' => 'required|string|max:255',
            'gross_amount' => 'required|numeric|min:0.01|decimal:0,2',
            'payment_method_id' => [
                'required',
                Rule::exists('payment_methods', 'id')->where(function ($query) use ($tenantId) {
                    $query->where('tenant_id', $tenantId)
                        ->where('is_active', true);
                })
            ],
            'reference_number' => 'nullable|string|max:100',
            'status' => 'required|in:pending,completed,failed,cancelled',
            'pay_period_start' => 'nullable|date',
            'pay_period_end' => 'nullable|date|after_or_equal:pay_period_start',
            'hours_worked' => 'nullable|numeric|min:0|decimal:0,2',
            'hourly_rate' => 'nullable|numeric|min:0|decimal:0,2',
            'notes' => 'nullable|string|max:500',
            'selected_taxes' => 'nullable|array',
            'selected_taxes.*' => 'exists:taxes,id',
        ]);

        // Check if employee belongs to tenant
        $employee = Employee::where('id', $validated['employee_id'])
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => __('auth.employee_not_found'),
            ]);
        }

        // Calculate amount if hours and rate provided for overtime
        if ($request->payment_type === 'overtime' && $request->hours_worked && $request->hourly_rate) {
            $validated['gross_amount'] = $request->hours_worked * $request->hourly_rate;
        }

        // Calculate taxes if any are selected
        $taxCalculation = null;
        $netAmount = $validated['gross_amount'];
        
        if ($request->has('selected_taxes') && !empty($request->selected_taxes)) {
            $taxCalculation = $this->taxService->calculateTaxes(
                $validated['gross_amount'],
                $request->selected_taxes,
                $tenantId,
                $employee
            );
            
            $netAmount = $taxCalculation['net_amount'];
        }

        // Prepare breakdown data
        $breakdown = [
            'hours_worked' => $request->hours_worked,
            'hourly_rate' => $request->hourly_rate,
            'calculated_gross' => $validated['gross_amount']
        ];

        if ($taxCalculation) {
            $breakdown['taxes'] = $taxCalculation['tax_breakdown'];
            $breakdown['total_tax'] = $taxCalculation['total_tax_amount'];
            $breakdown['net_amount'] = $taxCalculation['net_amount'];
        }

        $payment = EmployeePayment::create([
            'employee_id' => $validated['employee_id'],
            'tenant_id' => $tenantId,
            'payment_date' => $validated['payment_date'],
            'payment_type' => $validated['payment_type'],
            'description' => $validated['description'],
            'gross_amount' => $validated['gross_amount'],
            'net_amount' => $netAmount,
            'amount' => $netAmount, // Store net amount as the actual payment amount
            'total_tax_amount' => $taxCalculation['total_tax_amount'] ?? 0,
            'applied_taxes' => $taxCalculation['applied_taxes'] ?? null,
            'is_tax_computed' => !is_null($taxCalculation),
            'payment_method_id' => $validated['payment_method_id'],
            'reference_number' => $validated['reference_number'] ?? null,
            'status' => $validated['status'],
            'pay_period_start' => $validated['pay_period_start'] ?? null,
            'pay_period_end' => $validated['pay_period_end'] ?? null,
            'hours_worked' => $validated['hours_worked'] ?? null,
            'hourly_rate' => $validated['hourly_rate'] ?? null,
            'breakdown' => $breakdown,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'reload' => true,
            'componentId' => 'reloadPaymentComponent',
            'refresh' => false,
            'message' => __('auth.payment_created'),
            'redirect' => route('payment.index'),
            'payment_summary' => [
                'gross' => number_format($payment->gross_amount, 2),
                'tax' => number_format($payment->total_tax_amount, 2),
                'net' => number_format($payment->net_amount, 2),
            ]
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        $payment = EmployeePayment::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => __('auth.payment_not_found'),
            ]);
        }

        // DEBUG: Log the raw request data
        \Log::info('=== UPDATE PAYMENT DEBUG ===');
        \Log::info('Payment ID: ' . $id);
        \Log::info('Raw request data:', $request->all());
        \Log::info('Selected taxes raw: ' . json_encode($request->input('selected_taxes')));
        \Log::info('Has selected_taxes: ' . ($request->has('selected_taxes') ? 'yes' : 'no'));
        
        $selectedTaxes = $request->input('selected_taxes', []);
        
        // Filter out empty values (like the empty string we might send)
        if (is_array($selectedTaxes)) {
            $selectedTaxes = array_filter($selectedTaxes, function($value) {
                return !is_null($value) && $value !== '';
            });
        }
        
        \Log::info('Selected taxes after filtering:', $selectedTaxes);
        
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'payment_date' => 'required|date',
            'payment_type' => 'required|in:salary,allowance,bonus,overtime,advance,other',
            'description' => 'required|string|max:255',
            'gross_amount' => 'required|numeric|min:0.01',
            'payment_method_id' => [
                'required',
                Rule::exists('payment_methods', 'id')->where(function ($query) use ($tenantId) {
                    $query->where('tenant_id', $tenantId)
                        ->where('is_active', true);
                })
            ],
            'reference_number' => 'nullable|string|max:100',
            'status' => 'required|in:pending,completed,failed,cancelled',
            'pay_period_start' => 'nullable|date',
            'pay_period_end' => 'nullable|date|after_or_equal:pay_period_start',
            'hours_worked' => 'nullable|numeric|min:0',
            'hourly_rate' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
            'selected_taxes' => 'nullable|array',
            'selected_taxes.*' => 'nullable|exists:taxes,id',
        ]);

        \Log::info('Validated data:', $validated);
        \Log::info('Selected taxes after validation:', $validated['selected_taxes'] ?? []);

        // Check if employee belongs to tenant
        $employee = Employee::where('id', $validated['employee_id'])
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => __('auth.employee_not_found'),
            ]);
        }

        // FIXED: Recalculate amount if hours and rate changed for overtime
        // Only recalculate if both values are greater than 0
        if ($request->payment_type === 'overtime') {
            $hoursWorked = floatval($request->hours_worked ?? 0);
            $hourlyRate = floatval($request->hourly_rate ?? 0);
            
            if ($hoursWorked > 0 && $hourlyRate > 0) {
                $validated['gross_amount'] = $hoursWorked * $hourlyRate;
                \Log::info('Recalculated gross for overtime: ' . $validated['gross_amount']);
            } else {
                \Log::info('Overtime but hours/rate are zero or not provided, keeping submitted gross_amount: ' . $validated['gross_amount']);
            }
        }

        // Calculate taxes if any are selected
        $taxCalculation = null;
        $netAmount = $validated['gross_amount']; // Default to gross amount (no taxes)
        $totalTaxAmount = 0;
        $appliedTaxes = null;
        $isTaxComputed = false;
        
        if (!empty($selectedTaxes)) {
            \Log::info('Calling taxService with:', [
                'gross_amount' => $validated['gross_amount'],
                'selected_taxes' => $selectedTaxes,
                'tenant_id' => $tenantId,
                'employee_id' => $employee->id
            ]);
            
            try {
                $taxCalculation = $this->taxService->calculateTaxes(
                    $validated['gross_amount'],
                    $selectedTaxes,
                    $tenantId,
                    $employee
                );
                
                \Log::info('Tax calculation result:', $taxCalculation);
                
                if ($taxCalculation && isset($taxCalculation['net_amount'])) {
                    $netAmount = $taxCalculation['net_amount'];
                    $totalTaxAmount = $taxCalculation['total_tax_amount'] ?? 0;
                    
                    // Prepare applied_taxes JSON
                    if (isset($taxCalculation['applied_taxes'])) {
                        $appliedTaxes = $taxCalculation['applied_taxes'];
                    } elseif (isset($taxCalculation['tax_breakdown'])) {
                        $appliedTaxes = $taxCalculation['tax_breakdown'];
                    }
                    
                    $isTaxComputed = true;
                    
                    \Log::info('Net amount set to: ' . $netAmount);
                    \Log::info('Total tax set to: ' . $totalTaxAmount);
                }
            } catch (\Exception $e) {
                \Log::error('Tax calculation error: ' . $e->getMessage());
                \Log::error($e->getTraceAsString());
                // On error, keep gross as net (no taxes)
            }
        } else {
            \Log::info('No taxes selected, keeping gross amount as net amount');
            // When no taxes are selected, net_amount = gross_amount, total_tax_amount = 0
            $netAmount = $validated['gross_amount'];
            $totalTaxAmount = 0;
            $appliedTaxes = null;
            $isTaxComputed = false;
        }

        // Update breakdown
        $breakdown = [
            'hours_worked' => $request->hours_worked,
            'hourly_rate' => $request->hourly_rate,
            'calculated_gross' => $validated['gross_amount'],
            'updated_at' => now()->toDateTimeString()
        ];

        if ($taxCalculation) {
            $breakdown['taxes'] = $taxCalculation['tax_breakdown'] ?? [];
            $breakdown['total_tax'] = $totalTaxAmount;
            $breakdown['net_amount'] = $netAmount;
        } else {
            $breakdown['taxes'] = [];
            $breakdown['total_tax'] = 0;
            $breakdown['net_amount'] = $netAmount;
        }

        \Log::info('Final values to save:', [
            'gross_amount' => $validated['gross_amount'],
            'net_amount' => $netAmount,
            'total_tax_amount' => $totalTaxAmount,
            'applied_taxes' => json_encode($appliedTaxes),
            'is_tax_computed' => $isTaxComputed
        ]);

        $payment->update([
            'employee_id' => $validated['employee_id'],
            'payment_date' => $validated['payment_date'],
            'payment_type' => $validated['payment_type'],
            'description' => $validated['description'],
            'gross_amount' => $validated['gross_amount'],
            'net_amount' => $netAmount,
            'amount' => $netAmount,
            'total_tax_amount' => $totalTaxAmount,
            'applied_taxes' => $appliedTaxes,
            'is_tax_computed' => $isTaxComputed,
            'payment_method_id' => $validated['payment_method_id'],
            'reference_number' => $validated['reference_number'] ?? null,
            'status' => $validated['status'],
            'pay_period_start' => $validated['pay_period_start'] ?? null,
            'pay_period_end' => $validated['pay_period_end'] ?? null,
            'hours_worked' => $validated['hours_worked'] ?? null,
            'hourly_rate' => $validated['hourly_rate'] ?? null,
            'breakdown' => $breakdown,
            'notes' => $validated['notes'] ?? null,
        ]);

        // Refresh the model to get updated values
        $payment->refresh();

        return response()->json([
            'success' => true,
            'reload' => true,
            'componentId' => 'reloadPaymentComponent',
            'refresh' => false,
            'message' => __('auth.payment_updated'),
            'redirect' => route('payment.index'),
            'payment_summary' => [
                'gross' => number_format($payment->gross_amount, 2),
                'tax' => number_format($payment->total_tax_amount, 2),
                'net' => number_format($payment->net_amount, 2),
            ]
        ]);
    }

    /**
     * Calculate taxes for preview (AJAX endpoint)
     */
    public function calculateTaxPreview(Request $request)
    {
        $request->validate([
            'gross_amount' => 'required|numeric|min:0',
            'selected_taxes' => 'required|array',
            'selected_taxes.*' => 'exists:taxes,id',
            'employee_id' => 'nullable|exists:employees,id',
        ]);

        $tenantId = Auth::user()->tenant_id;
        $employee = null;
        
        if ($request->employee_id) {
            $employee = Employee::find($request->employee_id);
        }

        $calculation = $this->taxService->calculateTaxes(
            $request->gross_amount,
            $request->selected_taxes,
            $tenantId,
            $employee
        );

        return response()->json([
            'success' => true,
            'calculation' => $calculation
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        $payment = EmployeePayment::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => __('auth.payment_not_found'),
            ]);
        }

        // Check if payment is completed and user doesn't have permission
        if ($payment->status === 'completed' && !$user->can('delete completed payment')) {
            return response()->json([
                'success' => false,
                'message' => __('auth.cannot_delete_completed_payment'),
            ]);
        }

        $payment->delete();

        
        return response()->json([
            'success' => true,
            'reload' => true,
            'componentId' => 'reloadPaymentComponent',
            'refresh' => false,
            'message' => __('auth.payment_deleted'),
            'redirect' => route('payment.index'),
        ]);
    }

    public function updatePaymentStatus(Request $request, $id)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        $request->validate([
            'status' => 'required|in:pending,completed,failed,cancelled',
        ]);

        $payment = EmployeePayment::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => __('auth.payment_not_found'),
            ]);
        }

        // Prevent changing from completed to any other status
        if ($payment->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => __('auth.cannot_change_completed_payment'),
            ]);
        }

        // Optional: Prevent changing from failed/cancelled to pending
        if ($request->status === 'pending' && 
            in_array($payment->status, ['failed', 'cancelled'])) {
            return response()->json([
                'success' => false,
                'message' => __('auth.cannot_revert_failed_payment'),
            ]);
        }

        // Optional: Add validation for business logic
        if ($request->status === 'completed') {
            // Check if payment can be marked as completed
            if ($payment->status === 'failed' && $payment->failed_at && 
                now()->diffInDays($payment->failed_at) > 30) {
                return response()->json([
                    'success' => false,
                    'message' => __('auth.cannot_complete_old_failed_payment'),
                ]);
            }
            
            // Check if payment method exists
            if (!$payment->payment_method_id) {
                return response()->json([
                    'success' => false,
                    'message' => __('auth.no_payment_method_for_employee_payment'),
                ]);
            }
        }

        DB::beginTransaction();
        try {
            $updateData = [
                'status' => $request->status,
                'updated_at' => now(),
            ];

            // ✅ PROCESS PAYMENT WITHDRAWAL WHEN STATUS IS COMPLETED
            if ($request->status === 'completed') {
                $paymentMethod = PaymentMethod::findForTenant($payment->payment_method_id, $tenantId);
                
                if (!$paymentMethod) {
                    throw new \Exception(__('pagination.payment_method_not_found'));
                }
                
                // Validate the payment method can handle this transaction
                $validation = $paymentMethod->validateTransaction($payment->amount);
                if (!$validation['success']) {
                    throw new \Exception($validation['message']);
                }

                // Map payment_type from database to transaction_category
                $categoryMap = [
                    'salary' => 'SALARY',
                    'allowance' => 'ALLOWANCE', 
                    'bonus' => 'BONUS',
                    'overtime' => 'OVERTIME',
                    'advance' => 'ADVANCE',
                    'other' => 'OTHER'
                ];

                // Get payment_type and convert to lowercase for mapping
                $paymentType = strtolower($payment->payment_type);
                $transactionCategory = $categoryMap[$paymentType] ?? 'OTHER';
                \Log::info($transactionCategory);

                // Record employee payment withdrawal using PaymentTransactionService
                $transactionData = [
                    'user_id' => $user->id,
                    'payment_method_id' => $paymentMethod->id,
                    'tenant_id' => $tenantId,
                    'transaction_type' => 'WITHDRAWAL',
                    'transaction_category' => $transactionCategory,
                    'amount' => $payment->amount,
                    'currency_id' => $paymentMethod->currency_id ?? \App\Models\Currency::default()->id,
                    'reference_table' => 'employee_payments',
                    'reference_id' => $payment->id,
                    'description' => ucfirst($payment->payment_type) . ' Payment - ' . $payment->description,
                    'notes' => ucfirst($payment->payment_type) . ' payment to ' . ($payment->employee->name ?? 'employee'),
                    'metadata' => [
                        'employee_id' => $payment->employee_id,
                        'employee_name' => $payment->employee->first_name .' '.$payment->employee->last_name  ?? 'Unknown',
                        'payment_type' => $payment->payment_type,
                        'payment_date' => $payment->payment_date,
                        'reference_number' => $payment->reference_number,
                        'pay_period_start' => $payment->pay_period_start,
                        'pay_period_end' => $payment->pay_period_end,
                        'hours_worked' => $payment->hours_worked,
                        'hourly_rate' => $payment->hourly_rate,
                        'processed_by_id' => $user->id,
                        'processed_by_name' => $user->name,
                        'transaction_nature' => 'EMPLOYEE_PAYMENT',
                        'original_payment_type' => $payment->payment_type,
                        'mapped_transaction_category' => $transactionCategory,
                    ],
                ];

                // Record the transaction
                $transactionLog = app('payment-transaction')->recordTransaction($transactionData);
                
                // Add completed_at timestamp
                $updateData['completed_at'] = now();

                \Log::info('Employee payment transaction recorded', [
                    'employee_payment_id' => $payment->id,
                    'payment_type' => $payment->payment_type,
                    'transaction_category' => $transactionCategory,
                    'amount' => $payment->amount,
                    'payment_method' => $paymentMethod->name,
                    'payment_method_id' => $payment->payment_method_id,
                    'transaction_ref' => $transactionLog->transaction_ref ?? 'N/A',
                ]);
            } 
            // If marking as failed or cancelled from pending
            elseif (in_array($request->status, ['failed', 'cancelled']) && $payment->status === 'pending') {
                // Set timestamp for failed or cancelled
                $timestampField = $request->status === 'failed' ? 'failed_at' : 'cancelled_at';
                $updateData[$timestampField] = now();
            }

            $payment->update($updateData);

            DB::commit();

            return response()->json([
                'success' => true,
                'reload' => true,
                'componentId' => 'reloadPaymentComponent',
                'refresh' => false,
                'message' => __('auth.status_updated'),
                'redirect' => route('payment.index'),
                'transaction_info' => isset($transactionLog) ? [
                    'transaction_ref' => $transactionLog->transaction_ref,
                    'transaction_type' => 'WITHDRAWAL',
                    'transaction_category' => $transactionCategory ?? null,
                    'amount' => $payment->amount,
                    'payment_method' => $paymentMethod->name ?? null,
                    'payment_type' => $payment->payment_type,
                ] : null,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating payment status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('auth.error_updating_status') . ': ' . $e->getMessage(),
            ]);
        }
    }
}
