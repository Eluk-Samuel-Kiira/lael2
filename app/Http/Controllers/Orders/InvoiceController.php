<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Models\{ Invoice, Order, OrderPayment, ProductVariant, InventoryAdjustments,
                    InventoryTransactions, SingleShopInventoryLog, PaymentMethod, Currency, 
                    InvoicePayment, InventoryItems, PurchaseReceiptItem, BatchLog, SerialNumber };
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
    // public function send(Request $request, $id)
    // {
    //     $user = Auth::user();
    //     $tenantId = $user->tenant_id;

    //     $invoice = Invoice::with('order.orderItems')
    //         ->where('tenant_id', $tenantId)
    //         ->findOrFail($id);

    //     if ($invoice->isVoid() || $invoice->isPaid()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => __('payments.invoice_cannot_be_sent'),
    //         ], 422);
    //     }

    //     $validated = $request->validate([
    //         'channel' => 'nullable|in:email,print,sms,whatsapp',
    //         'email'   => 'nullable|email',
    //         'subject' => 'nullable|string|max:255',
    //         'message' => 'nullable|string',
    //     ]);

    //     $channel = $validated['channel'] ?? 'email';

    //     // For email channel, validate email
    //     if ($channel === 'email') {
    //         $emailToUse = $validated['email'] ?? $invoice->billing_email;
            
    //         // Log the email being used
    //         // Log::info('Email channel selected', [
    //         //     'invoice_id' => $invoice->id,
    //         //     'email_from_request' => $validated['email'] ?? null,
    //         //     'email_from_invoice' => $invoice->billing_email,
    //         //     'email_to_use' => $emailToUse
    //         // ]);
            
    //         if (!$emailToUse) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => __('payments.customer_email_required'),
    //             ], 422);
    //         }
    //     }

    //     DB::beginTransaction();
    //     try {
    //         $isFirstSend = $invoice->isDraft();

    //         // Update email if changed
    //         if ($channel === 'email' && !empty($validated['email']) && $validated['email'] !== $invoice->billing_email) {
    //             $invoice->billing_email = $validated['email'];
    //         }

    //         // First send - reduce stock
    //         if ($isFirstSend) {
    //             $isSingleShop = tenant_is_single_shop($tenantId);

    //             foreach ($invoice->order->orderItems as $item) {
    //                 $variant = ProductVariant::find($item->variant_id);
    //                 if (!$variant) continue;

    //                 if ($isSingleShop) {
    //                     $this->reduceSingleShopStockForInvoice($variant, $item, $invoice->order);
    //                 } else {
    //                     $this->reduceMultiShopStockForInvoice($variant, $item, $invoice->order, $user);
    //                 }
    //             }

    //             $invoice->status = Invoice::STATUS_SENT;
    //             $invoice->sent_at = now();
    //             $invoice->save();
    //         }

    //         // ─── SEND EMAIL ──────────────────────────────────────────────
    //         if ($channel === 'email') {
    //             $emailToUse = $validated['email'] ?? $invoice->billing_email;
    //             $subject = $validated['subject'] ?? __('payments.invoice_subject', [
    //                 'number' => $invoice->invoice_number,
    //                 'app_name' => config('app.name'),
    //             ]);
    //             $customMessage = $validated['message'] ?? null;

    //             // Log before sending
    //             Log::info('Attempting to send invoice email', [
    //                 'invoice_id' => $invoice->id,
    //                 'to' => $emailToUse,
    //                 'subject' => $subject,
    //             ]);

    //             try {
    //                 // USE send() NOT queue() - to send immediately
    //                 Mail::to($emailToUse)
    //                     ->send(new \App\Mail\InvoiceMail($invoice, $subject, $customMessage));
                    
    //                 Log::info('Invoice email sent successfully', [
    //                     'invoice_id' => $invoice->id,
    //                     'to' => $emailToUse
    //                 ]);

    //                 // Create send record
    //                 $invoice->sends()->create([
    //                     'channel' => $channel,
    //                     'recipient' => $invoice->billing_email,
    //                     'status' => 'sent',
    //                     'provider' => config('mail.default'),
    //                     'sent_by' => $user->id,
    //                     'sent_at' => now(),
    //                 ]);

    //             } catch (\Exception $e) {
    //                 Log::error('Failed to send invoice email: ' . $e->getMessage(), [
    //                     'invoice_id' => $invoice->id,
    //                     'to' => $emailToUse,
    //                     'trace' => $e->getTraceAsString()
    //                 ]);
                    
    //                 // Record the failure
    //                 $invoice->sends()->create([
    //                     'channel' => $channel,
    //                     'recipient' => $emailToUse,
    //                     'status' => 'failed',
    //                     'provider' => config('mail.default'),
    //                     'error_message' => $e->getMessage(),
    //                     'sent_by' => $user->id,
    //                 ]);
                    
    //                 throw $e;
    //             }
    //         }

    //         // ─── DOWNLOAD ──────────────────────────────────────────────────
    //         if ($channel === 'download') {
    //             $invoice->sends()->create([
    //                 'channel' => $channel,
    //                 'recipient' => $invoice->billing_phone ?? null,
    //                 'status' => 'delivered',
    //                 'sent_by' => $user->id,
    //                 'sent_at' => now(),
    //             ]);
    //         }

    //         DB::commit();

    //         return response()->json([
    //             'success' => true,
    //             'message' => $channel === 'email'
    //                 ? ($isFirstSend
    //                     ? __('payments.invoice_sent_and_stock_reduced')
    //                     : __('payments.invoice_resent'))
    //                 : __('payments.invoice_ready_for_download'),
    //             'invoice_id' => $invoice->id,
    //             'status' => $invoice->status,
    //             'download_url' => $channel === 'download' ? route('invoices.pdf', $invoice->id) : null,
    //         ]);

    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error('Invoice send failed: ' . $e->getMessage(), [
    //             'invoice_id' => $id,
    //             'trace' => $e->getTraceAsString()
    //         ]);
            
    //         return response()->json([
    //             'success' => false,
    //             'message' => __('payments.invoice_send_failed') . ': ' . $e->getMessage(),
    //         ], 500);
    //     }
    // }

  
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
            // ─── Get Location and Department Names ────────────────────────
            $locationName = $invoice->order?->location?->name ?? 'Unknown Location';
            $departmentName = $invoice->order?->department?->name ?? 'Unknown Department';
            $orderNumber = $invoice->order?->order_number ?? $invoice->invoice_number;

            // ─── Build Readable Notes ──────────────────────────────────────
            $paymentNote = 'Payment of ' . number_format($amount, 2) . ' received for invoice #' . $invoice->invoice_number 
                            . ' from ' . $locationName . ' (' . $departmentName . ')';

            // 1. Record deposit
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
                'notes' => $validated['notes'] ?? $paymentNote,
                'currency_code' => $invoice->currency ?? 'UGX',
            ]);

            // 3. Update payment method balance
            $paymentMethod->current_balance += $amount;
            $paymentMethod->save();

            // 4. Update invoice
            $newAmountPaid = $invoice->amount_paid + $amount;
            $newBalance = max(0, $invoice->total - $newAmountPaid);
            $isFullyPaid = $newBalance <= 0;
            
            $invoice->update([
                'amount_paid' => $newAmountPaid,
                'balance_due' => $newBalance,
                'status' => $isFullyPaid ? 'paid' : 'partially_paid',
                'paid_at' => $isFullyPaid ? now() : null,
            ]);

            // 5. Update order if exists
            if ($invoice->order) {
                $order = $invoice->order;
                $orderNewPaid = $order->paid_amount + $amount;
                $orderNewBalance = max(0, $order->total - $orderNewPaid);
                $orderIsFullyPaid = $orderNewBalance <= 0;
                
                $order->update([
                    'paid_amount' => $orderNewPaid,
                    'balance_due' => $orderNewBalance,
                    // ✅ If fully paid, mark as completed
                    'status' => $orderIsFullyPaid ? 'completed' : ($order->status ?: 'confirmed'),
                    'completed_at' => $orderIsFullyPaid ? now() : $order->completed_at,
                ]);

                // ✅ If order is now completed, also update any pending inventory
                if ($orderIsFullyPaid && $order->status !== 'completed') {
                    Log::info('Order marked as completed after full payment', [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'total_paid' => $orderNewPaid,
                        'total' => $order->total,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('payments.payment_recorded'),
                'status' => $invoice->status,
                'amount_paid' => $invoice->amount_paid,
                'balance_due' => $invoice->balance_due,
                'order_status' => $invoice->order?->status ?? null,
                'order_completed' => $invoice->order?->status === 'completed',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment failed: ' . $e->getMessage(), [
                'invoice_id' => $id,
                'tenant_id' => $user->tenant_id,
                'user_id' => $user->id,
            ]);
            
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

        // ─── Get Location and Department Names ────────────────────────
        $locationName = $inventory->itemLocation ? $inventory->itemLocation->name : 'Unknown Location';
        $departmentName = $inventory->departmentItem ? $inventory->departmentItem->name : 'Unknown Department';
        $variantName = $variant->name ?? 'Unknown Variant';
        $variantSku = $variant->sku ?? 'N/A';

        // ─── Build Readable Notes ──────────────────────────────────────
        $adjustmentNote = 'Stock reduced — ' . $item->quantity . ' unit(s) of "' . $variantName . '" (' . $variantSku . ') removed from ' 
                        . $locationName . ' (' . $departmentName . ') due to invoice sent for order #' . $order->order_number;

        $transactionNote = 'Issued ' . $item->quantity . ' unit(s) of "' . $variantName . '" (' . $variantSku . ') from ' 
                        . $locationName . ' (' . $departmentName . ') via invoice for order #' . $order->order_number;

        $beforeQty = $inventory->quantity_allocated;
        $afterQty  = $beforeQty - $item->quantity;

        $inventory->update(['quantity_allocated' => $afterQty]);

        // ✅ Record adjustment (audit trail)
        InventoryAdjustments::create([
            'quantity_before' => $beforeQty,
            'quantity_after'  => $afterQty,
            'reason'          => 'invoice_sent_and_items_left_inventory',
            'notes'           => $adjustmentNote,
            'inventory_id'    => $inventory->id,
            'created_by'      => auth()->id(),
            'tenant_id'       => $order->tenant_id,
        ]);

        // ✅ Record transaction
        InventoryTransactions::create([
            'quantity'       => -$item->quantity,
            'reference_id'   => $order->id,
            'reference_type' => 'order',
            'type'           => 'sale',
            'notes'          => $transactionNote,
            'inventory_id'   => $inventory->id,
            'created_by'     => auth()->id(),
            'tenant_id'      => $order->tenant_id,
        ]);
    }

    // private function restockSingleShopForVoid($variant, $item, $order)
    // {
    //     $beforeQty = $variant->overal_quantity_at_hand;
    //     $afterQty  = $beforeQty + $item->quantity;

    //     $variant->update(['overal_quantity_at_hand' => $afterQty]);

    //     SingleShopInventoryLog::create([
    //         'variant_id'      => $variant->id,
    //         'order_id'        => $order->id,
    //         'tenant_id'       => $order->tenant_id,
    //         'created_by'      => auth()->id(),
    //         'quantity_before' => $beforeQty,
    //         'quantity_after'  => $afterQty,
    //         'quantity_change' => $item->quantity,
    //         'reason'          => 'invoice_voided',
    //         'notes'           => 'Invoice voided, stock restored - Order #' . $order->order_number,
    //         'source'          => 'invoice',
    //         'metadata' => [
    //             'item_name'  => $item->item_name,
    //             'unit_price' => $item->unit_price,
    //         ],
    //     ]);
    // }

    // private function restockMultiShopForVoid($variant, $item, $order, $user)
    // {
    //     // ✅ Get inventory_id from the stored inventory_data
    //     $inventoryData = json_decode($item->inventory_data, true);
    //     $inventoryId = $inventoryData['inventory_id'] ?? null;
    //     $departmentId = $inventoryData['department_id'] ?? $user->department_id ?? 1;
    //     $locationId = $inventoryData['location_id'] ?? $user->location_id ?? 1;

    //     // ✅ Try to find by inventory_id first
    //     if ($inventoryId) {
    //         $inventory = InventoryItems::find($inventoryId);
    //     }

    //     // ✅ Fallback: query by location and department
    //     if (!$inventory) {
    //         $inventory = $variant->inventory()
    //             ->where('location_id', $locationId)
    //             ->where('department_id', $departmentId)
    //             ->first();
    //     }

    //     if (!$inventory) {
    //         Log::warning("No inventory found for variant {$variant->id} while voiding invoice", [
    //             'variant_id' => $variant->id,
    //             'order_id'   => $order->id,
    //             'inventory_id' => $inventoryId,
    //             'department_id' => $departmentId,
    //             'location_id' => $locationId,
    //         ]);
    //         return;
    //     }

    //     // ─── Get Location and Department Names ────────────────────────
    //     $locationName = $inventory->itemLocation ? $inventory->itemLocation->name : 'Unknown Location';
    //     $departmentName = $inventory->departmentItem ? $inventory->departmentItem->name : 'Unknown Department';
    //     $variantName = $variant->name ?? 'Unknown Variant';
    //     $variantSku = $variant->sku ?? 'N/A';

    //     // ─── Build Readable Notes ──────────────────────────────────────
    //     $adjustmentNote = 'Stock restored — ' . $item->quantity . ' unit(s) of "' . $variantName . '" (' . $variantSku . ') returned to ' 
    //                     . $locationName . ' (' . $departmentName . ') due to voided order #' . $order->order_number;

    //     $transactionNote = 'Restocked ' . $item->quantity . ' unit(s) of "' . $variantName . '" (' . $variantSku . ') — invoice voided for order #' . $order->order_number;

    //     $beforeQty = $inventory->quantity_allocated;
    //     $afterQty  = $beforeQty + $item->quantity;

    //     $inventory->update(['quantity_allocated' => $afterQty]);

    //     // ✅ Record adjustment (audit trail)
    //     InventoryAdjustments::create([
    //         'quantity_before' => $beforeQty,
    //         'quantity_after'  => $afterQty,
    //         'reason'          => 'invoice_voided_and_items_returned',
    //         'notes'           => $adjustmentNote,
    //         'inventory_id'    => $inventory->id,
    //         'created_by'      => auth()->id(),
    //         'tenant_id'       => $order->tenant_id,
    //     ]);

    //     // ✅ Record transaction
    //     InventoryTransactions::create([
    //         'quantity'       => $item->quantity,
    //         'reference_id'   => $order->id,
    //         'reference_type' => 'order',
    //         'type'           => 'return',
    //         'notes'          => $transactionNote,
    //         'inventory_id'   => $inventory->id,
    //         'created_by'     => auth()->id(),
    //         'tenant_id'      => $order->tenant_id,
    //     ]);
    // }




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
        $tenantId = $user->tenant_id;
        
        $invoice = Invoice::where('tenant_id', $tenantId)->findOrFail($id);
        
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
            // ─── Apply discount to Invoice ──────────────────────────────
            $invoice->applyDiscount($discountAmount, $validated['discount_notes'] ?? null);
            
            // ─── Update associated Order ─────────────────────────────────
            $order = Order::where('id', $invoice->order_id)
                        ->where('tenant_id', $tenantId)
                        ->first();
            
            if ($order) {
                // ✅ Update order with the same discount
                $order->discount_total = ($order->discount_total ?? 0) + $discountAmount;
                $order->total = $order->subtotal + $order->tax_total - $order->discount_total;
                $order->balance_due = $order->total - $order->paid_amount;
                $order->save();
                
                // ✅ Log the discount application on the order
                Log::info('Discount applied to order from invoice', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'discount_amount' => $discountAmount,
                    'new_order_total' => $order->total,
                    'applied_by' => $user->id,
                ]);
            } else {
                Log::warning('No associated order found for invoice discount', [
                    'invoice_id' => $invoice->id,
                    'order_id' => $invoice->order_id,
                ]);
            }
            
            DB::commit();
            
            // ✅ Convert back to display format for response
            return response()->json([
                'success' => true,
                'message' => __('payments.discount_applied', ['amount' => number_format($discountAmount, 2)]),
                'invoice' => $invoice->fresh(),
                'order' => $order ? $order->fresh() : null,
                'discount_amount' => $discountAmount,
                'new_invoice_total' => $invoice->total,
                'new_order_total' => $order ? $order->total : null,
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Discount apply failed: ' . $e->getMessage(), [
                'invoice_id' => $id,
                'tenant_id' => $tenantId,
                'user_id' => $user->id,
            ]);
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



    /**
     * Send invoice via email or download - with full inventory strategy support
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

        if ($channel === 'email') {
            $emailToUse = $validated['email'] ?? $invoice->billing_email;
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

            if ($channel === 'email' && !empty($validated['email']) && $validated['email'] !== $invoice->billing_email) {
                $invoice->billing_email = $validated['email'];
            }

            // ─── FIRST SEND: Reduce stock using strategy ──────────────────
            if ($isFirstSend) {
                $isSingleShop = tenant_is_single_shop($tenantId);

                foreach ($invoice->order->orderItems as $item) {
                    $variant = ProductVariant::find($item->variant_id);
                    if (!$variant) continue;

                    $product = $variant->product;
                    if (!$product) continue;

                    $strategy = $product->resolvedInventoryStrategy();

                    // ✅ Use the same strategy-based depletion as POS
                    if ($strategy === 'recipe') {
                        $this->depleteRecipeIngredientsForInvoice($variant, $item, $invoice->order);
                    } else {
                        $this->depleteInventoryByStrategyForInvoice($variant, $item, $invoice->order, $isSingleShop);
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

                try {
                    Mail::to($emailToUse)
                        ->send(new \App\Mail\InvoiceMail($invoice, $subject, $customMessage));
                    
                    Log::info('Invoice email sent successfully', [
                        'invoice_id' => $invoice->id,
                        'to' => $emailToUse
                    ]);

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
     * Deplete inventory by strategy for invoice
     */
    private function depleteInventoryByStrategyForInvoice($variant, $item, $order, $isSingleShop)
    {
        $product = $variant->product;
        if (!$product) {
            throw new \Exception("Product not found for variant '{$variant->name}'");
        }

        $strategy = $product->resolvedInventoryStrategy();

        Log::info('[Invoice] Depleting inventory by strategy', [
            'variant_id' => $variant->id,
            'variant_name' => $variant->name,
            'strategy' => $strategy,
            'quantity' => is_object($item) ? ($item->quantity ?? 0) : ($item['quantity'] ?? 0),
            'is_single_shop' => $isSingleShop,
            'order_id' => $order->id,
        ]);

        if ($isSingleShop) {
            switch ($strategy) {
                case 'quantity':
                    $this->depleteSingleQuantityForInvoice($variant, $item, $order);
                    break;
                case 'batch':
                    $this->depleteSingleBatchForInvoice($variant, $item, $order);
                    break;
                case 'serial':
                    $this->depleteSingleSerialForInvoice($variant, $item, $order);  // ✅ Uses serial_id
                    break;
                default:
                    throw new \Exception("Unknown strategy '{$strategy}' for single shop invoice");
            }
        } else {
            switch ($strategy) {
                case 'quantity':
                    $this->depleteMultiQuantityForInvoice($variant, $item, $order);
                    break;
                case 'batch':
                    $this->depleteMultiBatchForInvoice($variant, $item, $order);
                    break;
                case 'serial':
                    $this->depleteMultiSerialForInvoice($variant, $item, $order);  // ✅ Uses serial_id + location/department
                    break;
                default:
                    throw new \Exception("Unknown strategy '{$strategy}' for multi shop invoice");
            }
        }
    }

    // ─── SINGLE SHOP SERIAL ─────────────────────────────────────────────

    /**
     * Single Shop - Serial strategy
     * Depletes from: serial_numbers table (marks as SOLD)
     * ✅ Uses serial_id from order item
     */
    private function depleteSingleSerialForInvoice($variant, $item, $order)
    {
        // ✅ Get serial_id from the order item
        $serialId = is_object($item) ? ($item->serial_id ?? null) : ($item['serial_id'] ?? null);
        $serialNumber = is_object($item) ? ($item->serial_number ?? null) : ($item['serial_number'] ?? null);
        
        // ✅ Get quantity from item
        $quantity = is_object($item) ? ($item->quantity ?? 1) : ($item['quantity'] ?? 1);
        
        Log::info('[Invoice] Depleting Single Serial', [
            'variant_id' => $variant->id,
            'variant_name' => $variant->name,
            'serial_id' => $serialId,
            'serial_number' => $serialNumber,
            'quantity' => $quantity,
            'order_id' => $order->id
        ]);
        
        if ($serialId) {
            // ✅ Deplete SPECIFIC serial
            $serial = SerialNumber::where('id', $serialId)
                ->where('variant_id', $variant->id)
                ->where('status', SerialNumber::STATUS_AVAILABLE)
                ->where('tenant_id', $order->tenant_id)
                ->first();
                
            if (!$serial) {
                throw new \Exception("Serial number not found or already sold");
            }
            
            // ✅ Mark as SOLD (borrowed from POSController)
            $serial->update([
                'status' => SerialNumber::STATUS_SOLD,
                'order_id' => $order->id,
                'sold_at' => now(),
                'sold_by' => auth()->id(),
            ]);
            
            Log::info('[Invoice] Serial sold (Single Shop)', [
                'serial_id' => $serial->id,
                'serial_number' => $serial->serial_number,
                'order_id' => $order->id,
                'variant_id' => $variant->id,
            ]);
            
        } else {
            // ✅ Fallback: FIFO - get first available serial
            $serials = SerialNumber::where('variant_id', $variant->id)
                ->where('status', SerialNumber::STATUS_AVAILABLE)
                ->where('tenant_id', $order->tenant_id)
                ->limit($quantity)
                ->get();

            if ($serials->count() < $quantity) {
                throw new \Exception("Insufficient serial numbers for {$variant->name}. Available: {$serials->count()}, Required: {$quantity}");
            }

            foreach ($serials as $serial) {
                $serial->update([
                    'status' => SerialNumber::STATUS_SOLD,
                    'order_id' => $order->id,
                    'sold_at' => now(),
                    'sold_by' => auth()->id(),
                ]);
            }
            
            Log::info('[Invoice] Serial sold (FIFO - Single Shop)', [
                'variant' => $variant->name,
                'quantity' => $quantity,
                'serials' => $serials->pluck('serial_number')->toArray()
            ]);
        }

        // ✅ Update overall quantity (virtual, for reference only)
        $variant->overal_quantity_at_hand = max(0, ($variant->overal_quantity_at_hand ?? 0) - $quantity);
        $variant->save();
    }

    // ─── MULTI SHOP SERIAL ──────────────────────────────────────────────

    /**
     * Multi Shop - Serial strategy
     * Depletes from: serial_numbers table (filtered by location/department)
     * ✅ Uses serial_id from order item + location/department filtering
     */
    private function depleteMultiSerialForInvoice($variant, $item, $order)
    {
        $user = Auth::user();
        $tenantId = $order->tenant_id;

        // ✅ Get serial_id from the order item
        $serialId = is_object($item) ? ($item->serial_id ?? null) : ($item['serial_id'] ?? null);
        $serialNumber = is_object($item) ? ($item->serial_number ?? null) : ($item['serial_number'] ?? null);
        
        // ✅ Get quantity from item
        $quantity = is_object($item) ? ($item->quantity ?? 1) : ($item['quantity'] ?? 1);

        // ✅ Safely get inventory_data
        $inventoryData = [];
        if (is_object($item)) {
            if (property_exists($item, 'inventory_data') && !empty($item->inventory_data)) {
                $inventoryData = is_string($item->inventory_data) 
                    ? json_decode($item->inventory_data, true) 
                    : (array) $item->inventory_data;
            }
        } else if (is_array($item)) {
            if (isset($item['inventory_data']) && !empty($item['inventory_data'])) {
                $inventoryData = is_string($item['inventory_data']) 
                    ? json_decode($item['inventory_data'], true) 
                    : (array) $item['inventory_data'];
            }
        }
        
        $inventoryId = $inventoryData['inventory_id'] ?? null;
        $locationId = $inventoryData['location_id'] ?? $user->location_id ?? null;
        $departmentId = $inventoryData['department_id'] ?? $user->department_id ?? null;

        Log::info('[Invoice] Depleting Multi Serial', [
            'variant_id' => $variant->id,
            'variant_name' => $variant->name,
            'serial_id' => $serialId,
            'serial_number' => $serialNumber,
            'quantity' => $quantity,
            'location_id' => $locationId,
            'department_id' => $departmentId,
            'order_id' => $order->id
        ]);

        // Get inventory record (for stock tracking)
        $inventory = null;
        if ($inventoryId) {
            $inventory = InventoryItems::find($inventoryId);
        }

        if (!$inventory && $locationId && $departmentId) {
            $inventory = InventoryItems::where('variant_id', $variant->id)
                ->where('tenant_id', $tenantId)
                ->where('location_id', $locationId)
                ->where('department_id', $departmentId)
                ->first();
        }

        if (!$inventory) {
            throw new \Exception("No inventory found for {$variant->name} at this location/department");
        }

        if ($serialId) {
            // ✅ Deplete SPECIFIC serial (borrowed from POSController)
            $serial = SerialNumber::where('id', $serialId)
                ->where('variant_id', $variant->id)
                ->where('status', SerialNumber::STATUS_AVAILABLE)
                ->where('tenant_id', $tenantId)
                ->where('location_id', $locationId)
                ->where('department_id', $departmentId)
                ->first();
                
            if (!$serial) {
                throw new \Exception("Serial number not found or already sold at this location");
            }
            
            $serial->update([
                'status' => SerialNumber::STATUS_SOLD,
                'order_id' => $order->id,
                'sold_at' => now(),
                'sold_by' => auth()->id(),
            ]);
            
            Log::info('[Invoice] Serial sold (Multi Shop - Specific)', [
                'serial_id' => $serial->id,
                'serial_number' => $serial->serial_number,
                'location_id' => $locationId,
                'department_id' => $departmentId,
                'order_id' => $order->id,
            ]);
            
            $depletedQuantity = 1;
            
        } else {
            // ✅ Fallback: FIFO - get available serials at this location/department
            $serials = SerialNumber::where('variant_id', $variant->id)
                ->where('status', SerialNumber::STATUS_AVAILABLE)
                ->where('tenant_id', $tenantId)
                ->where('location_id', $locationId)
                ->where('department_id', $departmentId)
                ->limit($quantity)
                ->get();

            if ($serials->count() < $quantity) {
                throw new \Exception("Insufficient serial numbers for {$variant->name}. Available: {$serials->count()}, Required: {$quantity}");
            }

            foreach ($serials as $serial) {
                $serial->update([
                    'status' => SerialNumber::STATUS_SOLD,
                    'order_id' => $order->id,
                    'sold_at' => now(),
                    'sold_by' => auth()->id(),
                ]);
            }
            
            Log::info('[Invoice] Serial sold (Multi Shop - FIFO)', [
                'variant' => $variant->name,
                'quantity' => $quantity,
                'serials' => $serials->pluck('serial_number')->toArray(),
                'location_id' => $locationId,
                'department_id' => $departmentId,
            ]);
            
            $depletedQuantity = $quantity;
        }

        // ✅ Update inventory allocation
        $inventory->quantity_allocated = max(0, $inventory->quantity_allocated - $depletedQuantity);
        $inventory->save();

        // Log inventory transaction
        InventoryTransactions::create([
            'quantity' => -$depletedQuantity,
            'reference_id' => $order->id,
            'reference_type' => 'order',
            'type' => 'sale',
            'notes' => "Invoice sent - Order #{$order->order_number} - {$variant->name} (SERIAL)",
            'inventory_id' => $inventory->id,
            'created_by' => auth()->id(),
            'tenant_id' => $tenantId,
        ]);

        // Log adjustment
        InventoryAdjustments::create([
            'quantity_before' => $inventory->quantity_allocated + $depletedQuantity,
            'quantity_after' => $inventory->quantity_allocated,
            'reason' => 'invoice_sent',
            'notes' => "Invoice sent - Order #{$order->order_number} - {$variant->name} (SERIAL)",
            'inventory_id' => $inventory->id,
            'created_by' => auth()->id(),
            'tenant_id' => $tenantId,
        ]);
    }

    // ─── RECIPE DEPLETION FOR INVOICE (with serial support) ─────────────

    private function depleteRecipeIngredientsForInvoice($variant, $item, $order)
    {
        $product = $variant->product;
        
        if (!$product->hasRecipe()) {
            throw new \Exception("No recipe found for {$product->name}");
        }

        $recipe = $product->recipe;
        $ingredients = $recipe->ingredients;
        $quantityMultiplier = $item->quantity;

        if ($ingredients->isEmpty()) {
            throw new \Exception("Recipe for {$product->name} has no ingredients");
        }

        $isSingleShop = tenant_is_single_shop($order->tenant_id);

        Log::info('[Invoice] Depleting Recipe', [
            'product' => $product->name,
            'quantity' => $item->quantity,
            'order_id' => $order->id
        ]);

        foreach ($ingredients as $ingredient) {
            $ingredientVariant = $ingredient->ingredientVariant;
            $requiredQuantity = $ingredient->quantity_required * $quantityMultiplier;

            if (!$ingredientVariant) {
                throw new \Exception("Ingredient variant not found for recipe '{$product->name}'");
            }

            $ingredientProduct = $ingredientVariant->product;
            if (!$ingredientProduct) {
                throw new \Exception("Product not found for ingredient '{$ingredientVariant->name}'");
            }

            $strategy = $ingredientProduct->resolvedInventoryStrategy();
            
            if ($strategy === 'recipe') {
                throw new \Exception("Nested recipes are not allowed. '{$ingredientVariant->name}' is also a recipe product.");
            }

            // ✅ Check stock BEFORE depleting
            $this->checkIngredientStockForInvoice($ingredientVariant, $requiredQuantity, $order->tenant_id);

            // ✅ Get inventory data for this ingredient (for multi-shop)
            $inventoryData = [];
            if (!$isSingleShop) {
                $inventory = InventoryItems::where('variant_id', $ingredientVariant->id)
                    ->where('tenant_id', $order->tenant_id)
                    ->first();
                
                if ($inventory) {
                    $inventoryData = [
                        'inventory_id' => $inventory->id,
                        'location_id' => $inventory->location_id,
                        'department_id' => $inventory->department_id,
                    ];
                }
            }

            // ✅ Create the ingredient item with ALL required properties including serial fields
            $ingredientItem = (object) [
                'variant_id' => $ingredientVariant->id,
                'quantity' => $requiredQuantity,
                'name' => $ingredientVariant->name,
                'price' => 0,
                'tax_total' => 0,
                'discount' => 0,
                'total' => 0,
                'batch_id' => null,
                'batch_number' => null,
                'serial_id' => null,        // ✅ For serial ingredients
                'serial_number' => null,    // ✅ For serial ingredients
                'inventory_data' => !empty($inventoryData) ? json_encode($inventoryData) : null,
            ];

            // Deplete the ingredient using its strategy
            $this->depleteInventoryByStrategyForInvoice($ingredientVariant, $ingredientItem, $order, $isSingleShop);
        }

        // Update recipe product quantity (virtual, for reference only)
        $variant->overal_quantity_at_hand = max(0, ($variant->overal_quantity_at_hand ?? 0) - $item->quantity);
        $variant->save();

        Log::info('[Invoice] Recipe depleted', [
            'product' => $product->name,
            'quantity' => $item->quantity,
            'order_id' => $order->id
        ]);
    }

    // ─── STOCK CHECK FOR INVOICE ─────────────────────────────────────────

    private function checkIngredientStockForInvoice($variant, $requiredQuantity, $tenantId)
    {
        $product = $variant->product;
        if (!$product) {
            throw new \Exception("Product not found for variant '{$variant->name}'");
        }

        $strategy = $product->resolvedInventoryStrategy();
        $isSingleShop = tenant_is_single_shop($tenantId);

        if ($strategy === 'recipe') {
            throw new \Exception("Nested recipes are not allowed. '{$variant->name}' is a recipe product.");
        }

        if ($strategy === 'serial') {
            // ✅ Check serial numbers availability
            if ($isSingleShop) {
                $available = SerialNumber::where('variant_id', $variant->id)
                    ->where('status', SerialNumber::STATUS_AVAILABLE)
                    ->where('tenant_id', $tenantId)
                    ->count();
                    
                if ($available < $requiredQuantity) {
                    throw new \Exception("Insufficient serial numbers for {$variant->name}. Available: {$available}, Required: {$requiredQuantity}");
                }
            } else {
                // Multi-shop: Check serials by location/department
                // This will be checked in the depletion method with location filtering
                $available = SerialNumber::where('variant_id', $variant->id)
                    ->where('status', SerialNumber::STATUS_AVAILABLE)
                    ->where('tenant_id', $tenantId)
                    ->count();
                    
                if ($available < $requiredQuantity) {
                    throw new \Exception("Insufficient serial numbers for {$variant->name}. Available: {$available}, Required: {$requiredQuantity}");
                }
            }
            return true;
        }

        if ($strategy === 'batch') {
            $available = PurchaseReceiptItem::query()
                ->join('purchase_receipts', 'purchase_receipt_items.purchase_receipt_id', '=', 'purchase_receipts.id')
                ->join('purchase_orders', 'purchase_receipts.purchase_order_id', '=', 'purchase_orders.id')
                ->join('purchase_order_items', 'purchase_receipt_items.purchase_order_item_id', '=', 'purchase_order_items.id')
                ->where('purchase_orders.tenant_id', $tenantId)
                ->where('purchase_order_items.product_variant_id', $variant->id)
                ->where(function($q) {
                    $q->where('purchase_receipt_items.quantity_remaining', '>', 0)
                      ->orWhereNull('purchase_receipt_items.quantity_remaining');
                })
                ->sum('purchase_receipt_items.quantity_remaining');
                
            if ($available < $requiredQuantity) {
                throw new \Exception("Insufficient batch stock for {$variant->name}. Available: {$available}, Required: {$requiredQuantity}");
            }
            return true;
        }

        // Quantity strategy
        if ($isSingleShop) {
            $available = $variant->overal_quantity_at_hand ?? 0;
            if ($available < $requiredQuantity) {
                throw new \Exception("Insufficient stock for {$variant->name}. Available: {$available}, Required: {$requiredQuantity}");
            }
        } else {
            $inventory = InventoryItems::where('variant_id', $variant->id)
                ->where('tenant_id', $tenantId)
                ->first();

            if (!$inventory) {
                throw new \Exception("No inventory allocation found for {$variant->name}");
            }

            if ($inventory->quantity_allocated < $requiredQuantity) {
                throw new \Exception("Insufficient stock for {$variant->name}. Available: {$inventory->quantity_allocated}, Required: {$requiredQuantity}");
            }
        }

        return true;
    }



    // ─── SINGLE SHOP QUANTITY ────────────────────────────────────────────

    private function depleteSingleQuantityForInvoice($variant, $item, $order)
    {
        $before = $variant->overal_quantity_at_hand;
        $after = $before - $item->quantity;

        if ($after < 0) {
            throw new \Exception("Insufficient stock for {$variant->name}. Available: {$before}, Required: {$item->quantity}");
        }

        $variant->update(['overal_quantity_at_hand' => $after]);

        SingleShopInventoryLog::create([
            'variant_id' => $variant->id,
            'order_id' => $order->id,
            'tenant_id' => $order->tenant_id,
            'created_by' => auth()->id(),
            'quantity_before' => $before,
            'quantity_after' => $after,
            'quantity_change' => -$item->quantity,
            'reason' => 'invoice_sent',
            'notes' => "Invoice sent - Order #{$order->order_number} ({$variant->name})",
            'source' => 'invoice',
            'metadata' => ['strategy' => 'quantity']
        ]);

        Log::info('[Invoice] Single Shop Quantity depleted', [
            'variant' => $variant->name,
            'before' => $before,
            'after' => $after
        ]);
    }

    // ─── SINGLE SHOP BATCH ──────────────────────────────────────────────

    private function depleteSingleBatchForInvoice($variant, $item, $order)
    {
        $tenantId = $order->tenant_id;
        $quantityNeeded = $item->quantity;
        
        // ✅ Get batch_id from the order item
        $batchId = $item->batch_id ?? null;
        
        Log::info('[Invoice] Depleting Single Batch', [
            'variant_id' => $variant->id,
            'variant_name' => $variant->name,
            'batch_id' => $batchId,
            'batch_number' => $item->batch_number ?? null,
            'quantity_needed' => $quantityNeeded,
            'order_id' => $order->id
        ]);
        
        if ($batchId) {
            $batch = PurchaseReceiptItem::query()
                ->join('purchase_receipts', 'purchase_receipt_items.purchase_receipt_id', '=', 'purchase_receipts.id')
                ->join('purchase_orders', 'purchase_receipts.purchase_order_id', '=', 'purchase_orders.id')
                ->where('purchase_orders.tenant_id', $tenantId)
                ->where('purchase_receipt_items.id', $batchId)
                ->where(function($q) {
                    $q->where('purchase_receipt_items.quantity_remaining', '>', 0)
                      ->orWhereNull('purchase_receipt_items.quantity_remaining');
                })
                ->select('purchase_receipt_items.*')
                ->first();
                
            if (!$batch) {
                throw new \Exception("Batch not found or has no remaining quantity");
            }
            
            $effectiveQuantity = $batch->quantity_remaining ?? $batch->quantity_received ?? 0;
            if ($effectiveQuantity < $quantityNeeded) {
                throw new \Exception("Insufficient quantity in batch {$batch->batch_number}. Available: {$effectiveQuantity}, Required: {$quantityNeeded}");
            }
            
            if ($batch->quantity_remaining !== null) {
                $batch->quantity_remaining -= $quantityNeeded;
            } else {
                $batch->quantity_remaining = ($batch->quantity_received ?? 0) - $quantityNeeded;
            }
            $batch->save();
            
            $this->logBatchDepletionForInvoice($batch, $variant, $item, $order, $quantityNeeded);
            
            $variant->overal_quantity_at_hand = max(0, ($variant->overal_quantity_at_hand ?? 0) - $quantityNeeded);
            $variant->save();
            return;
        }
        
        // FIFO fallback
        $batches = PurchaseReceiptItem::query()
            ->join('purchase_receipts', 'purchase_receipt_items.purchase_receipt_id', '=', 'purchase_receipts.id')
            ->join('purchase_orders', 'purchase_receipts.purchase_order_id', '=', 'purchase_orders.id')
            ->join('purchase_order_items', 'purchase_receipt_items.purchase_order_item_id', '=', 'purchase_order_items.id')
            ->where('purchase_orders.tenant_id', $tenantId)
            ->where('purchase_order_items.product_variant_id', $variant->id)
            ->where(function($q) {
                $q->where('purchase_receipt_items.quantity_remaining', '>', 0)
                  ->orWhereNull('purchase_receipt_items.quantity_remaining');
            })
            ->orderBy('purchase_receipt_items.expiry_date', 'asc')
            ->select('purchase_receipt_items.*')
            ->get();

        if ($batches->isEmpty()) {
            throw new \Exception("No available batches for {$variant->name}");
        }

        $totalAvailable = $batches->sum(function($batch) {
            return $batch->quantity_remaining ?? $batch->quantity_received ?? 0;
        });
        
        if ($totalAvailable < $quantityNeeded) {
            throw new \Exception("Insufficient batch stock for {$variant->name}. Available: {$totalAvailable}, Required: {$quantityNeeded}");
        }

        foreach ($batches as $batch) {
            if ($quantityNeeded <= 0) break;

            $effectiveQuantity = $batch->quantity_remaining ?? $batch->quantity_received ?? 0;
            $deduct = min($effectiveQuantity, $quantityNeeded);
            
            if ($batch->quantity_remaining !== null) {
                $batch->quantity_remaining -= $deduct;
            } else {
                $batch->quantity_remaining = ($batch->quantity_received ?? 0) - $deduct;
            }
            $batch->save();
            $quantityNeeded -= $deduct;

            $this->logBatchDepletionForInvoice($batch, $variant, $item, $order, $deduct);
        }

        $variant->overal_quantity_at_hand = max(0, ($variant->overal_quantity_at_hand ?? 0) - $quantityNeeded);
        $variant->save();
    }



    // ─── MULTI SHOP QUANTITY ─────────────────────────────────────────────

    private function depleteMultiQuantityForInvoice($variant, $item, $order)
    {
        $user = Auth::user();
        $tenantId = $order->tenant_id;

        $inventoryData = json_decode($item->inventory_data, true);
        $inventoryId = $inventoryData['inventory_id'] ?? null;
        $locationId = $inventoryData['location_id'] ?? $user->location_id ?? null;
        $departmentId = $inventoryData['department_id'] ?? $user->department_id ?? null;

        $inventory = null;
        if ($inventoryId) {
            $inventory = InventoryItems::find($inventoryId);
        }

        if (!$inventory && $locationId && $departmentId) {
            $inventory = InventoryItems::where('variant_id', $variant->id)
                ->where('tenant_id', $tenantId)
                ->where('location_id', $locationId)
                ->where('department_id', $departmentId)
                ->first();
        }

        if (!$inventory) {
            throw new \Exception("No inventory found for {$variant->name} at location/department");
        }

        $before = $inventory->quantity_allocated;
        $after = $before - $item->quantity;

        if ($after < 0) {
            throw new \Exception("Insufficient stock for {$variant->name}. Available: {$before}, Required: {$item->quantity}");
        }

        $inventory->update(['quantity_allocated' => $after]);

        InventoryTransactions::create([
            'quantity' => -$item->quantity,
            'reference_id' => $order->id,
            'reference_type' => 'order',
            'type' => 'sale',
            'notes' => "Invoice sent - Order #{$order->order_number} - {$variant->name}",
            'inventory_id' => $inventory->id,
            'created_by' => auth()->id(),
            'tenant_id' => $tenantId,
        ]);

        InventoryAdjustments::create([
            'quantity_before' => $before,
            'quantity_after' => $after,
            'reason' => 'invoice_sent',
            'notes' => "Invoice sent - Order #{$order->order_number} - {$variant->name}",
            'inventory_id' => $inventory->id,
            'created_by' => auth()->id(),
            'tenant_id' => $tenantId,
        ]);

        Log::info('[Invoice] Multi Shop Quantity depleted', [
            'variant' => $variant->name,
            'inventory_id' => $inventory->id,
            'location_id' => $inventory->location_id,
            'department_id' => $inventory->department_id,
            'before' => $before,
            'after' => $after
        ]);
    }

    // ─── MULTI SHOP BATCH ────────────────────────────────────────────────

    private function depleteMultiBatchForInvoice($variant, $item, $order)
    {
        $user = Auth::user();
        $tenantId = $order->tenant_id;
        $quantityNeeded = $item->quantity;
        
        // ✅ Get batch_id from the order item
        $batchId = $item->batch_id ?? null;
        
        Log::info('[Invoice] Depleting Multi Batch', [
            'variant_id' => $variant->id,
            'variant_name' => $variant->name,
            'batch_id' => $batchId,
            'batch_number' => $item->batch_number ?? null,
            'quantity_needed' => $quantityNeeded,
            'order_id' => $order->id
        ]);

        $inventoryData = json_decode($item->inventory_data, true);
        $inventoryId = $inventoryData['inventory_id'] ?? null;
        $locationId = $inventoryData['location_id'] ?? $user->location_id ?? null;
        $departmentId = $inventoryData['department_id'] ?? $user->department_id ?? null;

        $inventory = null;
        if ($inventoryId) {
            $inventory = InventoryItems::find($inventoryId);
        }

        if (!$inventory && $locationId && $departmentId) {
            $inventory = InventoryItems::where('variant_id', $variant->id)
                ->where('tenant_id', $tenantId)
                ->where('location_id', $locationId)
                ->where('department_id', $departmentId)
                ->first();
        }

        if (!$inventory) {
            throw new \Exception("No inventory found for {$variant->name} at this location/department");
        }

        $batches = PurchaseReceiptItem::query()
            ->join('purchase_receipts', 'purchase_receipt_items.purchase_receipt_id', '=', 'purchase_receipts.id')
            ->join('purchase_orders', 'purchase_receipts.purchase_order_id', '=', 'purchase_orders.id')
            ->join('purchase_order_items', 'purchase_receipt_items.purchase_order_item_id', '=', 'purchase_order_items.id')
            ->where('purchase_orders.tenant_id', $tenantId)
            ->where('purchase_order_items.product_variant_id', $variant->id)
            ->where(function($q) {
                $q->where('purchase_receipt_items.quantity_remaining', '>', 0)
                  ->orWhereNull('purchase_receipt_items.quantity_remaining');
            })
            ->when($locationId, function($q) use ($locationId) {
                return $q->where('purchase_receipt_items.location_id', $locationId);
            })
            ->when($departmentId, function($q) use ($departmentId) {
                return $q->where('purchase_receipt_items.department_id', $departmentId);
            })
            ->orderBy('purchase_receipt_items.expiry_date', 'asc')
            ->select('purchase_receipt_items.*')
            ->get();

        if ($batches->isEmpty()) {
            throw new \Exception("No available batches for {$variant->name} at this location/department");
        }

        $totalAvailable = $batches->sum(function($batch) {
            return $batch->quantity_remaining ?? $batch->quantity_received ?? 0;
        });
        
        if ($totalAvailable < $quantityNeeded) {
            throw new \Exception("Insufficient batch stock for {$variant->name}. Available: {$totalAvailable}, Required: {$quantityNeeded}");
        }

        if ($batchId) {
            $targetBatch = $batches->firstWhere('id', $batchId);
            if (!$targetBatch) {
                throw new \Exception("Specified batch not found or not available");
            }
            
            $effectiveQuantity = $targetBatch->quantity_remaining ?? $targetBatch->quantity_received ?? 0;
            if ($effectiveQuantity < $quantityNeeded) {
                throw new \Exception("Insufficient quantity in batch {$targetBatch->batch_number}. Available: {$effectiveQuantity}, Required: {$quantityNeeded}");
            }
            
            if ($targetBatch->quantity_remaining !== null) {
                $targetBatch->quantity_remaining -= $quantityNeeded;
            } else {
                $targetBatch->quantity_remaining = ($targetBatch->quantity_received ?? 0) - $quantityNeeded;
            }
            $targetBatch->save();
            
            $this->logBatchDepletionForInvoice($targetBatch, $variant, $item, $order, $quantityNeeded);
            
        } else {
            foreach ($batches as $batch) {
                if ($quantityNeeded <= 0) break;

                $effectiveQuantity = $batch->quantity_remaining ?? $batch->quantity_received ?? 0;
                $deduct = min($effectiveQuantity, $quantityNeeded);
                
                if ($batch->quantity_remaining !== null) {
                    $batch->quantity_remaining -= $deduct;
                } else {
                    $batch->quantity_remaining = ($batch->quantity_received ?? 0) - $deduct;
                }
                $batch->save();
                $quantityNeeded -= $deduct;

                $this->logBatchDepletionForInvoice($batch, $variant, $item, $order, $deduct);
            }
        }

        $inventory->quantity_allocated = max(0, $inventory->quantity_allocated - $quantityNeeded);
        $inventory->save();

        InventoryTransactions::create([
            'quantity' => -$quantityNeeded,
            'reference_id' => $order->id,
            'reference_type' => 'order',
            'type' => 'sale',
            'notes' => "Invoice sent - Order #{$order->order_number} - {$variant->name} (BATCH)",
            'inventory_id' => $inventory->id,
            'created_by' => auth()->id(),
            'tenant_id' => $tenantId,
        ]);

        Log::info('[Invoice] Multi Shop Batch depleted', [
            'variant' => $variant->name,
            'inventory_id' => $inventory->id,
            'location_id' => $locationId,
            'department_id' => $departmentId,
            'quantity' => $quantityNeeded,
            'batch_id_used' => $batchId
        ]);
    }



    // ─── BATCH LOG FOR INVOICE ───────────────────────────────────────────

    private function logBatchDepletionForInvoice($batch, $variant, $item, $order, $quantityDeducted)
    {
        $beforeQty = ($batch->quantity_remaining ?? $batch->quantity_received ?? 0) + $quantityDeducted;
        $afterQty = $batch->quantity_remaining ?? $batch->quantity_received ?? 0;
        
        BatchLog::create([
            'batch_id' => $batch->id,
            'batch_number' => $batch->batch_number,
            'variant_id' => $variant->id,
            'variant_name' => $variant->name,
            'variant_sku' => $variant->sku,
            'type' => BatchLog::TYPE_DEPLETED,
            'quantity_change' => -$quantityDeducted,
            'quantity_before' => $beforeQty,
            'quantity_after' => $afterQty,
            'unit_cost' => $batch->unit_cost ?? 0,
            'total_cost' => ($batch->unit_cost ?? 0) * $quantityDeducted,
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'tenant_id' => $order->tenant_id,
            'location_id' => $batch->location_id ?? $order->location_id,
            'department_id' => $batch->department_id ?? $order->department_id,
            'expiry_date' => $batch->expiry_date,
            'event_date' => now(),
            'performed_by' => auth()->id(),
            'metadata' => [
                'item_name' => $item->name ?? $variant->name,
                'customer_name' => $order->customer_name,
                'unit_price' => $item->price ?? 0,
                'source' => 'invoice',
                'invoice_id' => $order->invoice_id ?? null,
            ],
        ]);
    }

    // ─── VOID / RESTOCK METHODS WITH STRATEGY SUPPORT ────────────────────

    private function restockSingleShopForVoid($variant, $item, $order)
    {
        $beforeQty = $variant->overal_quantity_at_hand;
        $afterQty = $beforeQty + $item->quantity;

        $variant->update(['overal_quantity_at_hand' => $afterQty]);

        SingleShopInventoryLog::create([
            'variant_id' => $variant->id,
            'order_id' => $order->id,
            'tenant_id' => $order->tenant_id,
            'created_by' => auth()->id(),
            'quantity_before' => $beforeQty,
            'quantity_after' => $afterQty,
            'quantity_change' => $item->quantity,
            'reason' => 'invoice_voided',
            'notes' => 'Invoice voided, stock restored - Order #' . $order->order_number,
            'source' => 'invoice',
            'metadata' => [
                'item_name' => $item->item_name,
                'unit_price' => $item->unit_price,
            ],
        ]);
        
        Log::info('[Invoice] Void - Stock restored (Single Shop)', [
            'variant' => $variant->name,
            'quantity' => $item->quantity,
            'order_id' => $order->id
        ]);
    }

    private function restockMultiShopForVoid($variant, $item, $order, $user)
    {
        $inventoryData = json_decode($item->inventory_data, true);
        $inventoryId = $inventoryData['inventory_id'] ?? null;
        $departmentId = $inventoryData['department_id'] ?? $user->department_id ?? 1;
        $locationId = $inventoryData['location_id'] ?? $user->location_id ?? 1;

        if ($inventoryId) {
            $inventory = InventoryItems::find($inventoryId);
        }

        if (!$inventory) {
            $inventory = $variant->inventory()
                ->where('location_id', $locationId)
                ->where('department_id', $departmentId)
                ->first();
        }

        if (!$inventory) {
            Log::warning("No inventory found for variant {$variant->id} while voiding invoice", [
                'variant_id' => $variant->id,
                'order_id' => $order->id,
            ]);
            return;
        }

        $beforeQty = $inventory->quantity_allocated;
        $afterQty = $beforeQty + $item->quantity;

        $inventory->update(['quantity_allocated' => $afterQty]);

        InventoryAdjustments::create([
            'quantity_before' => $beforeQty,
            'quantity_after' => $afterQty,
            'reason' => 'invoice_voided',
            'notes' => "Invoice voided, stock restored - Order #{$order->order_number} - {$variant->name}",
            'inventory_id' => $inventory->id,
            'created_by' => auth()->id(),
            'tenant_id' => $order->tenant_id,
        ]);

        InventoryTransactions::create([
            'quantity' => $item->quantity,
            'reference_id' => $order->id,
            'reference_type' => 'order',
            'type' => 'return',
            'notes' => "Stock restored - Invoice voided - Order #{$order->order_number} - {$variant->name}",
            'inventory_id' => $inventory->id,
            'created_by' => auth()->id(),
            'tenant_id' => $order->tenant_id,
        ]);

        Log::info('[Invoice] Void - Stock restored (Multi Shop)', [
            'variant' => $variant->name,
            'inventory_id' => $inventory->id,
            'quantity' => $item->quantity,
            'order_id' => $order->id
        ]);
    }


}