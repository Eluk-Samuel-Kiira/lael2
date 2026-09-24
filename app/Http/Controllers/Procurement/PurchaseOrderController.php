<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{ Supplier, PurchaseOrder, ProductVariant, PurchaseOrderItem, InventoryItems, PaymentMethod,
        PurchaseReceipt, InventoryTransactions, InventoryAdjustment, PurchaseReceiptItem, SingleShopInventoryLog,
        Location, Department, SupplierTaxLiability, Tax, ReceivedProductVariant, Tenant, BatchLog };
use Illuminate\Support\Facades\{ Auth, DB };
use Illuminate\Support\Str;
use App\Mail\PurchaseOrderDocumentMail;
use App\Services\MessagingService;
use Illuminate\Support\Facades\Mail;


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
                        'tax_amount' => 0,
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

    

    public function sendDocument(Request $request, $id, MessagingService $messaging)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        $purchase = PurchaseOrder::with(['items', 'supplier'])
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$purchase) {
            return response()->json(['success' => false, 'message' => __('auth._not_found')], 404);
        }

        $validated = $request->validate([
            'channel' => 'required|in:email,whatsapp',
            'email'   => 'nullable|email',
            'phone'   => ['nullable', 'regex:/^\+[1-9]\d{7,14}$/'],
            'subject' => 'nullable|string|max:255',
            'message' => 'nullable|string',
        ], [
            'phone.regex' => __('payments.invalid_phone_format'),
        ]);

        $channel = $validated['channel'];

        try {
            if ($channel === 'email') {
                $emailToUse = $validated['email'] ?? $purchase->supplier->email ?? null;
                if (!$emailToUse) {
                    return response()->json(['success' => false, 'message' => __('payments.customer_email_required')], 422);
                }

                $subject = $validated['subject'] ?? __('passwords.purchase_order_subject', ['number' => $purchase->po_number]);

                Mail::to($emailToUse)->send(
                    new PurchaseOrderDocumentMail($purchase, $subject, $validated['message'] ?? null)
                );

                return response()->json(['success' => true, 'message' => __('passwords.purchase_order_sent')]);
            }

            if ($channel === 'whatsapp') {
                $phoneToUse = $validated['phone'] ?? $purchase->supplier->phone ?? null;
                if (!$phoneToUse) {
                    return response()->json(['success' => false, 'message' => __('payments.customer_phone_required')], 422);
                }

                $result = $messaging->sendWhatsApp($phoneToUse, [
                    'ref'  => $purchase->po_number,
                    'date' => $purchase->created_at->format('d M Y'),
                ]);

                if (!$result['success']) {
                    return response()->json(['success' => false, 'message' => $result['error']], 500);
                }

                return response()->json(['success' => true, 'message' => __('passwords.purchase_order_sent')]);
            }
        } catch (\Exception $e) {
            \Log::error('Purchase order document send failed: ' . $e->getMessage(), ['purchase_order_id' => $id]);
            return response()->json(['success' => false, 'message' => __('passwords.purchase_order_send_failed')], 500);
        }
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

    /**
     * Send the purchase order to the supplier (email or WhatsApp).
     * Pure status transition (approved -> sent) + notification.
     * No money moves here — payment is recorded later at receiveItems(),
     * against the actual quantities and actual unit costs invoiced.
     */
    public function sendToSupplier(Request $request, $id, MessagingService $messaging)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('send purchase_orders')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

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

        if ($purchase->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => __('passwords.can_only_send_from_approved'),
            ]);
        }

        $validated = $request->validate([
            'channel' => 'required|in:email,whatsapp',
            'supplier_email' => 'nullable|email',
            'supplier_phone' => ['nullable', 'regex:/^\+[1-9]\d{7,14}$/'],
            'notes' => 'nullable|string|max:500',
        ], [
            'supplier_phone.regex' => __('payments.invalid_phone_format'),
        ]);

        $channel = $validated['channel'];

        if ($channel === 'email' && empty($validated['supplier_email']) && empty($purchase->supplier->email)) {
            return response()->json([
                'success' => false,
                'message' => __('payments.customer_email_required'),
            ], 422);
        }

        if ($channel === 'whatsapp' && empty($validated['supplier_phone']) && empty($purchase->supplier->phone)) {
            return response()->json([
                'success' => false,
                'message' => __('payments.customer_phone_required'),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $purchase->status = 'sent';
            $purchase->sent_at = now();
            $purchase->sent_by = $user->id;
            if (!empty($validated['notes'])) {
                $purchase->notes = $validated['notes'];
            }
            $purchase->save();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Send to supplier failed: ' . $e->getMessage(), ['purchase_order_id' => $id]);
            return response()->json([
                'success' => false,
                'message' => __('passwords.error_occurred') . $e->getMessage(),
            ], 500);
        }

        // Status has already committed at this point — a notification
        // hiccup shouldn't roll back the status change, but we do log it
        // and let the user know so they can follow up manually.
        $notifyWarning = null;

        try {
            if ($channel === 'email') {
                $emailToUse = $validated['supplier_email'] ?? $purchase->supplier->email;
                $subject = __('passwords.purchase_order_subject', ['number' => $purchase->po_number]);

                Mail::to($emailToUse)->send(
                    new PurchaseOrderDocumentMail($purchase, $subject, $validated['notes'] ?? null)
                );
            } else {
                $phoneToUse = $validated['supplier_phone'] ?? $purchase->supplier->phone;

                $result = $messaging->sendWhatsApp($phoneToUse, [
                    'ref'  => $purchase->po_number,
                    'date' => $purchase->created_at->format('d M Y'),
                ]);

                if (!($result['success'] ?? false)) {
                    $notifyWarning = $result['error'] ?? __('passwords.purchase_order_send_failed');
                    \Log::error('Failed to send PO via WhatsApp: ' . $notifyWarning, ['purchase_order_id' => $id]);
                }
            }
        } catch (\Exception $e) {
            $notifyWarning = $e->getMessage();
            \Log::error('Failed to notify supplier: ' . $e->getMessage(), ['purchase_order_id' => $id]);
        }

        return response()->json([
            'success' => true,
            'reload' => true,
            'refresh' => false,
            'componentId' => 'reloadPurchasesComponent',
            'message' => $notifyWarning
                ? __('passwords.status_updated_but_notify_failed')
                : __('passwords.send_supplier_success'),
            'redirect' => route('purchase_order.index'),
        ]);
    }


    /**
     * Send purchase order email to supplier with PDF attachment
     */
    private function sendPurchaseOrderEmail(PurchaseOrder $purchaseOrder)
    {
        try {
            $supplier = $purchaseOrder->supplier;
            
            if (!$supplier || !$supplier->email) {
                \Log::warning('Cannot send purchase order email - supplier email not found', [
                    'purchase_order_id' => $purchaseOrder->id,
                    'supplier_id' => $purchaseOrder->supplier_id
                ]);
                return;
            }
            
            $subject = 'Purchase Order #' . $purchaseOrder->po_number;
            
            // Use the PurchaseOrderDocumentMail class
            Mail::to($supplier->email)->send(
                new PurchaseOrderDocumentMail(
                    $purchaseOrder,
                    $subject,
                    null // custom message (optional)
                )
            );
            
            // \Log::info('Purchase order email sent to supplier with PDF', [
            //     'purchase_order_id' => $purchaseOrder->id,
            //     'po_number' => $purchaseOrder->po_number,
            //     'supplier_email' => $supplier->email
            // ]);
            
        } catch (\Exception $e) {
            \Log::error('Failed to send purchase order email: ' . $e->getMessage(), [
                'purchase_order_id' => $purchaseOrder->id,
                'trace' => $e->getTraceAsString()
            ]);
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
            'items.*.actual_unit_cost' => 'nullable|numeric|min:0', // The actual cost from supplier
            'expiry_date' => 'nullable|date|after_or_equal:today',
            'notes' => 'nullable|string|max:500',
            'selected_taxes' => 'nullable|array',
            'selected_taxes.*' => 'exists:taxes,id',
            'batch_number' => 'required|string|max:100',
            'payment_method_id' => 'required_if:payment_amount,gt:0|nullable|exists:payment_methods,id',
            'payment_amount'    => 'required|numeric|gt:0',
            'payment_date'      => 'nullable|date',
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

        // Check for over supply of the ordered quantities
        $overReceipts = [];
        foreach ($validated['items'] as $itemData) {
            if (($itemData['quantity_received'] ?? 0) <= 0) continue;

            $poi = PurchaseOrderItem::find($itemData['purchase_order_item_id']);
            if (!$poi) continue;

            $newTotal = (float) $poi->received_quantity + (float) $itemData['quantity_received'];
            if ($newTotal > (float) $poi->quantity) {
                $overReceipts[] = [
                    'purchase_order_item_id' => $poi->id,
                    'product_name'           => $poi->product_name,
                    'ordered'                => (float) $poi->quantity,
                    'previously_received'    => (float) $poi->received_quantity,
                    'receiving_now'          => (float) $itemData['quantity_received'],
                    'would_total'            => $newTotal,
                    'over_by'                => $newTotal - (float) $poi->quantity,
                ];
            }
        }

        if (!empty($overReceipts) && !$request->boolean('allow_over_receipt')) {
            return response()->json([
                'success'          => false,
                'requires_confirm' => true,
                'message'          => __('passwords.over_receipt_warning'),
                'over_receipts'    => $overReceipts,
            ], 422);
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
                    
                    // Get the actual unit cost from the request, or fallback to the PO unit cost
                    $actualUnitCost = isset($itemData['actual_unit_cost']) && $itemData['actual_unit_cost'] > 0
                        ? $itemData['actual_unit_cost']
                        : $purchaseOrderItem->unit_cost;

                    // Calculate item cost using the ACTUAL unit cost
                    $itemCost = $actualUnitCost * $quantityReceived;
                    $currentReceiptSubtotal += $itemCost;

                    // Get the variant from the purchase order item
                    $variant = ProductVariant::find($itemData['product_variant_id']);
                    
                    if (!$variant) {
                        throw new \Exception("Product variant not found: {$itemData['product_variant_id']}");
                    }

                    // ── UPDATE VARIANT COST PRICE ─────────────────────────────
                    // When receiving items at a different cost, update the variant's
                    // supplier_cost_price. This ensures the cost reflects the actual
                    // price paid to the supplier.
                    if ($actualUnitCost != $variant->supplier_cost_price) {
                        $variant->supplier_cost_price = $actualUnitCost;
                        
                        // Recalculate grand_total_cost_price if there are other costs
                        // This depends on your business logic - you might want to
                        // recalculate based on other cost components
                        if ($variant->total_shipping_cost || $variant->ura_taxes_applied || $variant->additional_expenses) {
                            $variant->grand_total_cost_price = $actualUnitCost 
                                + ($variant->total_shipping_cost ?? 0) 
                                + ($variant->ura_taxes_applied ?? 0) 
                                + ($variant->additional_expenses ?? 0);
                        } else {
                            $variant->grand_total_cost_price = $actualUnitCost;
                        }
                        
                        $variant->save();
                        
                        // Log the cost change for audit
                        // \Log::info('Variant cost updated from PO receipt', [
                        //     'variant_id' => $variant->id,
                        //     'variant_sku' => $variant->sku,
                        //     'old_cost' => $variant->supplier_cost_price,
                        //     'new_cost' => $actualUnitCost,
                        //     'purchase_order_id' => $purchaseOrder->id,
                        //     'purchase_receipt_id' => $purchaseReceipt->id,
                        // ]);
                    }

                    // Determine inventory strategy for THIS variant's product
                    $strategy = $variant->product
                        ? $variant->product->resolvedInventoryStrategy()
                        : 'quantity';

                    $quantityBefore = $variant->overal_quantity_at_hand;
                    $quantityRemainingForBatch = null;

                    if ($strategy === 'batch') {
                        // BATCH STRATEGY - don't touch overall quantity
                        $quantityAfter = $quantityBefore;
                        $quantityRemainingForBatch = $quantityReceived;
                    } else {
                        // QUANTITY STRATEGY - update overall quantity
                        $variant->overal_quantity_at_hand += $quantityReceived;
                        $variant->save();
                        $quantityAfter = $variant->overal_quantity_at_hand;
                    }

                    // Update purchase order item received quantity
                    $newReceivedQuantity = (float) $purchaseOrderItem->received_quantity + (float) $quantityReceived;
                    $purchaseOrderItem->received_quantity = $newReceivedQuantity;
                    $purchaseOrderItem->save();

                    $totalReceived += $quantityReceived;
                    
                    // ✅ Create receipt item with unit_cost field
                    $receiptItem = PurchaseReceiptItem::create([
                        'purchase_receipt_id' => $purchaseReceipt->id,
                        'purchase_order_item_id' => $purchaseOrderItem->id,
                        'quantity_received' => $quantityReceived,
                        'quantity_remaining' => $quantityRemainingForBatch,
                        'unit_cost' => $actualUnitCost, // ✅ Now this field exists
                        'batch_number' => $validated['batch_number'] ?? null,
                        'expiry_date' => $validated['expiry_date'] ?? null,
                        'tenant_id' => $tenantId,
                    ]);

                    // Log batch receipt for batch-strategy items
                    if ($strategy === 'batch' && $quantityReceived > 0) {
                        $this->logBatchReceipt(
                            $receiptItem,
                            $variant,
                            $purchaseOrder,
                            $purchaseReceipt,
                            $quantityReceived,
                            $actualUnitCost, // Pass the actual unit cost
                            $user,
                            $tenantId
                        );
                    }

                    // Log received product variant
                    ReceivedProductVariant::create([
                        'purchase_order_id' => $purchaseOrder->id,
                        'purchase_receipt_id' => $purchaseReceipt->id,
                        'purchase_order_item_id' => $purchaseOrderItem->id,
                        'product_variant_id' => $variant->id,
                        'quantity_received' => $quantityReceived,
                        'unit_cost' => $actualUnitCost, // Use actual cost
                        'total_cost' => $itemCost,
                        'batch_number' => $validated['batch_number'] ?? null,
                        'expiry_date' => $validated['expiry_date'] ?? null,
                        'notes' => $validated['notes'] ?? null,
                        'inventory_quantity_before' => $quantityBefore,
                        'inventory_quantity_after' => $quantityAfter,
                        'received_by' => $user->id,
                        'tenant_id' => $tenantId,
                    ]);

                    // LOG TO SINGLE SHOP INVENTORY LOG
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
                                'unit_cost' => $actualUnitCost,
                                'total_cost' => $itemCost,
                                'inventory_strategy' => $strategy,
                                'supplier_cost_updated' => true,
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
            
            // Calculate current receipt total payable
            $currentReceiptPayable = $currentReceiptSubtotal + $currentReceiptAdditiveTax - $currentReceiptWithholdingTax;
            
            // Get current cumulative totals from the purchase order
            $cumulativeSubtotal = $purchaseOrder->received_subtotal ?? 0;
            $cumulativeTaxTotal = $purchaseOrder->received_tax_total ?? 0;
            $cumulativeTotal = $purchaseOrder->received_total ?? 0;
            
            // Update cumulative totals with current receipt values
            $newCumulativeSubtotal = $cumulativeSubtotal + $currentReceiptSubtotal;
            $newCumulativeTaxTotal = $cumulativeTaxTotal + $currentReceiptTaxAmount;
            $newCumulativeTotal = $cumulativeTotal + $currentReceiptPayable;

            // Process optional payment
            if (!empty($validated['payment_amount']) && $validated['payment_amount'] > 0) {
                $paymentMethod = PaymentMethod::findForTenant($validated['payment_method_id'], $tenantId);
                if (!$paymentMethod) {
                    throw new \Exception(__('pagination.payment_method_not_found'));
                }

                $requestedAmount = (float) $validated['payment_amount'];
                $paymentAmount   = min($requestedAmount, $currentReceiptPayable);

                if ($paymentAmount < $requestedAmount) {
                    \Log::info('[Receiving] Payment clamped to receipt payable', [
                        'purchase_order_id'  => $purchaseOrder->id,
                        'purchase_receipt_id'=> $purchaseReceipt->id,
                        'requested'          => $requestedAmount,
                        'receipt_payable'    => $currentReceiptPayable,
                        'charged'            => $paymentAmount,
                    ]);
                }

                $transactionLog = app('payment-transaction')->recordTransaction([
                    'user_id' => $user->id,
                    'tenant_id' => $tenantId,
                    'payment_method_id' => $paymentMethod->id,
                    'transaction_type' => 'WITHDRAWAL',
                    'transaction_category' => 'PURCHASE_ORDER',
                    'amount' => $paymentAmount,
                    'currency_id' => $paymentMethod->currency_id ?? \App\Models\Currency::default()->id,
                    'reference_table' => 'purchase_orders',
                    'reference_id' => $purchaseOrder->id,
                    'description' => 'Purchase Order Payment - PO #' . $purchaseOrder->po_number . ' (Receipt #' . $purchaseReceipt->id . ')',
                    'notes' => 'Payment against goods actually received, not original PO estimate.',
                    'metadata' => [
                        'purchase_receipt_id' => $purchaseReceipt->id,
                        'receipt_payable' => $currentReceiptPayable,
                    ],
                ]);

                $purchaseOrder->total_paid = ($purchaseOrder->total_paid ?? 0) + $paymentAmount;
            }

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
    private function logBatchReceipt($batchItem, $variant, $purchaseOrder, $purchaseReceipt, $quantityReceived, $actualUnitCost, $user, $tenantId)
    {
        try {
            $quantityReceived = (int) $quantityReceived;
            
            if ($quantityReceived <= 0) {
                \Log::warning('[Batch Receipt] Skipped - quantity is zero or negative', [
                    'batch_id' => $batchItem->id ?? null,
                    'quantity' => $quantityReceived
                ]);
                return;
            }

            $unitCost = (float) ($actualUnitCost ?? $batchItem->unit_cost ?? 0);
            $totalCost = $unitCost * $quantityReceived;

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
                    'actual_unit_cost' => $unitCost,
                ],
            ]);

        } catch (\Exception $e) {
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
