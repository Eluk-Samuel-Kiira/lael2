<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{ Supplier, PurchaseOrder, ProductVariant, PurchaseOrderItem, InventoryItems, PaymentMethod,
        PurchaseReceipt, InventoryTransactions, InventoryAdjustment, PurchaseReceiptItem, SingleShopInventoryLog,
        Location, Department, SupplierTaxLiability, Tax, ReceivedProductVariant, Tenant, BatchLog };
use Illuminate\Support\Facades\{ Auth, DB };
use Illuminate\Support\Str;


class PurchaseOrderController extends Controller
{
    
    public function index(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('view purchase_orders')) {
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
        $query = PurchaseOrder::with(['items', 'supplier', 'location', 'creator']);
        
        // If user is NOT super_admin, filter by tenant
        if (!$user->hasRole('super_admin')) {
            $query->where('tenant_id', current_tenant_id());
        }
        
        // Apply search if provided
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('po_number', 'like', "%{$search}%")
                ->orWhere('status', 'like', "%{$search}%")
                ->orWhereHas('supplier', fn($s) => $s->where('name', 'like', "%{$search}%"))
                ->orWhereHas('creator', fn($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }
        
        // Paginate with dynamic per_page
        $purchaseOrders = $query->latest()->paginate($perPage);
        
        // Preserve per_page and search in pagination links
        $purchaseOrders->appends(['per_page' => $perPage, 'search' => $request->search]);
        
        $bladeToReload = $request->query('bladeFileToReload');
        
        // For AJAX requests - return just the component HTML
        if ($request->ajax() && $bladeToReload === 'reloadPurchasesComponent') {
            return view('procurement.purchase-order.component', [
                'purchaseOrders' => $purchaseOrders,
            ])->render();
        }
        
        // Regular page load
        return view('procurement.po-index', [
            'purchaseOrders' => $purchaseOrders,
        ]);
    }

    
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('create purchase_orders')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        // Validate the main purchase order data with tenant checks
        $validated = $request->validate([
            'supplier_id' => [
                'required',
                'exists:suppliers,id',
                function ($attribute, $value, $fail) use ($tenantId) {
                    $supplier = Supplier::where('id', $value)
                                    ->where('tenant_id', $tenantId)
                                    ->first();
                    if (!$supplier) {
                        $fail('The selected supplier is invalid.');
                    }
                }
            ],
            'location_id' => [
                'required',
                'exists:locations,id',
                function ($attribute, $value, $fail) use ($tenantId) {
                    $location = Location::where('id', $value)
                                    ->where('tenant_id', $tenantId)
                                    ->first();
                    if (!$location) {
                        $fail('The selected location is invalid.');
                    }
                }
            ],
            'expected_delivery_date' => 'required|date|after_or_equal:today',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.product_variant_id' => [
                'required',
                'exists:product_variants,id',
                function ($attribute, $value, $fail) use ($tenantId) {
                    $variant = ProductVariant::where('id', $value)
                                        ->where('tenant_id', $tenantId)
                                        ->first();
                    if (!$variant) {
                        $fail('The selected product variant is invalid.');
                    }
                }
            ],
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0.01',
        ]);

        // Calculate totals
        $subtotal = 0;
        
        foreach ($request->items as $item) {
            $itemSubtotal = $item['quantity'] * $item['unit_cost'];
            $subtotal += $itemSubtotal;
        }
        
        $total = $subtotal;

        // Generate PO number
        $poNumber = $this->generatePONumber();

        // Start database transaction
        DB::beginTransaction();

        try {
            // Create the purchase order
            $purchaseOrder = PurchaseOrder::create([
                'tenant_id' => $tenantId,
                'supplier_id' => $validated['supplier_id'],
                'location_id' => $validated['location_id'],
                'po_number' => $poNumber,
                'status' => 'draft',
                'expected_delivery_date' => $validated['expected_delivery_date'],
                'subtotal' => $subtotal,
                'tax_total' => 0,
                'total' => $total,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            // Create purchase order items
            foreach ($request->items as $item) {
                // Only create items that have a product selected
                if (!empty($item['product_variant_id'])) {
                    $variant = ProductVariant::where('id', $item['product_variant_id'])
                                        ->where('tenant_id', $tenantId)
                                        ->first();
                    
                    if ($variant) {
                        $itemSubtotal = $item['quantity'] * $item['unit_cost'];
                        
                        PurchaseOrderItem::create([
                            'purchase_order_id' => $purchaseOrder->id,
                            'product_variant_id' => $item['product_variant_id'],
                            'product_name' => $variant->name ?? null,
                            'sku' => $variant->sku ?? null,
                            'quantity' => $item['quantity'],
                            'unit_cost' => $item['unit_cost'],
                            'tax_amount' => 0,
                            'total_cost' => $itemSubtotal,
                            'received_quantity' => 0,
                            'tenant_id' => $tenantId, // Add tenant_id to items if your table has it
                        ]);
                    }
                }
            }

            // Commit transaction
            DB::commit();

            return response()->json([
                'success' => true,
                'reload' => true,
                'componentId' => 'reloadPurchasesComponent',
                'refresh' => false,
                'message' => __('auth._created'),
                'redirect' => route('purchase_order.index'),
            ]);

        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();

            \Log::error('Purchase order creation failed', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenantId,
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error creating purchase order: ' . $e->getMessage(),
            ]);
        }
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('edit purchase_orders')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        // Check tenant access and ensure it's in draft status
        if ($purchaseOrder->tenant_id !== auth()->user()->tenant_id || $purchaseOrder->status !== 'draft') {
            abort(403, __('payments.not_authorized'));
        }

        // Validate the main purchase order data
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'location_id' => 'required|exists:locations,id',
            'expected_delivery_date' => 'required|date|after_or_equal:today',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.product_variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0.01',
        ]);

        // Calculate totals
        $subtotal = 0;
        
        foreach ($request->items as $item) {
            $itemSubtotal = $item['quantity'] * $item['unit_cost'];
            $subtotal += $itemSubtotal;
        }
        
        $total = $subtotal;

        // Start database transaction
        DB::beginTransaction();

        try {
            // Update the purchase order
            $purchaseOrder->update([
                'supplier_id' => $validated['supplier_id'],
                'location_id' => $validated['location_id'],
                'expected_delivery_date' => $validated['expected_delivery_date'],
                'subtotal' => $subtotal,
                'tax_total' => 0, // Set tax total to 0
                'total' => $total,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Delete existing items
            $purchaseOrder->items()->delete();

            // Create new purchase order items
            foreach ($request->items as $item) {
                // Only create items that have a product selected
                if (!empty($item['product_variant_id'])) {
                    $variant = ProductVariant::find($item['product_variant_id']);
                    $itemSubtotal = $item['quantity'] * $item['unit_cost'];
                    
                    // No tax calculations - set to 0
                    $itemTaxAmount = 0;
                    $totalCost = $itemSubtotal; // Total cost is just the subtotal
                    
                    PurchaseOrderItem::create([
                        'purchase_order_id' => $purchaseOrder->id,
                        'product_variant_id' => $item['product_variant_id'],
                        'product_name' => $variant->name ?? null,
                        'sku' => $variant->sku ?? null,
                        'quantity' => $item['quantity'],
                        'unit_cost' => $item['unit_cost'],
                        'tax_amount' => 0, // Set tax amount to 0
                        'total_cost' => $totalCost,
                        'received_quantity' => 0,
                    ]);
                }
            }

            // Commit transaction
            DB::commit();

            return response()->json([
                'success' => true,
                'reload' => true,
                'componentId' => 'reloadPurchasesComponent',
                'refresh' => false,
                'message' => __('purchase_order.updated_successfully'),
                'redirect' => route('purchase-orders.show', $purchaseOrder->id),
            ]);

        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error updating purchase order: ' . $e->getMessage(),
            ]);
        }
    }


    private function generatePONumber()
    {
        $tenantId = auth()->user()->tenant_id ?? 1;
        $prefix = 'PO';
        $year = date('Y');
        
        // Atomic update to get the next sequence
        $maxSequence = PurchaseOrder::where('tenant_id', $tenantId)
            ->where('po_number', 'like', $prefix . '-' . $year . '-%')
            ->max(DB::raw('CAST(SUBSTRING_INDEX(po_number, "-", -1) AS UNSIGNED)'));
        
        $sequence = ($maxSequence ?? 0) + 1;
        
        $poNumber = $prefix . '-' . $year . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
        
        // Retry logic in case of duplicates
        $attempts = 0;
        while ($attempts < 10) {
            try {
                // Try to create a temporary record to claim this PO number
                DB::table('purchase_orders')->insert([
                    'tenant_id' => $tenantId,
                    'po_number' => $poNumber,
                    'supplier_id' => 0, // temporary
                    'location_id' => 0, // temporary  
                    'status' => 'draft',
                    'subtotal' => 0,
                    'tax_total' => 0,
                    'total' => 0,
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                // If we get here, the PO number is unique
                // Delete the temporary record
                DB::table('purchase_orders')
                    ->where('tenant_id', $tenantId)
                    ->where('po_number', $poNumber)
                    ->where('subtotal', 0)
                    ->delete();
                    
                return $poNumber;
                
            } catch (\Exception $e) {
                // Duplicate entry, try next sequence
                $sequence++;
                $poNumber = $prefix . '-' . $year . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
                $attempts++;
            }
        }
        
        // Fallback with timestamp
        return $prefix . '-' . $year . '-' . substr(time(), -6);
    }




    // purchase status 
    public function submitApproval(Request $request, $id) 
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('submit purchase_orders')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }
        // \Log::info($request->all());
        $validated = $request->validate([
            'status' => 'required', 
        ]);
        
        $purchase = PurchaseOrder::where('id', $id)
                        ->where('tenant_id', $tenantId)
                        ->first();

        if (!$purchase) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ]);
        }

        // Check if status is already pending_approval
        if ($purchase->status === 'pending_approval') {
            return response()->json([
                'success' => false,
                'message' => __('passwords.already_pending_approval'),
            ]);
        }

        // Validate that status transition is allowed (only from draft to pending_approval)
        if ($purchase->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => __('passwords.can_only_submit_from_draft'),
            ]);
        }

        // Validate that the requested status is pending_approval
        if ($validated['status'] !== 'pending_approval') {
            return response()->json([
                'success' => false,
                'message' => __('passwords.invalid_status_transition'),
            ]);
        }

        DB::beginTransaction();
        try {
            $purchase->status = $validated['status'];
            $purchase->submitted_at = now();
            $purchase->submitted_by = auth()->id();

            
            if ($purchase->save()) {  
                DB::commit();
                
                return response()->json([
                    'success' => true,
                    'reload' => true,
                    'refresh' => false,
                    'componentId' => 'reloadPurchasesComponent',
                    'message' => __('passwords.submit_approval_success'),
                    'redirect' => route('purchase_order.index'),
                ]);
            }

            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => __('passwords.status_update_failed'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => __('passwords.error_occurred') . $e->getMessage(),
            ]);
        }
    }

    public function approve(Request $request, $id)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('approve purchase_orders')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }
        
        $validated = $request->validate([
            'status' => 'required', 
        ]);
        
        $purchase = PurchaseOrder::where('id', $id)
                        ->where('tenant_id', $tenantId)
                        ->first();

        if (!$purchase) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ]);
        }

        // Check if status is already approved
        if ($purchase->status === 'approved') {
            return response()->json([
                'success' => false,
                'message' => __('passwords.already_approved'),
            ]);
        }

        // Validate that status transition is allowed (only from pending_approval to approved)
        if ($purchase->status !== 'pending_approval') {
            return response()->json([
                'success' => false,
                'message' => __('passwords.can_only_approve_from_pending'),
            ]);
        }

        // Validate that the requested status is approved
        if ($validated['status'] !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => __('passwords.invalid_status_transition'),
            ]);
        }

        DB::beginTransaction();

        try {
            // In approve method  
            $purchase->status = $validated['status'];
            $purchase->approved_at = now();
            $purchase->approved_by = auth()->id();
            
            if ($purchase->save()) {  
                DB::commit();

                return response()->json([
                    'success' => true,
                    'reload' => true,
                    'refresh' => false,
                    'componentId' => 'reloadPurchasesComponent',
                    'message' => __('passwords.approve_success'),
                    'redirect' => route('purchase_order.index'),
                ]);
            }

            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => __('passwords.status_update_failed'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => __('passwords.error_occurred') . $e->getMessage(),
            ]);
        }
    }

    public function sendToSupplier(Request $request, $id)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('send purchase_orders')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }
        
        $validated = $request->validate([
            'payment_method_id' => 'required|exists:payment_methods,id',
            'payment_amount' => 'required|numeric|min:0.01',
            'payment_status' => 'nullable|in:pending,partial,paid,overdue',
            'payment_date' => 'nullable|date',
            'supplier_email' => 'nullable|email',
            'notes' => 'nullable|string|max:500',
        ]);
        
        $purchase = PurchaseOrder::with(['supplier', 'items'])
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$purchase) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ]);
        }

        // ✅ Allow payments even if already sent
        // Only prevent sending if already sent (but still allow payments)
        $isAlreadySent = $purchase->status === 'sent';
        
        // For first-time send, status must be 'approved'
        if (!$isAlreadySent && $purchase->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => __('passwords.can_only_send_from_approved'),
            ]);
        }

        // ✅ Calculate remaining balance
        $totalAmount = $purchase->total ?? 0;
        $totalPaid = $purchase->total_paid ?? 0;
        $remainingBalance = $totalAmount - $totalPaid;
        
        // ✅ Validate payment amount
        $paymentAmount = (float) $validated['payment_amount'];
        
        if ($paymentAmount > $remainingBalance) {
            return response()->json([
                'success' => false,
                'message' => __('payments.payment_exceeds_balance') . ' ' . __('payments.balance') . ': ' . number_format($remainingBalance, 2),
            ]);
        }

        DB::beginTransaction();

        try {
            $paymentMethod = PaymentMethod::findForTenant($validated['payment_method_id'], $tenantId);
            
            if (!$paymentMethod) {
                throw new \Exception(__('pagination.payment_method_not_found'));
            }

            // ── Determine payment status ──────────────────────────────────────────
            $newTotalPaid = $totalPaid + $paymentAmount;
            $newRemainingBalance = $totalAmount - $newTotalPaid;
            
            if ($newRemainingBalance <= 0) {
                $paymentStatus = 'paid';
            } elseif ($paymentAmount > 0 && $paymentAmount < $remainingBalance) {
                $paymentStatus = 'partial';
            } else {
                $paymentStatus = $validated['payment_status'] ?? 'partial';
            }

            // ── Record payment transaction ──────────────────────────────────────────
            $transactionData = [
                'user_id' => $user->id,
                'tenant_id' => $tenantId,
                'payment_method_id' => $paymentMethod->id,
                'transaction_type' => 'WITHDRAWAL',
                'transaction_category' => 'PURCHASE_ORDER',
                'amount' => $paymentAmount,
                'currency_id' => $paymentMethod->currency_id ?? \App\Models\Currency::default()->id,
                'reference_table' => 'purchase_orders',
                'reference_id' => $purchase->id,
                'description' => 'Purchase Order Payment - PO #' . $purchase->po_number . ' (' . $paymentStatus . ')',
                'notes' => 'Payment of ' . number_format($paymentAmount, 2) . ' sent to supplier for PO #' . $purchase->po_number . 
                        ($validated['notes'] ? ' - ' . $validated['notes'] : ''),
                'metadata' => [
                    'purchase_order_number' => $purchase->po_number,
                    'supplier_id' => $purchase->supplier_id,
                    'supplier_name' => $purchase->supplier->name,
                    'payment_status' => $paymentStatus,
                    'payment_date' => $validated['payment_date'] ?? now()->toDateString(),
                    'total_amount' => $totalAmount,
                    'payment_amount' => $paymentAmount,
                    'total_paid_before' => $totalPaid,
                    'total_paid_after' => $newTotalPaid,
                    'balance_before' => $remainingBalance,
                    'balance_after' => $newRemainingBalance,
                    'transaction_nature' => 'PURCHASE_PAYMENT',
                    'sent_by_id' => $user->id,
                    'sent_by_name' => $user->name,
                    'is_additional_payment' => $isAlreadySent,
                ],
            ];

            if (isset($validated['payment_date'])) {
                $transactionData['effective_date'] = $validated['payment_date'];
            }

            $transactionLog = app('payment-transaction')->recordTransaction($transactionData);

            // ── Update purchase order items with payment info ─────────────────────
            $updateData = [];
            if (isset($validated['payment_method_id'])) {
                $updateData['payment_method_id'] = $validated['payment_method_id'];
            }
            if ($paymentStatus) {
                $updateData['payment_status'] = $paymentStatus;
            }
            if (isset($validated['payment_date'])) {
                $updateData['payment_date'] = $validated['payment_date'];
            }
            
            if (!empty($updateData)) {
                $purchase->items()->update($updateData);
            }

            // ── Update purchase order ──────────────────────────────────────────────
            $purchase->total_paid = $newTotalPaid;
            $purchase->payment_status = $paymentStatus;
            
            // ✅ Only set status to 'sent' if it's the first send
            if (!$isAlreadySent) {
                $purchase->status = 'sent';
                $purchase->sent_at = now();
                $purchase->sent_by = $user->id;
            }
            
            $purchase->save();

            DB::commit();

            // ── Send email to supplier (only on first send) ──────────────────────────
            if (!$isAlreadySent) {
                $emailToUse = $validated['supplier_email'] ?? $purchase->supplier->email;
                if ($emailToUse) {
                    try {
                        $this->sendPurchaseOrderEmail($purchase, $emailToUse);
                    } catch (\Exception $e) {
                        \Log::error('Failed to send PO email: ' . $e->getMessage());
                    }
                }
            }

            // ── Build response message ──────────────────────────────────────────────
            $message = '';
            if ($isAlreadySent) {
                $message = __('passwords.payment_recorded_success') . ' ' . number_format($paymentAmount, 2) . '. ';
                if ($newRemainingBalance <= 0) {
                    $message .= __('passwords.po_fully_paid');
                } else {
                    $message .= __('passwords.balance_remaining') . ': ' . number_format($newRemainingBalance, 2);
                }
            } else {
                $message = $paymentStatus === 'paid' 
                    ? __('passwords.send_supplier_success_paid')
                    : __('passwords.send_supplier_success_partial') . ' ' . number_format($paymentAmount, 2) . '. ' . __('passwords.balance_remaining') . ': ' . number_format($newRemainingBalance, 2);
            }

            return response()->json([
                'success' => true,
                'reload' => true,
                'refresh' => false,
                'componentId' => 'reloadPurchasesComponent',
                'message' => $message,
                'redirect' => route('purchase_order.index'),
                'transaction_ref' => $transactionLog->transaction_ref ?? null,
                'payment_status' => $paymentStatus,
                'balance_remaining' => $newRemainingBalance,
                'total_paid' => $newTotalPaid,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Send to supplier failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => __('passwords.error_occurred') . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send purchase order email to supplier
     */
    private function sendPurchaseOrderEmail(PurchaseOrder $purchaseOrder)
    {
        try {
            $supplier = $purchaseOrder->supplier;
            
            \Mail::send('emails.purchase-order', [
                'purchaseOrder' => $purchaseOrder,
                'supplier' => $supplier,
            ], function ($message) use ($purchaseOrder, $supplier) {
                $message->to($supplier->email)
                    ->subject('Purchase Order #' . $purchaseOrder->po_number)
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });
            
            \Log::info('Purchase order email sent to supplier: ' . $supplier->email);
            
        } catch (\Exception $e) {
            \Log::error('Failed to send purchase order email: ' . $e->getMessage());
            // Don't throw error - email failure shouldn't prevent status update
        }
    }

    /**
     * Generate batch number combining incoming value with date and random
     * Format: {INCOMING}-YYYYMMDD-XXXXX
     */
    private function generateBatchNumber($incomingBatch)
    {
        // Clean the incoming batch number
        $incomingBatch = strtoupper(trim($incomingBatch));
        
        // Remove any special characters that might cause issues
        $incomingBatch = preg_replace('/[^A-Z0-9]/', '', $incomingBatch);
        
        // Format: INCOMING-YYYYMMDD-XXXXX
        $date = now()->format('Ymd');
        $random = strtoupper(substr(uniqid(mt_rand(), true), -6));
        $sequence = $this->getNextBatchSequence();
        
        return "{$incomingBatch}-{$date}-{$random}-{$sequence}";
    }

    /**
     * Get next sequence number for uniqueness
     */
    private function getNextBatchSequence(): string
    {
        $lastRecord = PurchaseReceipt::orderBy('id', 'desc')->first();
        
        if ($lastRecord && $lastRecord->batch_number) {
            $parts = explode('-', $lastRecord->batch_number);
            $lastSeq = end($parts);
            $newSeq = (int)$lastSeq + 1;
            return str_pad($newSeq, 4, '0', STR_PAD_LEFT);
        }
        
        return '0001';
    }


    public function receiveItems(Request $request, PurchaseOrder $purchaseOrder)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('receive purchase_orders')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        // ✅ Check if PO is fully paid before allowing receipt
        $totalAmount = $purchaseOrder->total ?? 0;
        $totalPaid = $purchaseOrder->total_paid ?? 0;
        $balance = $totalAmount - $totalPaid;
        
        if ($balance > 0) {
            return response()->json([
                'success' => false,
                'message' => __('passwords.cannot_receive_unpaid_items') . ' ' . __('passwords.balance_remaining') . ': ' . number_format($balance, 2),
                'balance' => $balance,
            ], 422);
        }

        $items = $request->input('items', []);
        
        // If items is empty or missing, get all purchase order items and set quantity to 0
        if (empty($items)) {
            $purchaseOrderItems = $purchaseOrder->items;
            $items = [];
            foreach ($purchaseOrderItems as $orderItem) {
                $items[$orderItem->id] = [
                    'purchase_order_item_id' => $orderItem->id,
                    'product_variant_id' => $orderItem->product_variant_id,
                    'quantity_received' => 0,
                ];
            }
            $request->merge(['items' => $items]);
        }

        $validated = $request->validate([
            'status' => 'required|in:partially_received,received',
            'items' => 'required|array',
            'items.*.purchase_order_item_id' => 'required|exists:purchase_order_items,id',
            'items.*.product_variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity_received' => 'required|integer|min:0',
            'expiry_date' => 'nullable|date|after_or_equal:today',
            'notes' => 'nullable|string|max:500',
            'selected_taxes' => 'nullable|array',
            'selected_taxes.*' => 'exists:taxes,id',
            'total_tax_amount' => 'nullable|numeric|min:0',
            'net_amount' => 'nullable|numeric|min:0',
            'taxable_amount' => 'nullable|numeric|min:0',
            'batch_number' => 'required|string|max:100', 
        ]);

        // Generate batch number with incoming value + date + random
        $validated['batch_number'] = $this->generateBatchNumber($request->batch_number);

        // Check if purchase order can receive items
        if (!in_array($purchaseOrder->status, ['sent', 'partially_received'])) {
            return response()->json([
                'success' => false,
                'message' => __('passwords.cannot_receive_items_from_current_status'),
            ]);
        }

        DB::beginTransaction();
        try {
            $totalReceived = 0;
            $receiptItems = [];
            $currentReceiptSubtotal = 0;
            $user = auth()->user();
            $isSingleShop = tenant_is_single_shop($tenantId);

            // Get the gross amount from form or calculate from items
            $grossAmount = $request->gross_amount ?? 0;
            
            // Create purchase receipt record first
            $purchaseReceipt = PurchaseReceipt::create([
                'purchase_order_id' => $purchaseOrder->id,
                'received_by' => $user->id,
                'received_at' => now(),
                'batch_number' => $validated['batch_number'] ?? null,
                'expiry_date' => $validated['expiry_date'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Process each item and calculate current receipt subtotal
            foreach ($validated['items'] as $itemData) {
                $quantityReceived = $itemData['quantity_received'];
                
                if ($quantityReceived > 0) {
                    $purchaseOrderItem = PurchaseOrderItem::find($itemData['purchase_order_item_id']);
                    $variant = ProductVariant::with('product')->find($itemData['product_variant_id']);
                    
                    // Validate quantity doesn't exceed ordered quantity
                    $newReceivedQuantity = $purchaseOrderItem->received_quantity + $quantityReceived;
                    if ($newReceivedQuantity > $purchaseOrderItem->quantity) {
                        throw new \Exception(__('passwords.cannot_receive_more_than_ordered'));
                    }

                    // Calculate item cost for this receipt
                    $itemCost = $purchaseOrderItem->unit_cost * $quantityReceived;
                    $currentReceiptSubtotal += $itemCost;

                    // ── Determine inventory strategy for THIS variant's product ─────
                    // Only 'quantity' and 'batch' are handled here. Anything else
                    // ('serial', 'recipe') is out of scope for PO receiving and
                    // is resolved/consumed elsewhere in the app.
                    $strategy = $variant->product
                        ? $variant->product->resolvedInventoryStrategy()
                        : 'quantity';

                    $quantityBefore = $variant->overal_quantity_at_hand;
                    $quantityRemainingForBatch = null;

                    if ($strategy === 'batch') {
                        // ── BATCH STRATEGY ────────────────────────────────────
                        // Do NOT touch overal_quantity_at_hand. The batch itself
                        // (this PurchaseReceiptItem row) is the sellable stock
                        // ledger — quantity_remaining starts at the full amount
                        // received and gets depleted at sale time by whatever
                        // FIFO/allocation logic runs at the POS layer.
                        //
                        // location_id / department_id are left null on the
                        // batch — it isn't assigned to a specific department
                        // yet. That allocation is a separate step, not part of
                        // receiving.
                        $quantityAfter = $quantityBefore; // unchanged, by design
                        $quantityRemainingForBatch = $quantityReceived;
                    } else {
                        // ── QUANTITY STRATEGY (default / unchanged behaviour) ──
                        $variant->overal_quantity_at_hand += $quantityReceived;
                        $variant->save();
                        $quantityAfter = $variant->overal_quantity_at_hand;
                    }

                    // Update purchase order item received quantity — this
                    // tracks PO fulfilment regardless of inventory strategy,
                    // so it's unaffected by the branch above.
                    $purchaseOrderItem->received_quantity = $newReceivedQuantity;
                    $purchaseOrderItem->save();

                    $totalReceived += $quantityReceived;
                    
                    // Create receipt item — quantity_remaining is only
                    // meaningful for batch-strategy variants; left null for
                    // quantity-strategy ones since overal_quantity_at_hand is
                    // their source of truth instead.
                    $receiptItem = PurchaseReceiptItem::create([
                        'purchase_receipt_id' => $purchaseReceipt->id,
                        'purchase_order_item_id' => $purchaseOrderItem->id,
                        'quantity_received' => $quantityReceived,
                        'quantity_remaining' => $quantityRemainingForBatch,
                        'unit_cost' => $purchaseOrderItem->unit_cost,
                        'batch_number' => $validated['batch_number'] ?? null,
                        'expiry_date' => $validated['expiry_date'] ?? null,
                    ]);

                    // ✅ Log batch receipt for batch-strategy items
                    if ($strategy === 'batch' && $quantityReceived > 0) {
                        $this->logBatchReceipt(
                            $receiptItem,        // batchItem
                            $variant,            // variant
                            $purchaseOrder,      // purchaseOrder
                            $purchaseReceipt,    // purchaseReceipt
                            $quantityReceived,   // ✅ quantityReceived (the integer)
                            $user,               // user
                            $tenantId            // tenantId
                        );
                    }

                    // Log received product variant — always recorded for audit,
                    // regardless of strategy. Metadata notes which strategy
                    // applied so the trail is self-explanatory later.
                    ReceivedProductVariant::create([
                        'purchase_order_id' => $purchaseOrder->id,
                        'purchase_receipt_id' => $purchaseReceipt->id,
                        'purchase_order_item_id' => $purchaseOrderItem->id,
                        'product_variant_id' => $variant->id,
                        'quantity_received' => $quantityReceived,
                        'unit_cost' => $purchaseOrderItem->unit_cost,
                        'total_cost' => $itemCost,
                        'batch_number' => $validated['batch_number'] ?? null,
                        'expiry_date' => $validated['expiry_date'] ?? null,
                        'notes' => $validated['notes'] ?? null,
                        'inventory_quantity_before' => $quantityBefore,
                        'inventory_quantity_after' => $quantityAfter,
                        'received_by' => $user->id,
                        'tenant_id' => $tenantId,
                    ]);

                    // LOG TO SINGLE SHOP INVENTORY LOG — only for quantity-strategy
                    // items. This log specifically tracks overal_quantity_at_hand
                    // movements; batch-strategy items didn't move that field, so
                    // logging a zero-change entry here would be misleading noise.
                    if ($isSingleShop && $strategy === 'quantity') {
                        SingleShopInventoryLog::create([
                            'variant_id' => $variant->id,
                            'order_id' => $purchaseOrder->id,
                            'tenant_id' => $tenantId,
                            'created_by' => $user->id,
                            'quantity_before' => $quantityBefore,
                            'quantity_after' => $quantityAfter,
                            'quantity_change' => $quantityReceived,
                            'reason' => 'purchase_receipt',
                            'notes' => 'Stock received from Purchase Order #' . $purchaseOrder->po_number . 
                                    ' - Receipt #' . $purchaseReceipt->id,
                            'source' => 'purchase',
                            'metadata' => [
                                'purchase_order_id' => $purchaseOrder->id,
                                'purchase_receipt_id' => $purchaseReceipt->id,
                                'purchase_order_item_id' => $purchaseOrderItem->id,
                                'batch_number' => $validated['batch_number'] ?? null,
                                'expiry_date' => $validated['expiry_date'] ?? null,
                                'unit_cost' => $purchaseOrderItem->unit_cost,
                                'total_cost' => $itemCost,
                                'inventory_strategy' => $strategy,
                            ],
                        ]);
                    }

                    $receiptItems[] = $receiptItem;
                }
            }

            // Determine taxable amount for this receipt
            $taxableAmount = $grossAmount > 0 ? $grossAmount : $currentReceiptSubtotal;
            
            // Calculate taxes for this receipt only
            $currentReceiptTaxAmount = 0;
            $currentReceiptAdditiveTax = 0;
            $currentReceiptWithholdingTax = 0;
            $taxBreakdown = [];
            $taxLiabilities = [];
            
            if (!empty($validated['selected_taxes'])) {
                $taxes = Tax::whereIn('id', $validated['selected_taxes'])
                    ->where('tenant_id', $tenantId)
                    ->where('is_active', true)
                    ->get();
                
                foreach ($taxes as $tax) {
                    // Calculate tax amount based on taxable amount (subtotal)
                    if ($tax->type === Tax::TYPE_PERCENTAGE) {
                        $taxAmount = $taxableAmount * ($tax->rate / 100);
                    } else {
                        $taxAmount = $tax->rate;
                    }
                    
                    $currentReceiptTaxAmount += $taxAmount;
                    
                    if ($tax->is_withholding_tax) {
                        $currentReceiptWithholdingTax += $taxAmount;
                    } else {
                        $currentReceiptAdditiveTax += $taxAmount;
                    }
                    
                    $taxBreakdown[] = [
                        'tax_id' => $tax->id,
                        'tax_name' => $tax->name,
                        'tax_code' => $tax->code,
                        'rate' => $tax->rate,
                        'type' => $tax->type,
                        'amount' => $taxAmount,
                        'is_withholding_tax' => $tax->is_withholding_tax ?? false,
                    ];
                    
                    // Create tax liability record for this receipt
                    $taxLiabilities[] = SupplierTaxLiability::create([
                        'tenant_id' => $tenantId,
                        'supplier_id' => $purchaseOrder->supplier_id,
                        'purchase_order_id' => $purchaseOrder->id,
                        'purchase_receipt_id' => $purchaseReceipt->id,
                        'taxable_amount' => $taxableAmount,
                        'tax_amount' => $taxAmount,
                        'tax_rate' => $tax->rate,
                        'tax_name' => $tax->name,
                        'tax_code' => $tax->code,
                        'tax_type' => $tax->type,
                        'reference_number' => $purchaseOrder->po_number . '-' . $purchaseReceipt->id,
                        'transaction_date' => now(),
                        'due_date' => $this->calculateTaxDueDate(),
                        'status' => 'pending',
                        'tax_year' => now()->year,
                        'tax_month' => now()->month,
                        'tax_quarter' => ceil(now()->month / 3),
                        'is_withholding_tax' => $tax->is_withholding_tax ?? false,
                        'notes' => 'Receipt #' . $purchaseReceipt->id . ' from PO #' . $purchaseOrder->po_number,
                        'metadata' => [
                            'purchase_order_number' => $purchaseOrder->po_number,
                            'receipt_id' => $purchaseReceipt->id,
                            'supplier_name' => $purchaseOrder->supplier->name,
                            'items_received' => $totalReceived,
                        ],
                    ]);
                }
            }
            
            // Calculate current receipt total payable (for tracking only, no payment)
            $currentReceiptPayable = $currentReceiptSubtotal + $currentReceiptAdditiveTax - $currentReceiptWithholdingTax;
            
            // Get current cumulative totals from the purchase order
            $cumulativeSubtotal = $purchaseOrder->received_subtotal ?? 0;
            $cumulativeTaxTotal = $purchaseOrder->received_tax_total ?? 0;
            $cumulativeTotal = $purchaseOrder->received_total ?? 0;
            
            // Update cumulative totals with current receipt values
            $newCumulativeSubtotal = $cumulativeSubtotal + $currentReceiptSubtotal;
            $newCumulativeTaxTotal = $cumulativeTaxTotal + $currentReceiptTaxAmount;
            $newCumulativeTotal = $cumulativeTotal + $currentReceiptPayable;
            
            // Log the calculation for debugging
            // \Log::info('Receipt calculation (no payment)', [
            //     'receipt_subtotal' => $currentReceiptSubtotal,
            //     'receipt_additive_tax' => $currentReceiptAdditiveTax,
            //     'receipt_withholding_tax' => $currentReceiptWithholdingTax,
            //     'receipt_tax' => $currentReceiptTaxAmount,
            //     'receipt_payable' => $currentReceiptPayable,
            //     'cumulative_subtotal_before' => $cumulativeSubtotal,
            //     'cumulative_subtotal_after' => $newCumulativeSubtotal,
            //     'cumulative_tax_before' => $cumulativeTaxTotal,
            //     'cumulative_tax_after' => $newCumulativeTaxTotal,
            //     'cumulative_total_before' => $cumulativeTotal,
            //     'cumulative_total_after' => $newCumulativeTotal,
            // ]);

            // ✅ REMOVED: Payment transaction - payment already happened when sending to supplier

            // ✅ REMOVED: Payment method update on items - handled when sending

            // Update purchase order with CUMULATIVE totals
            $purchaseOrder->received_subtotal = $newCumulativeSubtotal;
            $purchaseOrder->received_tax_total = $newCumulativeTaxTotal;
            $purchaseOrder->received_total = $newCumulativeTotal;
            $purchaseOrder->subtotal = $purchaseOrder->subtotal ?? $newCumulativeSubtotal;
            $purchaseOrder->tax_total = $purchaseOrder->tax_total ?? $newCumulativeTaxTotal;
            $purchaseOrder->total = $purchaseOrder->total ?? $newCumulativeTotal;
            $purchaseOrder->status = $validated['status'];
            $purchaseOrder->received_at = now();
            $purchaseOrder->received_by = $user->id;
            $purchaseOrder->save();

            DB::commit();

            $response = [
                'success' => true,
                'message' => $validated['status'] === 'received' 
                    ? __('passwords.items_fully_received_success')
                    : __('passwords.items_partially_received_success'),
                'reload' => true,
                'data' => [
                    'total_received' => $totalReceived,
                    'receipt_subtotal' => $currentReceiptSubtotal,
                    'receipt_additive_tax' => $currentReceiptAdditiveTax,
                    'receipt_withholding_tax' => $currentReceiptWithholdingTax,
                    'receipt_tax' => $currentReceiptTaxAmount,
                    'receipt_payable' => $currentReceiptPayable,
                    'cumulative_subtotal' => $newCumulativeSubtotal,
                    'cumulative_tax' => $newCumulativeTaxTotal,
                    'cumulative_total' => $newCumulativeTotal,
                    'purchase_receipt_id' => $purchaseReceipt->id,
                ]
            ];

            if (!empty($taxLiabilities)) {
                $response['tax_liabilities'] = collect($taxLiabilities)->map(function($liability) {
                    return [
                        'id' => $liability->id,
                        'tax_name' => $liability->tax_name,
                        'tax_amount' => $liability->tax_amount,
                        'status' => $liability->status,
                        'due_date' => $liability->due_date,
                    ];
                });
            }
            
            session()->flash('toast', [
                'type' => 'success',
                'message' => $response['message'],
            ]);

            return response()->json($response);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error receiving items: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);
            return response()->json([
                'success' => false,
                'message' => __('passwords.receiving_error') . ': ' . $e->getMessage(),
            ]);
        }
    }

    /**
    * Log a batch receipt event
    */
    private function logBatchReceipt($batchItem, $variant, $purchaseOrder, $purchaseReceipt, $quantityReceived, $user, $tenantId)
    {
        try {
            // Ensure quantityReceived is a number
            $quantityReceived = (int) $quantityReceived;
            
            if ($quantityReceived <= 0) {
                \Log::warning('[Batch Receipt] Skipped - quantity is zero or negative', [
                    'batch_id' => $batchItem->id ?? null,
                    'quantity' => $quantityReceived
                ]);
                return; // Don't log zero or negative quantities
            }

            // Get unit cost from batch item
            $unitCost = (float) ($batchItem->unit_cost ?? 0);
            $totalCost = $unitCost * $quantityReceived;

            // Ensure we have a valid batch number
            $batchNumber = $batchItem->batch_number ?? 'BATCH-' . $batchItem->id;

            BatchLog::create([
                'batch_id' => $batchItem->id,
                'batch_number' => $batchNumber,
                'variant_id' => $variant->id,
                'variant_name' => $variant->name,
                'variant_sku' => $variant->sku,
                'type' => BatchLog::TYPE_RECEIVED,
                'quantity_change' => $quantityReceived,
                'quantity_before' => 0,
                'quantity_after' => $quantityReceived,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'purchase_order_id' => $purchaseOrder->id,
                'purchase_order_number' => $purchaseOrder->po_number,
                'purchase_receipt_id' => $purchaseReceipt->id,
                'supplier_id' => $purchaseOrder->supplier_id,
                'supplier_name' => $purchaseOrder->supplier ? $purchaseOrder->supplier->name : null,
                'tenant_id' => $tenantId,
                'expiry_date' => $batchItem->expiry_date,
                'event_date' => now(),
                'performed_by' => $user->id,
                'metadata' => [
                    'location_id' => $purchaseOrder->location_id,
                    'department_id' => $purchaseOrder->department_id ?? null,
                    'inventory_strategy' => 'batch',
                    'receipt_notes' => $purchaseReceipt->notes,
                ],
            ]);

            // \Log::info('[Batch Receipt] Logged successfully', [
            //     'batch_id' => $batchItem->id,
            //     'batch_number' => $batchNumber,
            //     'quantity' => $quantityReceived,
            //     'total_cost' => $totalCost,
            //     'variant' => $variant->name
            // ]);

        } catch (\Exception $e) {
            // Log error but don't break the receipt process
            \Log::error('Failed to log batch receipt: ' . $e->getMessage(), [
                'batch_id' => $batchItem->id ?? null,
                'variant_id' => $variant->id ?? null,
                'quantity_received' => $quantityReceived ?? null,
            ]);
        }
    }

    /**
     * Calculate tax due date (15th of following month)
     */
    private function calculateTaxDueDate()
    {
        return now()->addMonth()->startOfMonth()->addDays(14);
    }

    public function calculateTaxPreview(Request $request)
    {
        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;

            $request->validate([
                'taxable_amount' => 'required|numeric|min:0',
                'selected_taxes' => 'required|array',
                'selected_taxes.*' => 'exists:taxes,id',
            ]);

            $taxableAmount = $request->taxable_amount;
            $selectedTaxIds = $request->selected_taxes;

            $taxes = Tax::whereIn('id', $selectedTaxIds)
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->get();

            $totalTax = 0;
            $additiveTax = 0;
            $withholdingTax = 0;
            $breakdown = [];

            foreach ($taxes as $tax) {
                // Calculate tax amount
                if ($tax->type === Tax::TYPE_PERCENTAGE) {
                    $taxAmount = $taxableAmount * ($tax->rate / 100);
                } else {
                    $taxAmount = $tax->rate;
                }

                $totalTax += $taxAmount;
                
                if ($tax->is_withholding_tax) {
                    $withholdingTax += $taxAmount;
                } else {
                    $additiveTax += $taxAmount;
                }

                $breakdown[] = [
                    'id' => $tax->id,
                    'name' => $tax->name,
                    'code' => $tax->code,
                    'rate' => $tax->rate,
                    'type' => $tax->type,
                    'amount' => $taxAmount,
                    'formatted_rate' => $tax->formatted_rate,
                    'is_withholding_tax' => $tax->is_withholding_tax,
                ];
            }

            $netPayable = $taxableAmount + $additiveTax - $withholdingTax;

            return response()->json([
                'success' => true,
                'data' => [
                    'taxable_amount' => $taxableAmount,
                    'total_tax' => $totalTax,
                    'additive_tax' => $additiveTax,
                    'withholding_tax' => $withholdingTax,
                    'net_payable' => $netPayable,
                    'tax_breakdown' => $breakdown,
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Tax preview error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }



    public function cancel(Request $request, $id)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('cancel purchase_orders')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }
        
        $validated = $request->validate([
            'status' => 'required', 
        ]);
        
        $purchase = PurchaseOrder::where('id', $id)
                        ->where('tenant_id', $tenantId)
                        ->first();

        if (!$purchase) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ]);
        }

        // Check if status is already cancelled
        if ($purchase->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => __('passwords.already_cancelled'),
            ]);
        }

        // Validate that status transition is allowed (only from draft, pending_approval, or approved)
        $allowedStatuses = ['draft', 'pending_approval', 'approved'];
        if (!in_array($purchase->status, $allowedStatuses)) {
            return response()->json([
                'success' => false,
                'message' => __('passwords.can_only_cancel_from_allowed_status'),
            ]);
        }

        // Validate that the requested status is cancelled
        if ($validated['status'] !== 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => __('passwords.invalid_status_transition'),
            ]);
        }

        DB::beginTransaction();
        try {
            // Update purchase order status
            $purchase->status = $validated['status'];
            $purchase->cancelled_at = now();
            $purchase->cancelled_by = auth()->id();
            
            if ($purchase->save()) {  
                DB::commit();
                
                return response()->json([
                    'success' => true,
                    'reload' => true,
                    'refresh' => false,
                    'componentId' => 'reloadPurchasesComponent',
                    'message' => __('passwords.cancel_success'),
                    'redirect' => route('purchase_order.index'),
                ]);
            }

            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => __('passwords.status_update_failed'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => __('passwords.error_occurred') . $e->getMessage(),
            ]);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('edit purchase_orders')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        $purchaseOrder = PurchaseOrder::where('id', $id)
                        ->where('tenant_id', $tenantId)
                        ->first();

        if (!$purchaseOrder) {
            return response()->json([
                'success' => false,
                'message' => __('passwords.not_found'),
            ]);
        }

        // // Check tenant access
        // if ($purchaseOrder->tenant_id !== auth()->user()->tenant_id) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => __('auth.unauthorized'),
        //     ]);
        // }

        // Only allow deletion of draft or cancelled purchase orders
        if (!in_array($purchaseOrder->status, ['draft', 'cancelled'])) {
            return response()->json([
                'success' => false,
                'message' => __('passwords.can_only_delete_draft_or_cancelled'),
            ]);
        }

        // Check if purchase order has any received items
        if ($purchaseOrder->items()->where('received_quantity', '>', 0)->exists()) {
            return response()->json([
                'success' => false,
                'message' => __('passwords.has_received_items'),
            ]);
        }

        // Check if purchase order has any purchase receipts
        if ($purchaseOrder->receipts()->exists()) {
            return response()->json([
                'success' => false,
                'message' => __('passwords.has_related_receipts'),
            ]);
        }

        DB::beginTransaction();
        try {
            // Delete related records first
            $purchaseOrder->items()->delete();
            
            // Delete any related received product variants
            if (class_exists('App\Models\ReceivedProductVariant')) {
                \App\Models\ReceivedProductVariant::where('purchase_order_id', $purchaseOrder->id)->delete();
            }
            
            // Delete the purchase order
            $purchaseOrder->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'reload' => true,
                'componentId' => 'reloadPurchasesComponent',
                'refresh' => false,
                'message' => __('auth._deleted'),
                'redirect' => route('purchase-orders.index'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => __('passwords.delete_error') . $e->getMessage(),
            ]);
        }
    }
}
