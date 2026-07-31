<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Models\{ Invoice, Order, OrderPayment, ProductVariant, InventoryAdjustments,
                    InventoryTransactions, SingleShopInventoryLog, PaymentMethod, Currency, 
                    InvoicePayment, InventoryItems };
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{ Auth, DB, Log, Mail };
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('view invoice')) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.not_authorized'),
                ]);
            }
            abort(403);
        }

        $query = Invoice::with(['order', 'customer'])
            ->where('tenant_id', $tenantId)
            ->latest();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhere('billing_name', 'like', "%{$search}%")
                  ->orWhere('billing_email', 'like', "%{$search}%")
                  ->orWhere('billing_phone', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Update overdue invoices using the scope
        Invoice::where('tenant_id', $tenantId)
            ->outstanding()
            ->whereDate('due_date', '<', now())
            ->update(['status' => Invoice::STATUS_OVERDUE]);

        $perPage = $request->input('per_page', 15);
        $allowedPerPage = [15, 25, 50, 100];
        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 15;
        }

        $invoices = $query->paginate($perPage)->withQueryString();

        $bladeToReload = $request->query('bladeFileToReload');

        if ($request->ajax() && $bladeToReload === 'reloadInvoiceComponent') {
            return view('orders.invoice.component', [
                'invoices' => $invoices,
            ])->render();
        }

        return view('orders.invoice-index', [
            'invoices' => $invoices,
        ]);
    }

    public function create()
    {
        return view('orders.invoice.create');
    }

    public function store(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => __('payments.use_pos_to_create_invoice'),
        ], 501);
    }

    public function show($id)
    {
        $user    = Auth::user();
        $invoice = Invoice::with(['order.orderItems', 'customer', 'sends', 'payments.paymentMethod', 'creator'])
            ->where('tenant_id', $user->tenant_id)
            ->findOrFail($id);

        return view('orders.invoice.show', compact('invoice'));
    }

    public function edit($id)
    {
        $user    = Auth::user();
        $invoice = Invoice::where('tenant_id', $user->tenant_id)->findOrFail($id);

        return response()->json([
            'success' => true,
            'invoice' => $invoice,
        ]);
    }

    public function update(Request $request, $id)
    {
        $user    = Auth::user();
        $invoice = Invoice::where('tenant_id', $user->tenant_id)->findOrFail($id);

        if ($invoice->isPaid() || $invoice->isVoid()) {
            return response()->json([
                'success' => false,
                'message' => __('payments.invoice_locked'),
            ], 422);
        }

        $validated = $request->validate([
            'billing_email'   => 'nullable|email',
            'billing_phone'   => 'nullable|string|max:30',
            'billing_address' => 'nullable|string|max:500',
            'due_date'        => 'nullable|date',
            'terms'           => 'nullable|string|max:1000',
            'notes'           => 'nullable|string|max:1000',
        ]);

        $invoice->update($validated);

        return response()->json([
            'success' => true,
            'message' => __('payments.invoice_updated'),
        ]);
    }

    public function destroy($id)
    {
        $user    = Auth::user();
        $invoice = Invoice::where('tenant_id', $user->tenant_id)->findOrFail($id);

        if (! $invoice->isDraft()) {
            return response()->json([
                'success' => false,
                'message' => __('payments.only_draft_invoices_deletable'),
            ], 422);
        }

        DB::transaction(function () use ($invoice) {
            $invoice->order()->update(['status' => 'cancelled']);
            $invoice->delete();
        });

        return response()->json([
            'success' => true,
            'reload' => false,
            'componentId' => 'reloadInvoiceComponent',
            'refresh' => true,
            'message' => __('auth._deleted'),
            'redirect' => route('invoices.index'),
        ]);
    }

    /**
     * THE COMMIT POINT. First send: stock leaves, status flips to 'sent'.
     * A resend after that never deducts stock again.
     *
     * channel = 'email'    → needs a destination address. Uses billing_email
     *                        on file, or the 'email' field if the cashier
     *                        typed/overrode one — and persists that override
     *                        onto the invoice so it's there next time.
     * channel = 'download' → no address needed. Still marks the invoice sent
     *                        and moves stock (goods left the store either
     *                        way) — just hands back a PDF url for the
     *                        cashier to send manually (WhatsApp, print, etc).
     */
    /**
     * Send invoice via email or download
     */
    public function send(Request $request, $id)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        $invoice = Invoice::with('order.orderItems')
            ->where('tenant_id', $tenantId)
            ->findOrFail($id);

        if ($invoice->isVoid() || $invoice->isPaid()) {
            return response()->json([
                'success' => false,
                'message' => __('payments.invoice_cannot_be_sent'),
            ], 422);
        }

        $validated = $request->validate([
            'channel' => 'nullable|in:email,print,sms,whatsapp',
            'email'   => 'nullable|email',
            'subject' => 'nullable|string|max:255',
            'message' => 'nullable|string',
        ]);

        $channel = $validated['channel'] ?? 'email';

        // For email channel, validate email
        if ($channel === 'email') {
            $emailToUse = $validated['email'] ?? $invoice->billing_email;
            
            // Log the email being used
            Log::info('Email channel selected', [
                'invoice_id' => $invoice->id,
                'email_from_request' => $validated['email'] ?? null,
                'email_from_invoice' => $invoice->billing_email,
                'email_to_use' => $emailToUse
            ]);
            
            if (!$emailToUse) {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.customer_email_required'),
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            $isFirstSend = $invoice->isDraft();

            // Update email if changed
            if ($channel === 'email' && !empty($validated['email']) && $validated['email'] !== $invoice->billing_email) {
                $invoice->billing_email = $validated['email'];
            }

            // First send - reduce stock
            if ($isFirstSend) {
                $isSingleShop = tenant_is_single_shop($tenantId);

                foreach ($invoice->order->orderItems as $item) {
                    $variant = ProductVariant::find($item->variant_id);
                    if (!$variant) continue;

                    if ($isSingleShop) {
                        $this->reduceSingleShopStockForInvoice($variant, $item, $invoice->order);
                    } else {
                        $this->reduceMultiShopStockForInvoice($variant, $item, $invoice->order, $user);
                    }
                }

                $invoice->status = Invoice::STATUS_SENT;
                $invoice->sent_at = now();
                $invoice->save();
            }

            // ─── SEND EMAIL ──────────────────────────────────────────────
            if ($channel === 'email') {
                $emailToUse = $validated['email'] ?? $invoice->billing_email;
                $subject = $validated['subject'] ?? __('payments.invoice_subject', [
                    'number' => $invoice->invoice_number,
                    'app_name' => config('app.name'),
                ]);
                $customMessage = $validated['message'] ?? null;

                // Log before sending
                Log::info('Attempting to send invoice email', [
                    'invoice_id' => $invoice->id,
                    'to' => $emailToUse,
                    'subject' => $subject,
                ]);

                try {
                    // USE send() NOT queue() - to send immediately
                    Mail::to($emailToUse)
                        ->send(new \App\Mail\InvoiceMail($invoice, $subject, $customMessage));
                    
                    Log::info('Invoice email sent successfully', [
                        'invoice_id' => $invoice->id,
                        'to' => $emailToUse
                    ]);

                    // Create send record
                    $invoice->sends()->create([
                        'channel' => $channel,
                        'recipient' => $invoice->billing_email,
                        'status' => 'sent',
                        'provider' => config('mail.default'),
                        'sent_by' => $user->id,
                        'sent_at' => now(),
                    ]);

                } catch (\Exception $e) {
                    Log::error('Failed to send invoice email: ' . $e->getMessage(), [
                        'invoice_id' => $invoice->id,
                        'to' => $emailToUse,
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    // Record the failure
                    $invoice->sends()->create([
                        'channel' => $channel,
                        'recipient' => $emailToUse,
                        'status' => 'failed',
                        'provider' => config('mail.default'),
                        'error_message' => $e->getMessage(),
                        'sent_by' => $user->id,
                    ]);
                    
                    throw $e;
                }
            }

            // ─── DOWNLOAD ──────────────────────────────────────────────────
            if ($channel === 'download') {
                $invoice->sends()->create([
                    'channel' => $channel,
                    'recipient' => $invoice->billing_phone ?? null,
                    'status' => 'delivered',
                    'sent_by' => $user->id,
                    'sent_at' => now(),
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $channel === 'email'
                    ? ($isFirstSend
                        ? __('payments.invoice_sent_and_stock_reduced')
                        : __('payments.invoice_resent'))
                    : __('payments.invoice_ready_for_download'),
                'invoice_id' => $invoice->id,
                'status' => $invoice->status,
                'download_url' => $channel === 'download' ? route('invoices.pdf', $invoice->id) : null,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Invoice send failed: ' . $e->getMessage(), [
                'invoice_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => __('payments.invoice_send_failed') . ': ' . $e->getMessage(),
            ], 500);
        }
    }

  
    /**
     * Quick, no-money status changes only — 'paid' and 'partially_paid' are
     * rejected here on purpose. Those go through recordPayment(), which is
     * the endpoint that also credits a payment method.
     */
    public function updateStatus(Request $request, $id)
    {
        $user    = Auth::user();
        $invoice = Invoice::where('tenant_id', $user->tenant_id)->findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:sent,viewed,overdue,cancelled',
        ]);

        if ($invoice->isPaid() || $invoice->isVoid()) {
            return response()->json([
                'success' => false,
                'message' => __('payments.invoice_locked'),
            ], 422);
        }

        $invoice->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => __('payments.invoice_status_updated'),
            'status'  => $invoice->status,
        ]);
    }

    /**
     * The manual-reconciliation entry point. payment_method_id is now
     * REQUIRED — every reconciled payment has to land in a real account,
     * exactly like a POS sale, so it shows up correctly in financial
     * reports. This is also the exact code path a payment gateway webhook
     * should call later (recorded_via='webhook' instead of 'manual').
     */
    /**
     * Record payment for invoice
     */
    public function recordPayment(Request $request, $id)
    {
        $user = Auth::user();
        $invoice = Invoice::with('order')->where('tenant_id', $user->tenant_id)->findOrFail($id);

        if ($invoice->isPaid()) {
            return response()->json([
                'success' => false,
                'message' => __('payments.invoice_already_paid'),
            ], 422);
        }

        if ($invoice->isVoid()) {
            return response()->json([
                'success' => false,
                'message' => __('payments.invoice_voided'),
            ], 422);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'transaction_id' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $paymentMethod = PaymentMethod::findForTenant($validated['payment_method_id'], $user->tenant_id);
        if (!$paymentMethod) {
            return response()->json([
                'success' => false,
                'message' => __('payments.payment_method_not_found'),
            ], 422);
        }

        $amount = (float) $validated['amount'];

        if ($amount > $invoice->balance_due) {
            return response()->json([
                'success' => false,
                'message' => __('payments.amount_exceeds_balance'),
            ], 422);
        }

        DB::beginTransaction();
        try {
            // 1. Record deposit - this is all we need
            $this->recordDeposit($invoice, $paymentMethod, $amount);

            // 2. Create invoice payment record
            $invoicePayment = InvoicePayment::create([
                'invoice_id' => $invoice->id,
                'payment_method_id' => $paymentMethod->id,
                'processed_by' => $user->id,
                'amount' => $amount,
                'transaction_id' => $validated['transaction_id'] ?? (string) Str::uuid(),
                'status' => 'completed',
                'type' => $amount >= $invoice->balance_due ? 'full' : 'partial',
                'payment_date' => now(),
                'notes' => $validated['notes'] ?? 'Manual payment',
                'currency_code' => $invoice->currency ?? 'UGX',
            ]);

            // 3. Update payment method balance
            $paymentMethod->current_balance += $amount;
            $paymentMethod->save();

            // 4. Update invoice
            $newAmountPaid = $invoice->amount_paid + $amount;
            $newBalance = max(0, $invoice->total - $newAmountPaid);
            
            $invoice->update([
                'amount_paid' => $newAmountPaid,
                'balance_due' => $newBalance,
                'status' => $newBalance <= 0 ? 'paid' : 'partially_paid',
                'paid_at' => $newBalance <= 0 ? now() : null,
            ]);

            // 5. Update order if exists
            if ($invoice->order) {
                $order = $invoice->order;
                $orderNewPaid = $order->paid_amount + $amount;
                $orderNewBalance = max(0, $order->total - $orderNewPaid);
                
                $order->update([
                    'paid_amount' => $orderNewPaid,
                    'balance_due' => $orderNewBalance,
                    'status' => $orderNewBalance <= 0 ? 'completed' : $order->status,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('payments.payment_recorded'),
                'status' => $invoice->status,
                'amount_paid' => $invoice->amount_paid,
                'balance_due' => $invoice->balance_due,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment failed: ' . $e->getMessage(), ['invoice_id' => $id]);
            
            return response()->json([
                'success' => false,
                'message' => __('payments.payment_record_failed'),
            ], 500);
        }
    }

    /**
     * Record deposit - service handles everything
     */
    private function recordDeposit($invoice, $paymentMethod, $amount)
    {
        $transactionData = [
            'tenant_id' => $invoice->tenant_id,
            'user_id' => auth()->id(),
            'payment_method_id' => $paymentMethod->id,
            'transaction_type' => 'DEPOSIT',
            'transaction_category' => 'INVOICE',
            'amount' => $amount,
            'currency_id' => $paymentMethod->currency_id ?? Currency::default()->id,
            'reference_table' => 'invoices',
            'reference_id' => $invoice->id,
            'description' => 'Invoice Payment - ' . $invoice->invoice_number,
            'notes' => 'Invoice payment recorded',
            'metadata' => [
                'invoice_number' => $invoice->invoice_number,
                'customer_name' => $invoice->billing_name,
            ],
        ];

        app('payment-transaction')->recordTransaction($transactionData);
    }



    public function void(Request $request, $id)
    {
        $user = Auth::user();
        $invoice = Invoice::with('order.orderItems')->where('tenant_id', $user->tenant_id)->findOrFail($id);

        if ($invoice->isPaid()) {
            return response()->json([
                'success' => false,
                'message' => __('payments.cannot_void_paid_invoice'),
            ], 422);
        }

        if ($invoice->amount_paid > 0) {
            return response()->json([
                'success' => false,
                'message' => __('payments.cannot_void_partially_paid_invoice'),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $wasSent = in_array($invoice->status, [
                Invoice::STATUS_SENT, Invoice::STATUS_VIEWED, Invoice::STATUS_OVERDUE,
            ]);

            if ($wasSent) {
                $isSingleShop = tenant_is_single_shop($invoice->tenant_id);

                foreach ($invoice->order->orderItems as $item) {
                    $variant = ProductVariant::find($item->variant_id);
                    if (!$variant) continue;

                    if ($isSingleShop) {
                        $this->restockSingleShopForVoid($variant, $item, $invoice->order);
                    } else {
                        $this->restockMultiShopForVoid($variant, $item, $invoice->order, $user);
                    }
                }
            }

            // Save void reason if provided
            $notes = $invoice->notes;
            if ($request->filled('reason')) {
                $notes = ($notes ? $notes . "\n" : '') . "Void reason: " . $request->reason;
            }

            $invoice->update([
                'status' => Invoice::STATUS_VOID,
                'voided_at' => now(),
                'voided_by' => $user->id,
                'notes' => $notes,
            ]);

            if ($invoice->order) {
                $invoice->order->update(['status' => 'cancelled']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'reload' => false,
                'componentId' => 'reloadInvoiceComponent',
                'refresh' => true,
                'message' => __('payments.invoice_voided'),
                'redirect' => route('invoices.index'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Invoice void failed: ' . $e->getMessage(), ['invoice_id' => $id]);
            return response()->json([
                'success' => false,
                'message' => __('payments.invoice_void_failed') . ': ' . $e->getMessage(),
            ], 500);
        }
    }



    public function generatePdf($id)
    {
        $user    = Auth::user();
        $invoice = Invoice::with(['order.orderItems', 'customer'])
            ->where('tenant_id', $user->tenant_id)
            ->findOrFail($id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('orders.invoice.pdf', compact('invoice'));

        return $pdf->stream($invoice->invoice_number . '.pdf');
    }

    // ── Financial ledger helper ──────────────────────────────────────────

    /**
     * Mirrors POSController::recordOrderPaymentTransaction() but for
     * invoice payments — same service, different reference table, so
     * reports can tell a POS sale apart from a reconciled invoice payment.
     */
    private function recordInvoicePaymentTransaction($invoice, $paymentMethod, $amount, $paymentDetails = [])
    {
        try {
            $transactionData = [
                'tenant_id'            => $invoice->tenant_id,
                'user_id'              => auth()->id(),
                'payment_method_id'    => $paymentMethod->id,
                'transaction_type'     => 'DEPOSIT',
                'transaction_category' => 'INVOICE',
                'amount'               => $amount,
                'currency_id'          => $paymentMethod->currency_id ?? Currency::default()->id,
                'reference_table'      => 'invoices',
                'reference_id'         => $invoice->id,
                'description'          => 'Invoice Payment - Invoice #' . $invoice->invoice_number,
                'notes'                => 'Manual reconciliation of invoice payment',
                'metadata' => [
                    'invoice_number'     => $invoice->invoice_number,
                    'order_number'       => $invoice->order->order_number ?? null,
                    'customer_name'      => $invoice->billing_name,
                    'payment_type'       => $paymentMethod->type,
                    'payment_details'    => $paymentDetails,
                    'transaction_nature' => 'INVOICE_PAYMENT',
                ],
            ];

            app('payment-transaction')->recordTransaction($transactionData);

        } catch (\Exception $e) {
            Log::error('Failed to record invoice payment transaction: ' . $e->getMessage());
            throw $e;
        }
    }

    // ── Stock movement helpers ──────────────────────────────────────────

    private function reduceSingleShopStockForInvoice($variant, $item, $order)
    {
        $beforeQty = $variant->overal_quantity_at_hand;
        $afterQty  = $beforeQty - $item->quantity;

        $variant->update(['overal_quantity_at_hand' => $afterQty]);

        SingleShopInventoryLog::create([
            'variant_id'      => $variant->id,
            'order_id'        => $order->id,
            'tenant_id'       => $order->tenant_id,
            'created_by'      => auth()->id(),
            'quantity_before' => $beforeQty,
            'quantity_after'  => $afterQty,
            'quantity_change' => -$item->quantity,
            'reason'          => 'invoice_sent',
            'notes'           => 'Invoice sent - Order #' . $order->order_number,
            'source'          => 'invoice',
            'metadata' => [
                'item_name'     => $item->item_name,
                'unit_price'    => $item->unit_price,
                'customer_name' => $order->customer_name,
                'location_id'   => $order->location_id,
                'department_id' => $order->department_id,
            ],
        ]);
    }

    private function reduceMultiShopStockForInvoice($variant, $item, $order, $user)
    {
        // ✅ Get inventory_id from the stored inventory_data
        $inventoryData = json_decode($item->inventory_data, true);
        $inventoryId = $inventoryData['inventory_id'] ?? null;
        $departmentId = $inventoryData['department_id'] ?? $user->department_id ?? 1;
        $locationId = $inventoryData['location_id'] ?? $user->location_id ?? 1;

        // ✅ Try to find by inventory_id first
        if ($inventoryId) {
            $inventory = InventoryItems::find($inventoryId);
        }

        // ✅ Fallback: query by location and department
        if (!$inventory) {
            $inventory = $variant->inventory()
                ->where('location_id', $locationId)
                ->where('department_id', $departmentId)
                ->first();
        }

        if (!$inventory) {
            Log::warning("No inventory found for variant {$variant->id} while sending invoice", [
                'variant_id' => $variant->id,
                'order_id'   => $order->id,
                'inventory_id' => $inventoryId,
                'department_id' => $departmentId,
                'location_id' => $locationId,
            ]);
            return;
        }

        $beforeQty = $inventory->quantity_allocated;
        $afterQty  = $beforeQty - $item->quantity;

        $inventory->update(['quantity_allocated' => $afterQty]);

        InventoryAdjustments::create([
            'quantity_before' => $beforeQty,
            'quantity_after'  => $afterQty,
            'reason'          => 'invoice_sent and items left inventory',
            'notes'           => 'Stock reduced — invoice sent for order #' . $order->order_number,
            'inventory_id'    => $inventory->id,
            'created_by'      => auth()->id(),
            'tenant_id'       => $order->tenant_id,
        ]);

        InventoryTransactions::create([
            'quantity'       => -$item->quantity,
            'reference_id'   => $order->id,
            'reference_type' => 'order',
            'type'           => 'sale',
            'notes'          => 'Issued ' . $item->quantity . ' units of ' . $variant->sku . ' via invoice',
            'inventory_id'   => $inventory->id,
            'created_by'     => auth()->id(),
            'tenant_id'      => $order->tenant_id,
        ]);
    }

    private function restockSingleShopForVoid($variant, $item, $order)
    {
        $beforeQty = $variant->overal_quantity_at_hand;
        $afterQty  = $beforeQty + $item->quantity;

        $variant->update(['overal_quantity_at_hand' => $afterQty]);

        SingleShopInventoryLog::create([
            'variant_id'      => $variant->id,
            'order_id'        => $order->id,
            'tenant_id'       => $order->tenant_id,
            'created_by'      => auth()->id(),
            'quantity_before' => $beforeQty,
            'quantity_after'  => $afterQty,
            'quantity_change' => $item->quantity,
            'reason'          => 'invoice_voided',
            'notes'           => 'Invoice voided, stock restored - Order #' . $order->order_number,
            'source'          => 'invoice',
            'metadata' => [
                'item_name'  => $item->item_name,
                'unit_price' => $item->unit_price,
            ],
        ]);
    }

    private function restockMultiShopForVoid($variant, $item, $order, $user)
    {
        // ✅ Get inventory_id from the stored inventory_data
        $inventoryData = json_decode($item->inventory_data, true);
        $inventoryId = $inventoryData['inventory_id'] ?? null;
        $departmentId = $inventoryData['department_id'] ?? $user->department_id ?? 1;
        $locationId = $inventoryData['location_id'] ?? $user->location_id ?? 1;

        // ✅ Try to find by inventory_id first
        if ($inventoryId) {
            $inventory = InventoryItems::find($inventoryId);
        }

        // ✅ Fallback: query by location and department
        if (!$inventory) {
            $inventory = $variant->inventory()
                ->where('location_id', $locationId)
                ->where('department_id', $departmentId)
                ->first();
        }

        if (!$inventory) {
            Log::warning("No inventory found for variant {$variant->id} while voiding invoice", [
                'variant_id' => $variant->id,
                'order_id'   => $order->id,
                'inventory_id' => $inventoryId,
                'department_id' => $departmentId,
                'location_id' => $locationId,
            ]);
            return;
        }

        $beforeQty = $inventory->quantity_allocated;
        $afterQty  = $beforeQty + $item->quantity;

        $inventory->update(['quantity_allocated' => $afterQty]);

        InventoryAdjustments::create([
            'quantity_before' => $beforeQty,
            'quantity_after'  => $afterQty,
            'reason'          => 'invoice_voided and items returned',
            'notes'           => 'Stock restored — invoice voided for order #' . $order->order_number,
            'inventory_id'    => $inventory->id,
            'created_by'      => auth()->id(),
            'tenant_id'       => $order->tenant_id,
        ]);

        InventoryTransactions::create([
            'quantity'       => $item->quantity,
            'reference_id'   => $order->id,
            'reference_type' => 'order',
            'type'           => 'return',
            'notes'          => 'Restocked ' . $item->quantity . ' units of ' . $variant->sku . ' — invoice voided',
            'inventory_id'   => $inventory->id,
            'created_by'     => auth()->id(),
            'tenant_id'      => $order->tenant_id,
        ]);
    }



    // Public payment method coming soon 


    /**
     * Display public invoice
     */
    public function showPublicInvoice($token)
    {
        $invoice = Invoice::with(['order.orderItems', 'customer'])
            ->where('public_token', $token)
            ->firstOrFail();
            
        // Mark as viewed if not already
        if ($invoice->status === 'sent') {
            $invoice->markAsViewed();
        }
        
        return view('orders.invoice.show', compact('invoice'));
    }
    
    /**
     * Show payment page for public invoice
     */
    public function pay($token)
    {
        $invoice = Invoice::with(['order.orderItems', 'customer'])
            ->where('public_token', $token)
            ->firstOrFail();
            
        if ($invoice->isPaid()) {
            return redirect()->route('public.invoice.show', $token)
                ->with('message', 'This invoice has already been paid.');
        }
        
        return view('public.invoice.pay', compact('invoice'));
    }
    
    /**
     * Process payment for public invoice
     */
    public function processPayment(Request $request, $token)
    {
        $invoice = Invoice::where('public_token', $token)->firstOrFail();
        
        // Validate payment
        $request->validate([
            'payment_method' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
        ]);
        
        // Process payment (integrate with your payment gateway)
        // ...
        
        return response()->json([
            'success' => true,
            'message' => 'Payment processed successfully.',
        ]);
    }

    /**
     * Apply flat discount to invoice
     */
    public function applyDiscount(Request $request, $id)
    {
        $user = Auth::user();
        $invoice = Invoice::where('tenant_id', $user->tenant_id)->findOrFail($id);
        
        if ($invoice->isPaid() || $invoice->isVoid() || $invoice->isSent()) {
            return response()->json([
                'success' => false,
                'message' => __('payments.cannot_discount_sent_invoice'),
            ], 422);
        }
        
        $validated = $request->validate([
            'discount_amount' => 'required|numeric|min:0',
            'discount_notes' => 'nullable|string|max:500',
        ]);
        
        $discountAmount = (float) $validated['discount_amount'];
        
        if ($discountAmount <= 0) {
            return response()->json([
                'success' => false,
                'message' => __('payments.discount_must_be_positive'),
            ], 422);
        }
        
        // Ensure discount doesn't exceed subtotal
        if ($discountAmount > $invoice->subtotal) {
            return response()->json([
                'success' => false,
                'message' => __('payments.discount_exceeds_subtotal'),
            ], 422);
        }
        
        DB::beginTransaction();
        try {
            $invoice->applyDiscount($discountAmount, $validated['discount_notes'] ?? null);
            
            DB::commit();
            
            // ✅ Convert back to display format for response
            return response()->json([
                'success' => true,
                'message' => __('payments.discount_applied', ['amount' => number_format($discountAmount, 2)]),
                'invoice' => $invoice->fresh(),
                'discount_amount' => $discountAmount,
                'new_total' => $invoice->total,
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Discount apply failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('payments.discount_apply_failed'),
            ], 500);
        }
    }

    /**
     * Remove discount from invoice
     */
    public function removeDiscount($id)
    {
        $user = Auth::user();
        $invoice = Invoice::where('tenant_id', $user->tenant_id)->findOrFail($id);
        
        if ($invoice->isPaid() || $invoice->isVoid() || $invoice->isSent()) {
            return response()->json([
                'success' => false,
                'message' => __('payments.cannot_modify_sent_invoice'),
            ], 422);
        }
        
        if ($invoice->discount_total <= 0) {
            return response()->json([
                'success' => false,
                'message' => __('payments.no_discount_to_remove'),
            ], 422);
        }
        
        DB::beginTransaction();
        try {
            $invoice->removeDiscount();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => __('payments.discount_removed'),
                'invoice' => $invoice->fresh(),
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Discount remove failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('payments.discount_remove_failed'),
            ], 500);
        }
    }

}