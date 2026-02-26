<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{ Supplier, PurchaseOrder, ProductVariant, PurchaseOrderItem, InventoryItems, PaymentMethod,
        PurchaseReceipt, InventoryTransactions, InventoryAdjustment, PurchaseReceiptItem, SingleShopInventoryLog,
        Location, Department };
use Illuminate\Support\Facades\{ Auth, DB };


class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('view purchase_orders')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }
        
        // Build the query
        $query = PurchaseOrder::with(['items', 'supplier', 'location', 'creator']);
        
        // If user is NOT super_admin, filter by tenant
        if (!$user->hasRole('super_admin')) {
            $query->where('tenant_id', current_tenant_id());
        }
        
        $purchaseOrders = $query->latest()->get();

        $bladeToReload = $request->query('bladeFileToReload');
        switch ($bladeToReload) {
            case 'reloadPurchasesComponent':
                return view('procurement.purchase-order.component', [
                    'purchaseOrders' => $purchaseOrders,
                ]);
            default:
                return view('procurement.po-index', [
                    'purchaseOrders' => $purchaseOrders,
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
            'status' => 'required', 
        ]);
        
        $purchase = PurchaseOrder::with(['supplier', 'items.productVariant'])
                        ->where('id', $id)
                        ->where('tenant_id', $tenantId)
                        ->first();

        if (!$purchase) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ]);
        }

        // Check if status is already sent
        if ($purchase->status === 'sent') {
            return response()->json([
                'success' => false,
                'message' => __('passwords.already_sent'),
            ]);
        }

        // Validate that status transition is allowed (only from approved to sent)
        if ($purchase->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => __('passwords.can_only_send_from_approved'),
            ]);
        }

        // Validate that the requested status is sent
        if ($validated['status'] !== 'sent') {
            return response()->json([
                'success' => false,
                'message' => __('passwords.invalid_status_transition'),
            ]);
        }

        DB::beginTransaction();
        try {
            // Update purchase order status
            $purchase->status = $validated['status'];
            $purchase->sent_at = now();
            $purchase->sent_by = auth()->id();
            
            if ($purchase->save()) {  
                DB::commit();
                
                // Send email to supplier if email exists
                if ($purchase->supplier && $purchase->supplier->email) {
                    $this->sendPurchaseOrderEmail($purchase);
                }


                return response()->json([
                    'success' => true,
                    'reload' => true,
                    'refresh' => false,
                    'componentId' => 'reloadPurchasesComponent',
                    'message' => __('passwords.send_supplier_success'),
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

        $validated = $request->validate([
            'status' => 'required|in:partially_received,received',
            'items' => 'required|array',
            'items.*.purchase_order_item_id' => 'required|exists:purchase_order_items,id',
            'items.*.product_variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity_received' => 'required|integer|min:0',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'payment_status' => 'nullable|in:pending,partial,paid,overdue',
            'payment_date' => 'nullable|date',
            'batch_number' => 'nullable|string|max:100',
            'expiry_date' => 'nullable|date|after_or_equal:today',
            'notes' => 'nullable|string|max:500',
        ]);

        // Check if purchase order can receive items
        if (!in_array($purchaseOrder->status, ['sent', 'partially_received'])) {
            return response()->json([
                'success' => false,
                'message' => __('passwords.cannot_receive_items_from_current_status'),
            ]);
        }

        $tenantId = auth()->user()->tenant_id;
        $isSingleShop = tenant_is_single_shop($tenantId);

        DB::beginTransaction();
        try {
            $totalReceived = 0;
            $receiptItems = [];
            $totalCost = 0; // Track total cost for payment processing
            $user = auth()->user();

            // Create purchase receipt record first
            $purchaseReceipt = PurchaseReceipt::create([
                'purchase_order_id' => $purchaseOrder->id,
                'received_by' => $user->id,
                'received_at' => now(),
                'notes' => $validated['notes'] ?? null,
            ]);

            // Process each item
            foreach ($validated['items'] as $itemData) {
                $quantityReceived = $itemData['quantity_received'];
                
                if ($quantityReceived > 0) {
                    $purchaseOrderItem = PurchaseOrderItem::find($itemData['purchase_order_item_id']);
                    $variant = ProductVariant::find($itemData['product_variant_id']);
                    
                    // Validate quantity doesn't exceed ordered quantity
                    $newReceivedQuantity = $purchaseOrderItem->received_quantity + $quantityReceived;
                    if ($newReceivedQuantity > $purchaseOrderItem->quantity) {
                        throw new \Exception(__('passwords.cannot_receive_more_than_ordered'));
                    }

                    // Calculate item cost for payment (cost * quantity)
                    $itemCost = $purchaseOrderItem->unit_cost * $quantityReceived;
                    $totalCost += $itemCost; // Accumulate total cost

                    // Get current inventory quantity before update
                    $quantityBefore = $variant->overal_quantity_at_hand;

                    // Update purchase order item
                    $purchaseOrderItem->received_quantity = $newReceivedQuantity;
                    $purchaseOrderItem->save();

                    // Update product variant inventory
                    $variant->overal_quantity_at_hand += $quantityReceived;
                    $variant->save();

                    // Get quantity after update
                    $quantityAfter = $variant->overal_quantity_at_hand;

                    $totalReceived += $quantityReceived;
                    
                    // Create receipt item
                    $receiptItem = PurchaseReceiptItem::create([
                        'purchase_receipt_id' => $purchaseReceipt->id,
                        'purchase_order_item_id' => $purchaseOrderItem->id,
                        'quantity_received' => $quantityReceived,
                        'batch_number' => $validated['batch_number'] ?? null,
                        'expiry_date' => $validated['expiry_date'] ?? null,
                    ]);

                    // Log received product variant
                    \App\Models\ReceivedProductVariant::create([
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

                    // ✅ LOG TO SINGLE SHOP INVENTORY LOG IF SINGLE SHOP
                    if ($isSingleShop) {
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
                            ],
                        ]);
                    }

                    // Store receipt item data for response
                    $receiptItems[] = $receiptItem;
                }
            }

            // ✅ INTEGRATE PAYMENT WITHDRAWAL FROM ACCOUNT
            // Only process payment if payment method is provided and there's a total cost
            if (isset($validated['payment_method_id']) && $totalCost > 0) {
                $paymentMethod = PaymentMethod::findForTenant($validated['payment_method_id'], $tenantId);
                
                if (!$paymentMethod) {
                    throw new \Exception(__('pagination.payment_method_not_found'));
                }
                
                // Validate the payment method can handle this transaction
                $validation = $paymentMethod->validateTransaction($totalCost);
                if (!$validation['success']) {
                    throw new \Exception($validation['message']);
                }

                // Record purchase order payment withdrawal using PaymentTransactionService
                $transactionData = [
                    'user_id' => $user->id,
                    'tenant_id' => $tenantId,
                    'payment_method_id' => $paymentMethod->id,
                    'transaction_type' => 'WITHDRAWAL',
                    'transaction_category' => 'PURCHASE_ORDER',
                    'amount' => $totalCost,
                    'currency_id' => $paymentMethod->currency_id ?? \App\Models\Currency::default()->id,
                    'reference_table' => 'purchase_orders',
                    'reference_id' => $purchaseOrder->id,
                    'description' => 'Purchase Order Payment - PO #' . $purchaseOrder->po_number,
                    'notes' => 'Payment for received items (cost × quantity)',
                    'metadata' => [
                        'purchase_order_number' => $purchaseOrder->po_number,
                        'purchase_receipt_id' => $purchaseReceipt->id,
                        'total_items_received' => $totalReceived,
                        'total_cost' => $totalCost,
                        'payment_status' => $validated['payment_status'] ?? 'paid',
                        'payment_date' => $validated['payment_date'] ?? now()->toDateString(),
                        'items_count' => count($validated['items']),
                        'receiver_id' => $user->id,
                        'receiver_name' => $user->name,
                        'transaction_nature' => 'PURCHASE_PAYMENT',
                    ],
                ];

                // Add payment date if provided
                if (isset($validated['payment_date'])) {
                    $transactionData['effective_date'] = $validated['payment_date'];
                }

                // Record the withdrawal transaction
                $transactionLog = app('payment-transaction')->recordTransaction($transactionData);
                
                // \Log::info('Payment withdrawal recorded for PO #' . $purchaseOrder->po_number, [
                //     'transaction_ref' => $transactionLog->transaction_ref ?? 'N/A',
                //     'amount' => $totalCost,
                //     'payment_method' => $paymentMethod->name,
                //     'items_received' => $totalReceived,
                // ]);
            }

            // Update payment information for ALL items that were received
            // Only update if payment information was provided
            if (isset($validated['payment_method_id']) || isset($validated['payment_status'])) {
                $itemIds = collect($validated['items'])
                    ->pluck('purchase_order_item_id')
                    ->toArray();
                
                $updateData = [];
                
                if (isset($validated['payment_method_id'])) {
                    $updateData['payment_method_id'] = $validated['payment_method_id'];
                }
                
                if (isset($validated['payment_status'])) {
                    $updateData['payment_status'] = $validated['payment_status'];
                }
                
                if (isset($validated['payment_date'])) {
                    $updateData['payment_date'] = $validated['payment_date'];
                }
                
                // Update all received items with the same payment information
                PurchaseOrderItem::whereIn('id', $itemIds)
                    ->update($updateData);
            }

            // Update purchase order status
            $purchaseOrder->status = $validated['status'];
            $purchaseOrder->received_at = now();
            $purchaseOrder->received_by = $user->id;
            $purchaseOrder->save();

            DB::commit();

            // Prepare response
            $response = [
                'success' => true,
                'message' => $validated['status'] === 'received' 
                    ? __('passwords.items_fully_received_success')
                    : __('passwords.items_partially_received_success'),
                'reload' => true,
                'data' => [
                    'total_received' => $totalReceived,
                    'total_cost' => $totalCost,
                    'purchase_receipt_id' => $purchaseReceipt->id,
                ]
            ];

            // Add payment info to response if payment was processed
            if (isset($paymentMethod)) {
                $response['payment_info'] = [
                    'payment_method' => $paymentMethod->name,
                    'amount_withdrawn' => $totalCost,
                    'transaction_completed' => true,
                ];
            }
            
            session()->flash('toast', [
                'type' => 'success',
                'message' => $response['message'],
            ]);

            return response()->json($response);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error receiving items: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('passwords.receiving_error') . $e->getMessage(),
            ]);
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
