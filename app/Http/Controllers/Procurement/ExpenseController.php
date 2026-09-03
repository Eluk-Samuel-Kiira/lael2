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
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
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
        
        // Get per_page from request, default to 15
        $perPage = $request->input('per_page', 15);
        
        // Validate per_page is in allowed values
        $allowedPerPage = [15, 25, 50, 100];
        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 15;
        }
        
        // Build the query with relationships
        $query = Expense::with(['tenant', 'paymentMethod', 'category']);
        
        // If user is NOT super_admin, filter by tenant
        if (!$user->hasRole('super_admin')) {
            $query->where('tenant_id', current_tenant_id());
        }
        
        // Apply search if provided
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('vendor_name', 'like', "%{$search}%")
                ->orWhere('expense_number', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhere('payment_status', 'like', "%{$search}%")
                ->orWhereHas('paymentMethod', fn($p) => $p->where('name', 'like', "%{$search}%"))
                ->orWhereHas('category', fn($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }
        
        // Paginate with dynamic per_page
        $expenses = $query->latest()->paginate($perPage);
        
        // Preserve per_page and search in pagination links
        $expenses->appends(['per_page' => $perPage, 'search' => $request->search]);
        
        // ✅ Get active payment methods with location filtering
        $activePaymentMethods = PaymentMethod::where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->when($user->location_id, function($query, $locationId) {
                return $query->where(function($q) use ($locationId) {
                    $q->whereNull('location_id')
                    ->orWhereRaw('JSON_CONTAINS(location_id, ?)', [json_encode((string)$locationId)]);
                });
            })
            ->orderBy('name')
            ->get();
        
        $bladeToReload = $request->query('bladeFileToReload');
        
        // For AJAX requests - return just the component HTML
        if ($request->ajax() && $bladeToReload === 'reloadExpenseComponent') {
            return view('procurement.expense.component', [
                'expenses' => $expenses,
                'PaymentMethods' => $activePaymentMethods,
            ])->render();
        }
        
        // Regular page load
        return view('procurement.expense-index', [
            'expenses' => $expenses,
            'PaymentMethods' => $activePaymentMethods,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
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
     * Generate unique expense number
     */
    private function generateExpenseNumber($tenantId)
    {
        $prefix = 'EXP-' . date('ym');
        $count = Expense::where('tenant_id', $tenantId)
            ->where('expense_number', 'like', $prefix . '-%')
            ->count() + 1;

        return $prefix . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
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
