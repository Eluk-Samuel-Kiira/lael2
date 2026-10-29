<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{ TaxLiability, Employee, EmployeePayment, PaymentMethod, EmployeeAdvance };
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

    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasPermissionTo('view employee payment')) {
            abort(403, __('payments.not_authorized'));
        }

        return view('department.employee-payment-index', [
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

        if (!$user->hasPermissionTo('view employee payment')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }

        $perPage = (int) $request->get('per_page', 15);
        if (!in_array($perPage, [15, 25, 50, 100], true)) $perPage = 15;

        $query = EmployeePayment::with(['employee:id,first_name,last_name', 'paymentMethod:id,name,type,is_default'])
            ->where('tenant_id', $tenantId);

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->whereHas('employee', fn($e) => $e->where('first_name', 'like', "%{$s}%")
                                                    ->orWhere('last_name', 'like', "%{$s}%"))
                ->orWhere('description', 'like', "%{$s}%")
                ->orWhere('reference_number', 'like', "%{$s}%");
            });
        }

        foreach (['status', 'payment_type'] as $f) {
            if ($request->filled($f)) $query->where($f, $request->$f);
        }
        foreach (['employee_id', 'payment_method_id'] as $f) {
            if ($request->filled($f)) $query->where($f, (int) $request->$f);
        }
        if ($request->filled('date_from')) $query->whereDate('payment_date', '>=', $request->date_from);
        if ($request->filled('date_to'))   $query->whereDate('payment_date', '<=', $request->date_to);
        if ($request->filled('min_amount')) $query->where('gross_amount', '>=', to_base_currency($request->min_amount));
        if ($request->filled('max_amount')) $query->where('gross_amount', '<=', to_base_currency($request->max_amount));

        // Summary over filtered set
        $all = (clone $query)->get(['id', 'status', 'amount', 'gross_amount', 'total_tax_amount', 'total_advance_deduction', 'payment_date']);
        $monthStart = now()->startOfMonth();
        $monthEnd   = now()->endOfMonth();

        $summary = [
            'total_payments'    => $all->count(),
            'total_amount'      => from_base_currency($all->sum('amount')),
            'total_gross'       => from_base_currency($all->sum('gross_amount')),
            'total_tax'         => from_base_currency($all->sum('total_tax_amount')),
            'total_advance_deductions' => from_base_currency($all->sum('total_advance_deduction')),
            'pending_count'     => $all->where('status', 'pending')->count(),
            'completed_count'   => $all->where('status', 'completed')->count(),
            'failed_count'      => $all->where('status', 'failed')->count(),
            'cancelled_count'   => $all->where('status', 'cancelled')->count(),
            'this_month_count'  => $all->whereBetween('payment_date', [$monthStart, $monthEnd])->count(),
            'this_month_amount' => from_base_currency($all->whereBetween('payment_date', [$monthStart, $monthEnd])->sum('amount')),
            'avg_payment'       => from_base_currency($all->avg('amount')),
        ];

        $paginator = $query->latest()->paginate($perPage);

        $rows = collect($paginator->items())->map(fn(EmployeePayment $p) => [
            'id'                 => $p->id,
            'employee_name'      => trim(($p->employee->first_name ?? '') . ' ' . ($p->employee->last_name ?? '')),
            'payment_type'       => $p->payment_type,
            'payment_type_label' => ucfirst(str_replace('_', ' ', $p->payment_type)),
            'payment_method_name'=> $p->paymentMethod?->name ?? '—',
            'payment_method_type'=> $p->paymentMethod?->type ?? 'other',
            'payment_method_default' => (bool) ($p->paymentMethod?->is_default ?? false),
            'gross_amount'       => (float) $p->gross_amount,
            'net_amount'         => (float) $p->net_amount,
            'total_tax_amount'   => (float) $p->total_tax_amount,
            'total_advance_deduction' => (float) $p->total_advance_deduction,
            'currency_symbol'    => currency_symbol(),
            'payment_date'       => optional($p->payment_date)->format('d M Y'),
            'status'             => $p->status,
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

        if (!$user->hasPermissionTo('view employee payment')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }

        $payment = EmployeePayment::with(['employee:id,first_name,last_name,email', 'paymentMethod:id,name,type,is_default,currency_id', 'paymentMethod.currency:id,code'])
            ->where('tenant_id', $tenantId)
            ->find($id);

        if (!$payment) {
            return response()->json(['success' => false, 'message' => __('auth.payment_not_found')], 404);
        }

        // Safely decode JSON columns
        $appliedTaxes = $payment->applied_taxes;
        if (is_string($appliedTaxes)) $appliedTaxes = json_decode($appliedTaxes, true);
        $appliedTaxes = is_array($appliedTaxes) ? $appliedTaxes : [];

        $advanceDeductions = $payment->advance_deductions;
        if (is_string($advanceDeductions)) $advanceDeductions = json_decode($advanceDeductions, true);
        $advanceDeductions = is_array($advanceDeductions) ? $advanceDeductions : [];

        $can = [
            'can_edit'   => $user->can('edit employee payment')   && $payment->status !== 'completed',
            'can_delete' => $user->can('delete employee payment')
                && !($payment->status === 'completed' && !$user->can('delete completed payment')),
            'can_toggle_status' => $user->can('update employee payment') && $payment->status !== 'completed',
        ];

        return response()->json([
            'success' => true,
            'payment' => array_merge([
                'id'                 => $payment->id,
                'employee_id'        => $payment->employee_id,
                'employee_name'      => trim(($payment->employee->first_name ?? '') . ' ' . ($payment->employee->last_name ?? '')),
                'employee_email'     => $payment->employee->email ?? '—',
                'payment_type'       => $payment->payment_type,
                'payment_type_label' => ucfirst(str_replace('_', ' ', $payment->payment_type)),
                'description'        => $payment->description,
                'payment_date'       => optional($payment->payment_date)->format('Y-m-d'),
                'payment_date_label' => optional($payment->payment_date)->format('d M Y'),
                'pay_period_start'   => optional($payment->pay_period_start)->format('Y-m-d'),
                'pay_period_end'     => optional($payment->pay_period_end)->format('Y-m-d'),

                'payment_method_id'  => $payment->payment_method_id,
                'payment_method_name'=> $payment->paymentMethod?->name ?? '—',
                'payment_method_type'=> $payment->paymentMethod?->type ?? 'other',

                'gross_amount'       => (float) $payment->gross_amount,
                'total_tax_amount'   => (float) $payment->total_tax_amount,
                'total_advance_deduction' => (float) $payment->total_advance_deduction,
                'net_amount'         => (float) $payment->net_amount,
                'amount'             => (float) $payment->amount,
                'currency_symbol'    => currency_symbol(),

                'hours_worked'       => $payment->hours_worked !== null ? (float) $payment->hours_worked : null,
                'hourly_rate'        => $payment->hourly_rate !== null ? (float) $payment->hourly_rate : null,
                'reference_number'   => $payment->reference_number,
                'notes'              => $payment->notes,
                'status'             => $payment->status,

                'applied_taxes'      => $appliedTaxes,
                'advance_deductions' => $advanceDeductions,

                'breakdown'          => is_array($payment->breakdown) ? $payment->breakdown : (json_decode($payment->breakdown ?? '[]', true) ?: []),

                'completed_at'       => optional($payment->completed_at)->format('d M Y, H:i'),
                'failed_at'          => optional($payment->failed_at)->format('d M Y, H:i'),
                'cancelled_at'       => optional($payment->cancelled_at)->format('d M Y, H:i'),
                'created_at'         => optional($payment->created_at)->format('d M Y, H:i'),
                'updated_at'         => optional($payment->updated_at)->format('d M Y, H:i'),
            ], $can),
        ]);
    }

    /**
     * Meta endpoint — feeds the create + edit modals with employees, methods, taxes.
     * Call once per page (cached client-side). Replaces the server-injected view vars.
     */
    public function meta(Request $request)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('view employee payment')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }

        $employees = Employee::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name'])
            ->map(fn($e) => ['id' => $e->id, 'name' => trim($e->first_name . ' ' . $e->last_name)]);

        $paymentMethods = PaymentMethod::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get(['id', 'name', 'type', 'is_default']);

        $taxes = app(\App\Services\TaxCalculationService::class)->getAvailableTaxes($tenantId);

        return response()->json([
            'success'         => true,
            'employees'       => $employees,
            'payment_methods' => $paymentMethods,
            'taxes'           => $taxes,
            'currency_symbol' => currency_symbol(),
        ]);
    }

    /**
     * Get payment statistics
     */
    private function getPaymentStatistics($tenantId, $request)
    {
        $query = EmployeePayment::where('tenant_id', $tenantId);
        
        // Apply same filters to statistics for consistency
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }
        if ($request->has('employee_id') && !empty($request->employee_id)) {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->has('date_from') && !empty($request->date_from)) {
            $query->whereDate('payment_date', '>=', $request->date_from);
        }
        if ($request->has('date_to') && !empty($request->date_to)) {
            $query->whereDate('payment_date', '<=', $request->date_to);
        }

        $today = now();
        $startOfMonth = $today->copy()->startOfMonth();
        $endOfMonth = $today->copy()->endOfMonth();

        return [
            'total_payments' => $query->count(),
            'total_amount' => from_base_currency($query->sum('amount')),
            'total_gross' => from_base_currency($query->sum('gross_amount')),
            'total_tax' => from_base_currency($query->sum('total_tax_amount')),
            'total_advance_deductions' => from_base_currency($query->sum('total_advance_deduction')),
            
            'pending_count' => (clone $query)->where('status', 'pending')->count(),
            'completed_count' => (clone $query)->where('status', 'completed')->count(),
            'failed_count' => (clone $query)->where('status', 'failed')->count(),
            'cancelled_count' => (clone $query)->where('status', 'cancelled')->count(),
            
            'this_month_count' => (clone $query)->whereBetween('payment_date', [$startOfMonth, $endOfMonth])->count(),
            'this_month_amount' => from_base_currency((clone $query)->whereBetween('payment_date', [$startOfMonth, $endOfMonth])->sum('amount')),
            
            'avg_payment' => from_base_currency($query->avg('amount')),
            
            'by_type' => [
                'salary' => (clone $query)->where('payment_type', 'salary')->count(),
                'allowance' => (clone $query)->where('payment_type', 'allowance')->count(),
                'bonus' => (clone $query)->where('payment_type', 'bonus')->count(),
                'overtime' => (clone $query)->where('payment_type', 'overtime')->count(),
                'advance' => (clone $query)->where('payment_type', 'advance')->count(),
            ],
        ];
    }
    
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        if (!$user->hasPermissionTo('create employee payment')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

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

        // If not overtime, force-clear hours/rate so they don't leak into the row
        if (($validated['payment_type'] ?? '') !== 'overtime') {
            $validated['hours_worked'] = null;
            $validated['hourly_rate']  = null;
        }

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

        // Handle advance deductions
        $advanceDeductions = 0;
        $advanceData = [];

        if ($request->has('advance_deductions') && !empty($request->advance_deductions)) {
            $selectedAdvances = json_decode($request->advance_deductions, true);
            
            foreach ($selectedAdvances as $item) {
                $advance = EmployeeAdvance::where('id', $item['advance_id'])
                    ->where('tenant_id', $tenantId)
                    ->where('employee_id', $validated['employee_id'])
                    ->active()
                    ->first();
                
                if ($advance && $advance->appliesToSalaryType($validated['payment_type'])) {
                    $deductionAmount = min($item['deduction_amount'], $advance->remaining_amount);
                    $advanceDeductions += $deductionAmount;
                    $advanceData[] = [
                        'advance_id' => $advance->id,
                        'deduction_amount' => $deductionAmount,
                        'remaining_before' => $advance->remaining_amount,
                    ];
                }
            }
        }

        // Calculate final net amount after taxes and advances
        $finalNetAmount = $netAmount - $advanceDeductions;




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
            'total_advance_deduction' => $advanceDeductions,
            'advance_deductions' => !empty($advanceData) ? json_encode($advanceData) : null,
            'net_amount' => $finalNetAmount,
            'amount' => $finalNetAmount,
        ]);

        
        // After payment is created, record deductions against advances
        if (!empty($advanceData)) {
            foreach ($advanceData as $data) {
                $advance = EmployeeAdvance::find($data['advance_id']);
                if ($advance) {
                    $advance->recordDeduction($data['deduction_amount'], $payment);
                }
            }
        }

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
        if (!$user->hasPermissionTo('update employee payment')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

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
        // \Log::info('=== UPDATE PAYMENT DEBUG ===');
        // \Log::info('Payment ID: ' . $id);
        // \Log::info('Raw request data:', $request->all());
        // \Log::info('Selected taxes raw: ' . json_encode($request->input('selected_taxes')));
        // \Log::info('Has selected_taxes: ' . ($request->has('selected_taxes') ? 'yes' : 'no'));
        
        $selectedTaxes = $request->input('selected_taxes', []);
        
        // Filter out empty values (like the empty string we might send)
        if (is_array($selectedTaxes)) {
            $selectedTaxes = array_filter($selectedTaxes, function($value) {
                return !is_null($value) && $value !== '';
            });
        }
        
        // \Log::info('Selected taxes after filtering:', $selectedTaxes);
        
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
                // \Log::info('Recalculated gross for overtime: ' . $validated['gross_amount']);
            } else {
                // \Log::info('Overtime but hours/rate are zero or not provided, keeping submitted gross_amount: ' . $validated['gross_amount']);
            }
        }

        // Calculate taxes if any are selected
        $taxCalculation = null;
        $netAmount = $validated['gross_amount']; // Default to gross amount (no taxes)
        $totalTaxAmount = 0;
        $appliedTaxes = null;
        $isTaxComputed = false;
        
        if (!empty($selectedTaxes)) {
            // \Log::info('Calling taxService with:', [
            //     'gross_amount' => $validated['gross_amount'],
            //     'selected_taxes' => $selectedTaxes,
            //     'tenant_id' => $tenantId,
            //     'employee_id' => $employee->id
            // ]);
            
            try {
                $taxCalculation = $this->taxService->calculateTaxes(
                    $validated['gross_amount'],
                    $selectedTaxes,
                    $tenantId,
                    $employee
                );
                
                // \Log::info('Tax calculation result:', $taxCalculation);
                
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
            // \Log::info('No taxes selected, keeping gross amount as net amount');
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

        // Recompute advances from the request, and store them
        $advanceDeductionsTotal = 0;
        $advanceData = [];

        $rawAdv = $request->input('advance_deductions');
        if (!empty($rawAdv)) {
            $parsed = is_string($rawAdv) ? json_decode($rawAdv, true) : $rawAdv;
            if (is_array($parsed)) {
                foreach ($parsed as $item) {
                    $advance = EmployeeAdvance::where('id', $item['advance_id'] ?? null)
                        ->where('tenant_id', $tenantId)
                        ->where('employee_id', $validated['employee_id'])
                        ->active()
                        ->first();

                    if (!$advance) continue;

                    // Skip if the advance doesn't apply to this payment type
                    if (method_exists($advance, 'appliesToSalaryType')
                        && !$advance->appliesToSalaryType($validated['payment_type'])) {
                        continue;
                    }

                    $amount = min(
                        (float) ($item['deduction_amount'] ?? 0),
                        (float) $advance->remaining_amount
                    );
                    if ($amount <= 0) continue;

                    $advanceData[] = [
                        'advance_id'       => $advance->id,
                        'deduction_amount' => $amount,
                        'remaining_before' => (float) $advance->remaining_amount,
                    ];
                    $advanceDeductionsTotal += $amount;
                }
            }
        }

        // Final net = net after taxes, minus advance deductions (clamped to >= 0)
        $finalNet = max(0, $netAmount - $advanceDeductionsTotal);

        

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
            'net_amount'              => $finalNet,
            'amount'                  => $finalNet,
            'total_advance_deduction' => $advanceDeductionsTotal,
            'advance_deductions'      => !empty($advanceData) ? $advanceData : null,
        ]);

        session()->flash('toast', [
            'type' => 'success',
            'message' => __('auth.payment_updated'),
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

        if (!$user->hasPermissionTo('delete employee payment')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }
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

    private function actorLabel(?\App\Models\User $user): string
    {
        if (!$user) return 'System';
        return $user->name ?: "User #{$user->id}";
    }

    private function paymentTypeLabel(string $type): string
    {
        return ucfirst(str_replace('_', ' ', $type));
    }

    public function updatePaymentStatus(Request $request, $id)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('update employee payment')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ], 403);
        }

        $request->validate([
            'status' => 'required|in:pending,completed,failed,cancelled',
        ]);

        $payment = EmployeePayment::with(['employee', 'paymentMethod.currency'])
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => __('auth.payment_not_found'),
            ], 404);
        }

        // ── State machine guards ─────────────────────────────────
        if ($payment->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => __('auth.cannot_change_completed_payment'),
            ], 409);
        }

        if ($request->status === 'pending' && in_array($payment->status, ['failed', 'cancelled'], true)) {
            return response()->json([
                'success' => false,
                'message' => __('auth.cannot_revert_failed_payment'),
            ], 409);
        }

        DB::beginTransaction();
        try {
            $updateData = [
                'status'     => $request->status,
                'updated_at' => now(),
            ];

            // ── Shared references for narrative building ─────────
            $actorName    = $this->actorLabel($user);
            $employeeName = trim(($payment->employee->first_name ?? '') . ' ' . ($payment->employee->last_name ?? ''));
            $typeLabel    = ucfirst(str_replace('_', ' ', (string) $payment->payment_type));
            $grossStr     = number_format((float) $payment->gross_amount, 2);
            $netStr       = number_format((float) $payment->amount, 2);
            $taxStr       = number_format((float) $payment->total_tax_amount, 2);
            $advanceStr   = number_format((float) ($payment->total_advance_deduction ?? 0), 2);
            $payDateStr   = $payment->payment_date?->format('d M Y') ?? '—';

            $netTransaction = null;
            $repaymentTransaction = null;
            $advanceDeductionsProcessed = [];

            // ═══════════════════════════════════════════════════════
            // COMPLETED → record withdrawal + tax liabilities + deposit
            // ═══════════════════════════════════════════════════════
            if ($request->status === 'completed') {
                if (!$payment->payment_method_id) {
                    throw new \Exception(__('auth.no_payment_method_for_employee_payment'));
                }

                $paymentMethod = PaymentMethod::findForTenant($payment->payment_method_id, $tenantId);
                if (!$paymentMethod) {
                    throw new \Exception(__('pagination.payment_method_not_found'));
                }

                $validation = $paymentMethod->validateTransaction((float) $payment->amount);
                if (!$validation['success']) {
                    throw new \Exception($validation['message']);
                }

                $methodName = $paymentMethod->name ?? "Method #{$paymentMethod->id}";
                $currency   = optional($paymentMethod->currency)->code ?? 'UGX';
                $currencyId = $paymentMethod->currency_id ?? \App\Models\Currency::default()->id;

                $categoryMap = [
                    'salary'    => 'SALARY',
                    'allowance' => 'ALLOWANCE',
                    'bonus'     => 'BONUS',
                    'overtime'  => 'OVERTIME',
                    'advance'   => 'ADVANCE',
                    'other'     => 'OTHER',
                ];
                $transactionCategory = $categoryMap[strtolower((string) $payment->payment_type)] ?? 'OTHER';

                // ── STEP 1: process advance deductions first ─────
                if ($payment->advance_deductions) {
                    $deductions = $payment->advance_deductions;
                    if (is_string($deductions)) {
                        $deductions = json_decode($deductions, true) ?: [];
                    }
                    if (!is_array($deductions)) {
                        $deductions = [];
                    }

                    foreach ($deductions as $d) {
                        $advance = EmployeeAdvance::where('id', $d['advance_id'] ?? null)
                            ->where('tenant_id', $tenantId)
                            ->first();

                        if (!$advance || !$advance->canBeDeducted()) {
                            \Log::warning('Advance not deductible at completion', [
                                'advance_id' => $d['advance_id'] ?? null,
                                'payment_id' => $payment->id,
                            ]);
                            continue;
                        }

                        $deductAmount = min(
                            (float) ($d['deduction_amount'] ?? 0),
                            (float) $advance->remaining_amount
                        );
                        if ($deductAmount <= 0) continue;

                        $advance->recordDeduction($deductAmount, $payment);

                        $advanceDeductionsProcessed[] = [
                            'advance_id'       => $advance->id,
                            'deduction_amount' => $deductAmount,
                            'remaining_after'  => (float) $advance->fresh()->remaining_amount,
                        ];

                        \Log::info('Advance deduction applied', [
                            'advance_id' => $advance->id,
                            'payment_id' => $payment->id,
                            'amount'     => $deductAmount,
                            'remaining'  => (float) $advance->fresh()->remaining_amount,
                        ]);
                    }
                }

                // ── STEP 2: net payment to employee (WITHDRAWAL) ─
                $periodStr = ($payment->pay_period_start && $payment->pay_period_end)
                    ? ' for the period '
                        . $payment->pay_period_start->format('d M Y') . ' – '
                        . $payment->pay_period_end->format('d M Y')
                    : '';

                $deductionsParts = [];
                if ((float) $payment->total_tax_amount > 0) {
                    $deductionsParts[] = "{$taxStr} {$currency} in taxes";
                }
                if ((float) ($payment->total_advance_deduction ?? 0) > 0) {
                    $deductionsParts[] = "{$advanceStr} {$currency} toward salary advance recovery";
                }
                $deductionsText = $deductionsParts
                    ? ' Net pay arrived after deducting ' . implode(' and ', $deductionsParts) . '.'
                    : '';

                $netNarrative = "{$actorName} processed a {$typeLabel} payment of "
                    . "{$grossStr} {$currency} to {$employeeName}{$periodStr} from {$methodName}. "
                    . "The net disbursed was {$netStr} {$currency}.{$deductionsText} "
                    . "Reference: " . ($payment->reference_number ?: 'none') . ".";

                $netTransactionData = [
                    'user_id'              => $user->id,
                    'payment_method_id'    => $paymentMethod->id,
                    'tenant_id'            => $tenantId,
                    'transaction_type'     => 'WITHDRAWAL',
                    'transaction_category' => $transactionCategory,
                    'amount'               => (float) $payment->amount,
                    'currency_id'          => $currencyId,
                    'reference_table'      => 'employee_payments',
                    'reference_id'         => $payment->id,
                    'description'          => $netNarrative,
                    'notes'                => "Net pay to {$employeeName} — {$methodName}",
                    'metadata' => [
                        'employee_id'            => $payment->employee_id,
                        'employee_name'          => $employeeName,
                        'payment_type'           => $payment->payment_type,
                        'payment_date'           => $payment->payment_date?->format('Y-m-d'),
                        'reference_number'       => $payment->reference_number,
                        'pay_period_start'       => $payment->pay_period_start?->format('Y-m-d'),
                        'pay_period_end'         => $payment->pay_period_end?->format('Y-m-d'),
                        'hours_worked'           => $payment->hours_worked,
                        'hourly_rate'            => $payment->hourly_rate,
                        'processed_by_id'        => $user->id,
                        'processed_by_name'      => $user->name,
                        'transaction_nature'     => 'EMPLOYEE_PAYMENT_NET',
                        'gross_amount'           => (float) $payment->gross_amount,
                        'net_amount'             => (float) $payment->amount,
                        'total_tax'              => (float) $payment->total_tax_amount,
                        'total_advance_deduction'=> (float) ($payment->total_advance_deduction ?? 0),
                        'narrative'              => $netNarrative,
                    ],
                ];

                $netTransaction = app('payment-transaction')->recordTransaction($netTransactionData);

                // ── STEP 3: tax liabilities ───────────────────────
                if ($payment->is_tax_computed
                    && (float) $payment->total_tax_amount > 0
                    && $payment->applied_taxes) {

                    $taxes = $payment->applied_taxes;
                    if (is_string($taxes)) {
                        $taxes = json_decode($taxes, true) ?: [];
                    }

                    foreach ((array) $taxes as $tax) {
                        $taxName   = $tax['tax_name'] ?? $tax['name'] ?? 'Tax';
                        $taxAmount = (float) ($tax['amount'] ?? 0);

                        TaxLiability::create([
                            'tenant_id'           => $tenantId,
                            'employee_id'         => $payment->employee_id,
                            'employee_payment_id' => $payment->id,
                            'tax_id'              => $tax['tax_id'] ?? null,
                            'amount'              => $taxAmount,
                            'tax_name'            => $taxName,
                            'rate'                => $tax['rate'] ?? 0,
                            'tax_code'            => $tax['code'] ?? $tax['tax_code'] ?? null,
                            'tax_type'            => $tax['type'] ?? 'percentage',
                            'status'              => 'pending',
                            'due_date'            => $this->calculateTaxDueDate($payment->payment_date),
                            'tax_year'            => $payment->payment_date?->year,
                            'tax_month'           => $payment->payment_date?->month,
                            'tax_quarter'         => $payment->payment_date
                                                        ? (int) ceil($payment->payment_date->month / 3)
                                                        : null,
                            'metadata' => [
                                'payment_date' => $payment->payment_date?->format('Y-m-d'),
                                'payment_type' => $payment->payment_type,
                                'gross_amount' => (float) $payment->gross_amount,
                                'net_amount'   => (float) $payment->amount,
                                'narrative'    => "{$actorName} recorded a {$taxName} liability of "
                                                . number_format($taxAmount, 2) . " {$currency} against the "
                                                . "{$typeLabel} payment to {$employeeName} on {$payDateStr}.",
                            ],
                        ]);
                    }

                    \Log::info('Tax liabilities created', [
                        'payment_id' => $payment->id,
                        'count'      => count((array) $taxes),
                        'total'      => (float) $payment->total_tax_amount,
                    ]);
                }

                // ── STEP 4: advance repayment (DEPOSIT) ─────────
                if ((float) ($payment->total_advance_deduction ?? 0) > 0 && !empty($advanceDeductionsProcessed)) {
                    $advanceDetails = collect($advanceDeductionsProcessed)->map(function ($d) use ($currency) {
                        $adv = EmployeeAdvance::find($d['advance_id']);
                        $advDate = $adv?->advance_date?->format('d M Y') ?? '—';
                        return "#{$d['advance_id']} (advance dated {$advDate}, "
                            . number_format((float) $d['deduction_amount'], 2) . " {$currency} recovered, "
                            . 'remaining ' . number_format((float) $d['remaining_after'], 2) . ')';
                    })->implode('; ');

                    $repaymentNarrative = "{$actorName} recorded an advance repayment of "
                        . "{$advanceStr} {$currency} from {$employeeName} against the "
                        . "{$typeLabel} payment processed on {$payDateStr}. "
                        . "Advances covered: {$advanceDetails}. "
                        . "Funds were retained from the gross {$grossStr} {$currency} before net disbursement.";

                    $repaymentTransactionData = [
                        'user_id'              => $user->id,
                        'payment_method_id'    => $paymentMethod->id,
                        'tenant_id'            => $tenantId,
                        'transaction_type'     => 'DEPOSIT',
                        'transaction_category' => 'ADVANCE_REPAYMENT',
                        'amount'               => (float) $payment->total_advance_deduction,
                        'currency_id'          => $currencyId,
                        'reference_table'      => 'employee_payments',
                        'reference_id'         => $payment->id,
                        'description'          => $repaymentNarrative,
                        'notes'                => "Advance recovery from {$employeeName}",
                        'metadata' => [
                            'employee_id'         => $payment->employee_id,
                            'employee_name'       => $employeeName,
                            'advance_deductions'  => $advanceDeductionsProcessed,
                            'payment_id'          => $payment->id,
                            'transaction_nature'  => 'ADVANCE_REPAYMENT',
                            'narrative'           => $repaymentNarrative,
                        ],
                    ];

                    $repaymentTransaction = app('payment-transaction')->recordTransaction($repaymentTransactionData);

                    foreach ($advanceDeductionsProcessed as $d) {
                        EmployeeAdvance::where('id', $d['advance_id'])
                            ->where('tenant_id', $tenantId)
                            ->update(['repayment_transaction_ref' => $repaymentTransaction->transaction_ref]);
                    }
                }

                // ── STEP 5: link transaction refs to payment ────
                $payment->update([
                    'net_payment_transaction_ref' => $netTransaction->transaction_ref,
                    'completed_at'                => now(),
                ]);
            }

            // ═══════════════════════════════════════════════════════
            // FAILED / CANCELLED → timestamp only, no money moved
            // ═══════════════════════════════════════════════════════
            elseif (in_array($request->status, ['failed', 'cancelled'], true)
                && $payment->status === 'pending') {

                $timestampField = $request->status === 'failed' ? 'failed_at' : 'cancelled_at';
                $updateData[$timestampField] = now();

                \Log::info('Employee payment status changed without disbursement', [
                    'payment_id'      => $payment->id,
                    'amount'          => (float) $payment->amount,
                    'previous_status' => $payment->status,
                    'new_status'      => $request->status,
                ]);
            }

            $payment->update($updateData);

            DB::commit();

            return response()->json([
                'success' => true,
                'reload'  => true,
                'componentId' => 'reloadPaymentComponent',
                'refresh' => false,
                'message' => __('auth.status_updated'),
                'redirect'=> route('payment.index'),
                'transaction_info' => [
                    'transaction_ref'          => $netTransaction->transaction_ref ?? null,
                    'amount'                   => (float) $payment->amount,
                    'payment_method'           => $paymentMethod->name ?? null,
                    'payment_type'             => $payment->payment_type,
                    'advance_deductions'       => count($advanceDeductionsProcessed),
                    'total_advance_deducted'   => array_sum(array_column($advanceDeductionsProcessed, 'deduction_amount')),
                    'tax_liabilities_created'  => (float) $payment->total_tax_amount > 0
                                                    ? count((array) ($payment->applied_taxes ?? []))
                                                    : 0,
                ],
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating payment status', [
                'payment_id' => $id,
                'status'     => $request->status,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('auth.error_updating_status') . ': ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Calculate tax due date (15th of following month)
     */
    private function calculateTaxDueDate($paymentDate)
    {
        return \Carbon\Carbon::parse($paymentDate)
            ->addMonth()
            ->startOfMonth()
            ->addDays(14); // 15th of next month
    }



    public function export(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('export employee payment')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        $query = EmployeePayment::with(['employee', 'paymentMethod'])
            ->where('tenant_id', $tenantId);

        // Apply filters same as index
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }
        // ... add other filters

        $payments = $query->get();

        $filename = 'payments_' . now()->format('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($payments) {
            $file = fopen('php://output', 'w');
            
            // Headers
            fputcsv($file, [
                'ID',
                'Employee',
                'Payment Type',
                'Payment Method',
                'Gross Amount',
                'Net Amount',
                'Tax Amount',
                'Advance Deduction',
                'Payment Date',
                'Status',
            ]);

            // Data
            foreach ($payments as $payment) {
                fputcsv($file, [
                    $payment->id,
                    $payment->employee->first_name . ' ' . $payment->employee->last_name,
                    $payment->payment_type,
                    $payment->paymentMethod->name ?? 'N/A',
                    $payment->gross_amount,
                    $payment->net_amount,
                    $payment->total_tax_amount,
                    $payment->total_advance_deduction ?? 0,
                    $payment->payment_date->format('Y-m-d'),
                    $payment->status,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }


}
