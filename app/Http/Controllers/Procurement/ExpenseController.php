<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{ ExpenseCategory, Expense, Employee, PaymentMethod };
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
        if (!$user->hasPermissionTo('delete expense')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }
        
        // Build the query
        $query = Expense::with('tenant');
        
        // If user is NOT super_admin, filter by tenant
        if (!$user->hasRole('super_admin')) {
            $query->where('tenant_id', current_tenant_id());
        }
        
        $expenses = $query->latest()->get();

        $bladeToReload = $request->query('bladeFileToReload');
        switch ($bladeToReload) {
            case 'reloadExpenseComponent':
                return view('procurement.expense.component', [
                    'expenses' => $expenses,
                ]);
            default:
                return view('procurement.expense-index', [
                    'expenses' => $expenses,
                ]);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
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

        // Validation rules
        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01|decimal:0,2',
            'tax_amount' => 'nullable|numeric|min:0|decimal:0,2',
            'vendor_name' => 'required|string|max:200',
            'category_id' => 'required|exists:expense_categories,id',
            'employee_id' => 'nullable|exists:employees,id',
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
            'receipt' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120', // 5MB
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

        // Generate unique expense number
        $expenseNumber = $this->generateExpenseNumber($tenantId);

        // Handle receipt upload
        $receiptUrl = null;
        if ($request->hasFile('receipt')) {
            $receiptUrl = $this->uploadReceipt($request->file('receipt'), $tenantId);
        }

        // Calculate total amount
        $amount = $validated['amount'];
        $taxAmount = $validated['tax_amount'] ?? 0;
        $totalAmount = $amount + $taxAmount;

        // Create the expense
        $expense = Expense::create([
            'tenant_id' => $tenantId,
            'expense_number' => $expenseNumber,
            'description' => $validated['description'],
            'amount' => $amount,
            'tax_amount' => $taxAmount,
            // 'total_amount' => $totalAmount, // Will be calculated automatically if using generated column
            'vendor_name' => $validated['vendor_name'],
            'category_id' => $validated['category_id'],
            'employee_id' => $validated['employee_id'] ?? null,
            'date' => $validated['date'],
            'paid_date' => $validated['paid_date'] ?? null,
            'payment_method_id' => $validated['payment_method_id'] ?? null, 
            'payment_status' => $validated['payment_status'],
            'is_recurring' => $validated['is_recurring'] ?? false,
            'recurring_frequency' => $validated['recurring_frequency'] ?? null,
            'next_recurring_date' => $validated['next_recurring_date'] ?? null,
            'receipt_url' => $receiptUrl,
            'created_by' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'reload' => true,
            'componentId' => 'reloadExpenseComponent',
            'refresh' => false,
            'message' => __('auth.expense_created'),
            'redirect' => route('expense.index'),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
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

        // Validation rules
        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01|decimal:0,2',
            'tax_amount' => 'nullable|numeric|min:0|decimal:0,2',
            'vendor_name' => 'required|string|max:200',
            'category_id' => 'required|exists:expense_categories,id',
            'employee_id' => 'nullable|exists:employees,id',
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
        // $receiptUrl = $expense->receipt_url;
        // if ($request->hasFile('receipt')) {
        //     // Delete old receipt if exists
        //     if ($receiptUrl) {
        //         $this->deleteReceipt($receiptUrl);
        //     }
        //     $receiptUrl = $this->uploadReceipt($request->file('receipt'), $tenantId);
        // }

        // Calculate total amount
        $amount = $validated['amount'];
        $taxAmount = $validated['tax_amount'] ?? 0;
        $totalAmount = $amount + $taxAmount;

        // Update the expense
        $expense->update([
            'description' => $validated['description'],
            'amount' => $amount,
            'tax_amount' => $taxAmount,
            // 'total_amount' => $totalAmount, // Will be calculated automatically if using generated column
            'vendor_name' => $validated['vendor_name'],
            'category_id' => $validated['category_id'],
            'employee_id' => $validated['employee_id'] ?? null,
            'date' => $validated['date'],
            'paid_date' => $validated['paid_date'] ?? null,
            'payment_method_id' => $validated['payment_method_id'] ?? null, // Use payment_method_id
            'payment_status' => $validated['payment_status'],
            'is_recurring' => $validated['is_recurring'] ?? false,
            'recurring_frequency' => $validated['recurring_frequency'] ?? null,
            'next_recurring_date' => $validated['next_recurring_date'] ?? null,
            // 'receipt_url' => $receiptUrl,
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

        // Check if trying to mark as paid but expense is not approved
        if (($request->status === 'paid' || $request->status === 'reimbursed') && !$expense->approved_at) {
            return response()->json([
                'success' => false,
                'message' => __('auth.cannot_pay_unapproved_expense'),
            ]);
        }

        // Check if trying to change from paid/reimbursed back to pending
        if ($request->status === 'pending' && ($expense->payment_status === 'paid' || $expense->payment_status === 'reimbursed')) {
            return response()->json([
                'success' => false,
                'message' => __('auth.cannot_revert_paid_status'),
            ]);
        }

        // Check if payment method exists for paid/reimbursed status
        if (($request->status === 'paid' || $request->status === 'reimbursed') && !$expense->payment_method_id) {
            return response()->json([
                'success' => false,
                'message' => __('auth.no_payment_method_for_expense'),
            ]);
        }

        DB::beginTransaction();
        try {
            $updateData = [
                'payment_status' => $request->status,
                'updated_at' => now(),
            ];

            // Get the total amount from the expense
            $totalAmount = $expense->total_amount ?? $expense->amount + $expense->tax_amount;

            // Set paid date only when marking as paid or reimbursed
            if ($request->status === 'paid' || $request->status === 'reimbursed') {
                $updateData['paid_date'] = now();
                
                // If reimbursed, also set paid_by to current user
                if ($request->status === 'reimbursed') {
                    $updateData['paid_by'] = $user->id;
                }
                
                // ✅ PROCESS PAYMENT TRANSACTION USING EXISTING PAYMENT METHOD FROM DATABASE
                $paymentMethod = PaymentMethod::findForTenant($expense->payment_method_id, $tenantId);
                
                if (!$paymentMethod) {
                    throw new \Exception(__('pagination.payment_method_not_found'));
                }
                
                // Validate the payment method can handle this transaction
                $validation = $paymentMethod->validateTransaction($totalAmount);
                if (!$validation['success']) {
                    throw new \Exception($validation['message']);
                }

                // Determine transaction type based on status
                $transactionType = $request->status === 'paid' ? 'WITHDRAWAL' : 'DEPOSIT';
                $transactionCategory = $request->status === 'paid' ? 'EXPENSE' : 'REFUND';
                $description = $request->status === 'paid' 
                    ? 'Expense Payment - ' . $expense->expense_number
                    : 'Expense Reimbursement - ' . $expense->expense_number;
                
                $notes = $request->status === 'paid' 
                    ? 'Payment for expense'
                    : 'Reimbursement back to account';

                // Record expense payment using PaymentTransactionService
                $transactionData = [
                    'user_id' => $user->id,
                    'payment_method_id' => $paymentMethod->id,
                    'tenant_id' => $tenantId,
                    'transaction_type' => $transactionType,
                    'transaction_category' => $transactionCategory,
                    'amount' => $totalAmount,
                    'currency_id' => $paymentMethod->currency_id ?? \App\Models\Currency::default()->id,
                    'reference_table' => 'expenses',
                    'reference_id' => $expense->id,
                    'description' => $description,
                    'notes' => $notes,
                    'metadata' => [
                        'expense_number' => $expense->expense_number,
                        'expense_description' => $expense->description,
                        'vendor_name' => $expense->vendor_name,
                        'category_id' => $expense->category_id,
                        'payment_status' => $request->status,
                        'amount' => $expense->amount,
                        'tax_amount' => $expense->tax_amount,
                        'total_amount' => $totalAmount,
                        'processed_by_id' => $user->id,
                        'processed_by_name' => $user->last_name.' '.$user->last_name ?? 'Unkown',
                        'transaction_nature' => $request->status === 'paid' ? 'EXPENSE_PAYMENT' : 'EXPENSE_REIMBURSEMENT',
                    ],
                ];

                // Record the transaction
                $transactionLog = app('payment-transaction')->recordTransaction($transactionData);
                
                // Update expense with transaction reference
                $updateData['payment_transaction_ref'] = $transactionLog->transaction_ref ?? null;

                \Log::info('Expense payment transaction recorded', [
                    'expense_number' => $expense->expense_number,
                    'transaction_type' => $transactionType,
                    'amount' => $totalAmount,
                    'payment_method' => $paymentMethod->name,
                    'payment_method_id' => $expense->payment_method_id,
                    'transaction_ref' => $transactionLog->transaction_ref ?? 'N/A',
                ]);

            } else {
                // If reverting to pending, clear payment info
                $updateData['paid_date'] = null;
                $updateData['paid_by'] = null;
                $updateData['payment_transaction_ref'] = null;
                // Note: We keep the payment_method_id since it's part of the original expense record
            }

            $expense->update($updateData);

            DB::commit();

            // Log the activity
            // activity()
            //     ->causedBy($user)
            //     ->performedOn($expense)
            //     ->log('updated payment status to ' . $request->status);

            return response()->json([
                'success' => true,
                'message' => __('auth.status_updated'),
                'reload' => true,
                'componentId' => 'reloadExpenseComponent',
                'redirect' => route('expense.index'),
                'transaction_info' => isset($transactionLog) ? [
                    'transaction_ref' => $transactionLog->transaction_ref,
                    'transaction_type' => $transactionType ?? null,
                    'amount' => $totalAmount,
                    'payment_method' => $paymentMethod->name ?? null,
                ] : null,
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
