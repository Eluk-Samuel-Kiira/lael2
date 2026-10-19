<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{ Order, Location, ProductVariant };
use App\Models\{ OrderPayment, InventoryAdjustments,
                    InventoryTransactions, SingleShopInventoryLog, PaymentMethod, Currency,
                    InvoicePayment, InventoryItems, PurchaseReceiptItem, BatchLog, SerialNumber };
use App\Mail\OrderReceiptMail;
use App\Services\MessagingService;
use Illuminate\Support\Facades\{ Auth, DB, Mail, Log };

class OrderController extends Controller
{
    public function send(Request $request, $id, MessagingService $messaging)
    {
        $user = Auth::user();
        $order = Order::where('tenant_id', $user->tenant_id)->findOrFail($id);

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
                $emailToUse = $validated['email'] ?? $order->customer_email;
                if (!$emailToUse) {
                    return response()->json([
                        'success' => false,
                        'message' => __('payments.customer_email_required'),
                    ], 422);
                }

                $subject = $validated['subject'] ?? __('passwords.order_receipt_subject', [
                    'number' => $order->order_number,
                ]);

                Mail::to($emailToUse)->send(
                    new OrderReceiptMail($order, $subject, $validated['message'] ?? null)
                );

                return response()->json([
                    'success' => true,
                    'message' => __('passwords.receipt_sent'),
                ]);
            }

            if ($channel === 'whatsapp') {
                $phoneToUse = $validated['phone'] ?? $order->customer_phone;
                if (!$phoneToUse) {
                    return response()->json([
                        'success' => false,
                        'message' => __('payments.customer_phone_required'),
                    ], 422);
                }

                $result = $messaging->sendWhatsApp($phoneToUse, [
                    'ref'  => $order->order_number,
                    'date' => $order->created_at->format('d M Y'),
                ]);

                if (!$result['success']) {
                    return response()->json([
                        'success' => false,
                        'message' => $result['error'],
                    ], 500);
                }

                return response()->json([
                    'success' => true,
                    'message' => __('passwords.receipt_sent'),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Order receipt send failed: ' . $e->getMessage(), ['order_id' => $id]);
            return response()->json([
                'success' => false,
                'message' => __('passwords.receipt_send_failed'),
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('view order')) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.not_authorized'),
                ]);
            }
            abort(403);
        }

        // ── Visibility rule ─────────────────────────────────────────
        $isAdmin         = $user->hasAnyRole(['super_admin', 'admin']);
        $userLocationIds = $user->locations()->pluck('locations.id')->toArray();

        // ── Pagination ──────────────────────────────────────────────
        $perPage = $request->input('per_page', 15);
        $allowedPerPage = [15, 25, 50, 100];
        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 15;
        }

        // ── Base query ──────────────────────────────────────────────
        $query = Order::with([
            'orderItems',
            'customer',
            'orderCreater',
            'location',
            'department',
            'orderPayments',
        ]);

        if (!$user->hasRole('super_admin')) {
            $query->where('tenant_id', $tenantId);
        }

        if (!$isAdmin) {
            $query->where('created_by', $user->id);
        }

        // ── Search ──────────────────────────────────────────────────
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                ->orWhere('customer_name', 'like', "%{$search}%")
                ->orWhere('status', 'like', "%{$search}%")
                ->orWhere('type', 'like', "%{$search}%")
                ->orWhere('source', 'like', "%{$search}%")
                ->orWhereHas('customer', function ($c) use ($search) {
                    $c->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                })
                ->orWhereHas('orderCreater', fn($cr) => $cr->where('name', 'like', "%{$search}%"));
            });
        }

        // ── Location filter ─────────────────────────────────────────
        if ($request->filled('location_id')) {
            $requestedLocationId = (int) $request->input('location_id');

            // Admins can filter by any tenant location.
            // Non-admins may only filter within their own assigned locations.
            $allowedToFilter = $isAdmin
                || in_array($requestedLocationId, $userLocationIds, true);

            if ($allowedToFilter) {
                $query->where('location_id', $requestedLocationId);
            }
        }

        // ── Paginate ────────────────────────────────────────────────
        $orders = $query->latest()->paginate($perPage);
        $orders->appends([
            'per_page'    => $perPage,
            'search'      => $request->search,
            'location_id' => $request->location_id,
        ]);

        // ── AJAX partial reload ─────────────────────────────────────
        $bladeToReload = $request->query('bladeFileToReload');
        if ($request->ajax() && $bladeToReload === 'ordersIndexTable') {
            return view('orders.order.component', [
                'orders' => $orders,
            ])->render();
        }

        // ── Locations for the filter dropdown ───────────────────────
        // Admins see every tenant location. Non-admins see only theirs.
        $locationsQuery = Location::where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->orderBy('name');

        if (!$isAdmin) {
            $locationsQuery->whereIn('id', $userLocationIds);
        }

        $locations = $locationsQuery->get();

        $initialFilters = [
            'search'      => (string) $request->get('search', ''),
            'location_id' => (string) $request->get('location_id', ''),
            'status'      => (string) $request->get('status', ''),
            'source'      => (string) $request->get('source', ''),
            'date_from'   => (string) $request->get('date_from', ''),
            'date_to'     => (string) $request->get('date_to', ''),
            'per_page'    => $perPage,
        ];

        return view('orders.order-index', [
            'orders'         => $orders,          // kept for legacy component include
            'locations'      => $locations,
            'initialFilters' => $initialFilters,
        ]);

    }

    // ─────────────────────────────────────────────────────────────────
    // GET /orders/data — JSON list for the JS table
    // ─────────────────────────────────────────────────────────────────
    public function data(Request $request)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('view order')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ], 403);
        }

        $isAdmin         = $user->hasAnyRole(['super_admin', 'admin']);
        $userLocationIds = $user->locations()->pluck('locations.id')->toArray();

        $search     = trim((string) $request->get('search', ''));
        $locationId = $request->get('location_id');
        $status     = $request->get('status');
        $source     = $request->get('source');
        $dateFrom   = $request->get('date_from');
        $dateTo     = $request->get('date_to');

        $perPage = (int) $request->get('per_page', 15);
        if (!in_array($perPage, [15, 25, 50, 100], true)) $perPage = 15;

        $query = Order::query()
            ->with([
                'customer:id,first_name,last_name,email,phone',
                'orderCreater:id,name',
                'location:id,name',
                'department.location',
            ])
            ->where('tenant_id', $tenantId);

        if (!$isAdmin) {
            $query->where('created_by', $user->id);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                ->orWhere('customer_name', 'like', "%{$search}%")
                ->orWhere('status', 'like', "%{$search}%")
                ->orWhere('source', 'like', "%{$search}%")
                ->orWhereHas('customer', function ($c) use ($search) {
                    $c->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                })
                ->orWhereHas('orderCreater', fn($cr) => $cr->where('name', 'like', "%{$search}%"));
            });
        }

        if ($locationId) {
            $requested = (int) $locationId;
            if ($isAdmin || in_array($requested, $userLocationIds, true)) {
                $query->where('location_id', $requested);
            }
        }

        if ($status)  $query->where('status', $status);
        if ($source)  $query->where('source', $source);
        if ($dateFrom) $query->whereDate('created_at', '>=', $dateFrom);
        if ($dateTo)   $query->whereDate('created_at', '<=', $dateTo);

        // ── Summary from the FULL filtered set ────────────────────
        $allFiltered = (clone $query)->get([
            'id', 'status', 'total', 'paid_amount', 'balance_due',
        ]);

        $summary = [
            'total'          => $allFiltered->count(),
            'pending'        => $allFiltered->where('status', 'pending')->count(),
            'confirmed'      => $allFiltered->where('status', 'confirmed')->count(),
            'processing'     => $allFiltered->where('status', 'processing')->count(),
            'completed'      => $allFiltered->where('status', 'completed')->count(),
            'cancelled'      => $allFiltered->where('status', 'cancelled')->count(),
            'refunded'       => $allFiltered->where('status', 'refunded')->count(),
            'total_value'    => (float) $allFiltered->sum('total'),
            'total_paid'     => (float) $allFiltered->sum('paid_amount'),
            'total_balance'  => (float) $allFiltered->sum('balance_due'),
        ];

        $paginator = $query->latest()->paginate($perPage);

        $rows = collect($paginator->items())->map(function (Order $order) {
            $locationName = $order->correct_location?->name
                ?? $order->location?->name
                ?? '—';

            $customerName = $order->customer_name
                ?? trim(($order->customer?->first_name ?? '') . ' ' . ($order->customer?->last_name ?? ''))
                ?: null;

            return [
                'id'              => $order->id,
                'order_number'    => $order->order_number,
                'customer_name'   => $customerName ?: '—',
                'customer_initial'=> strtoupper(substr($customerName ?? '?', 0, 1)),
                'location_name'   => $locationName,
                'department_name' => $order->department?->name,

                'status'          => $order->status,
                'status_label'    => ucwords($order->status),
                'status_color'    => $this->statusColor($order->status),

                'source'          => $order->source,
                'source_label'    => ucwords($order->source),

                'type'            => $order->type,
                'created_by_name' => $order->orderCreater?->name ?? '—',

                'total'           => (float) $order->total,
                'total_formatted' => format_currency($order->total),
                'paid_amount'     => (float) $order->paid_amount,
                'balance_due'     => (float) $order->balance_due,
                'balance_due_formatted' => format_currency($order->balance_due),

                'created_at'      => optional($order->created_at)->format('d M Y H:i'),
                'created_date'    => optional($order->created_at)->format('Y-m-d'),
            ];
        });

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
            'filters' => [
                'search'      => $search,
                'location_id' => $locationId,
                'status'      => $status,
                'source'      => $source,
                'date_from'   => $dateFrom,
                'date_to'     => $dateTo,
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // GET /orders/{id}/details — drawer payload
    // ─────────────────────────────────────────────────────────────────
    public function details(Request $request, $id)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('view order')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ], 403);
        }

        $isAdmin = $user->hasAnyRole(['super_admin', 'admin']);

        $query = Order::query()
            ->with([
                'orderItems.variant',
                'customer',
                'orderCreater:id,name',
                'location:id,name',
                'department.location',
                'orderPayments.paymentMethod',
            ])
            ->where('tenant_id', $tenantId);

        if (!$isAdmin) {
            $query->where('created_by', $user->id);
        }

        $order = $query->find($id);
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ], 404);
        }

        $items = $order->orderItems->map(fn($item) => [
            'id'             => $item->id,
            'variant_id'     => $item->variant_id ?? $item->product_variant_id,
            'product_id'     => $item->product_id ?? null,
            'item_name'      => $item->item_name,
            'sku'            => $item->sku,
            'quantity'       => (float) $item->quantity,
            'unit_price'     => (float) $item->unit_price,
            'unit_price_formatted' => format_currency($item->unit_price),
            'discount'       => (float) $item->discount,
            'discount_formatted'   => format_currency($item->discount),
            'tax_amount'     => (float) $item->tax_amount,
            'tax_formatted'  => format_currency($item->tax_amount),
            'total_price'    => (float) $item->total_price,
            'total_formatted'=> format_currency($item->total_price),
            'note'           => $item->notes ?? null,

            // ── Refund tracking ───────────────────────────────────
            'refunded_quantity'   => (float) ($item->refunded_quantity ?? 0),
            'refundable_quantity' => $item->refundable_quantity,
            'refunded_amount'     => (float) ($item->refunded_quantity ?? 0) * (float) $item->unit_price,
            'refunded_amount_formatted' => format_currency(
                (float) ($item->refunded_quantity ?? 0) * (float) $item->unit_price
            ),

        ])->values();

        $payments = $order->orderPayments->map(fn($p) => [
            'id'               => $p->id,
            'amount'           => (float) $p->amount,
            'amount_formatted' => format_currency($p->amount),
            'method_name'      => $p->paymentMethod?->name ?? '—',
            'method_type'      => $p->paymentMethod?->type,
            'account_number'   => $p->paymentMethod?->account_number,
            'transaction_id'   => $p->transaction_id,
            'status'           => $p->status,
            'status_label'     => ucfirst($p->status),
            'status_color'     => ['completed'=>'success','pending'=>'warning','failed'=>'danger','refunded'=>'secondary'][$p->status] ?? 'secondary',
            'payment_date'     => optional($p->created_at)->format('d M Y H:i'),

            // Per-payment refund tracking — safe, no cross-scope variables
            'refunded_amount'   => (float) ($p->refunded_amount ?? 0),
            'refundable_amount' => max(0, (float) $p->amount - (float) ($p->refunded_amount ?? 0)),
        ])->values();

        return response()->json([
            'success' => true,
            'order' => [
                'id'             => $order->id,
                'order_number'   => $order->order_number,
                'status'         => $order->status,
                'status_label'   => ucwords($order->status),
                'status_color'   => $this->statusColor($order->status),
                'type'           => $order->type,
                'type_label'     => ucwords($order->type),
                'source'         => $order->source,
                'source_label'   => ucwords($order->source),

                'customer_name'    => $order->customer_name
                    ?? trim(($order->customer?->first_name ?? '') . ' ' . ($order->customer?->last_name ?? ''))
                    ?: '—',
                'customer_initial' => strtoupper(substr($order->customer_name ?? '?', 0, 1)),
                'customer_email'   => $order->customer_email,
                'customer_phone'   => $order->customer_phone,

                'creator_name'   => $order->orderCreater?->name ?? '—',
                'location_name'  => $order->correct_location?->name ?? $order->location?->name ?? '—',
                'department_name'=> $order->department?->name,

                'created_at'     => optional($order->created_at)->format('d M Y H:i'),
                'notes'          => $order->notes,

                'subtotal'       => (float) $order->subtotal,
                'subtotal_formatted'  => format_currency($order->subtotal),
                'discount_total' => (float) $order->discount_total,
                'discount_formatted'  => format_currency($order->discount_total),
                'tax_total'      => (float) $order->tax_total,
                'tax_formatted'       => format_currency($order->tax_total),
                'total'          => (float) $order->total,
                'total_formatted'=> format_currency($order->total),
                'paid_amount'    => (float) $order->paid_amount,
                'paid_formatted' => format_currency($order->paid_amount),

                // ── Refund tracking ────────────────────────────────────────
                'refunded_total'           => (float) $order->orderPayments->sum('refunded_amount'),
                'refundable_cap'           => max(0, (float) $order->paid_amount),
                'refundable_cap_formatted' => format_currency(max(0, (float) $order->paid_amount)),
                'balance_due'    => (float) $order->balance_due,
                'balance_formatted'   => format_currency($order->balance_due),

                'permissions' => [
                    'can_print'    => $user->can('print order'),
                    'can_send'     => $order->status === 'completed',
                    'can_complete' => $user->can('complete order')
                                        && strtolower((string) $order->status) === 'confirmed'
                                        && strtolower((string) $order->source) === 'pos',
                    'can_refund'    => $user->can('refund order')
                                        && $order->status === 'completed'
                                        && ((float) $order->paid_amount > 0),
                    'can_cancel'   => $user->can('cancel order')   && $order->status === 'confirmed',
                ],
            ],
            'items'    => $items,
            'payments' => $payments,
        ]);
    }

    // small helper — reused by data() and details()
    private function statusColor(string $status): string
    {
        return [
            'pending'    => 'warning',
            'confirmed'  => 'primary',
            'processing' => 'info',
            'completed'  => 'success',
            'cancelled'  => 'danger',
            'refunded'   => 'dark',
            'draft'      => 'secondary',
        ][strtolower($status)] ?? 'secondary';
    }

    // ═══════════════════════════════════════════════════════════════════
    //  REFUND — full reversal + stock return
    //  POST /orders/{id}/refund   { reason }
    // ═══════════════════════════════════════════════════════════════════
    public function refund(Request $request, $id)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('refund order')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }

        $validated = $request->validate([
            'reason'                => 'required|string|max:500',
            'items'                 => 'nullable|array',
            'items.*.order_item_id' => 'required_with:items|integer',
            'items.*.quantity'      => 'required_with:items|numeric|min:0.01',
        ]);

        $order = Order::with(['orderItems', 'orderPayments.paymentMethod'])
            ->where('tenant_id', $tenantId)
            ->findOrFail($id);

        if ($order->status !== 'completed') {
            return response()->json(['success' => false, 'message' => __('payments.can_only_refund_completed_orders')], 422);
        }

        $itemsById  = $order->orderItems->keyBy('id');
        $selections = collect($validated['items'] ?? []);

        if ($selections->isEmpty()) {
            $selections = $order->orderItems->map(fn($it) => [
                'order_item_id' => $it->id,
                'quantity'      => (float) $it->quantity,
            ])->values();
        }

        // ── Validate every selection (against REMAINING refundable) ──
        foreach ($selections as $sel) {
            $item = $itemsById->get((int) $sel['order_item_id']);
            if (!$item) {
                return response()->json(['success' => false, 'message' => "Item #{$sel['order_item_id']} is not part of this order."], 422);
            }
            if ((float) $sel['quantity'] <= 0) {
                return response()->json(['success' => false, 'message' => 'Refund quantity must be greater than zero.'], 422);
            }

            $alreadyRefunded = (float) ($item->refunded_quantity ?? 0);
            $remaining       = max(0, (float) $item->quantity - $alreadyRefunded);

            if ((float) $sel['quantity'] > $remaining + 0.0001) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot refund {$sel['quantity']} of \"{$item->item_name}\" — only "
                        . number_format($remaining, 2) . " remain refundable (ordered "
                        . number_format((float) $item->quantity, 2)
                        . ", already refunded " . number_format($alreadyRefunded, 2) . ").",
                ], 422);
            }
        }

        // ── Compute total refund ──
        $refundTotal = 0.0;
        foreach ($selections as $sel) {
            $item = $itemsById->get((int) $sel['order_item_id']);
            $refundTotal += (float) $sel['quantity'] * (float) $item->unit_price;
        }

        if ($refundTotal <= 0) {
            return response()->json(['success' => false, 'message' => 'Nothing to refund.'], 422);
        }

        // ── Cap against paid & not-yet-refunded ──
        $alreadyRefundedMoney = (float) $order->orderPayments->sum('refunded_amount');
        $refundableCap        = (float) $order->paid_amount - $alreadyRefundedMoney;

        if ($refundTotal > $refundableCap + 0.01) {
            return response()->json([
                'success' => false,
                'message' => 'Refund amount (' . number_format($refundTotal, 2)
                    . ') exceeds what has been paid and not yet refunded (' . number_format($refundableCap, 2) . ').',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $actor        = $this->actorLabel($user);
            $customerName = $order->customer_name ?? $order->customer?->name ?? 'the customer';
            $context      = $this->contextLabel($order);
            $reason       = $validated['reason'];

            // ── 1. Reverse payments (oldest first) ──
            $remainingRefund = $refundTotal;
            $affectedPayments = [];

            foreach ($order->orderPayments->sortBy('created_at') as $payment) {
                if ($remainingRefund <= 0) break;
                if ($payment->status !== 'completed') continue;

                $alreadyRefunded = (float) ($payment->refunded_amount ?? 0);
                $available       = (float) $payment->amount - $alreadyRefunded;
                if ($available <= 0) continue;

                $toRefund = min($available, $remainingRefund);

                $payment->refunded_amount = $alreadyRefunded + $toRefund;
                if (abs(($alreadyRefunded + $toRefund) - (float) $payment->amount) < 0.01) {
                    $payment->status = 'refunded';
                }
                $payment->notes = trim(($payment->notes ?? '') . "\n" .
                    "[{$actor}] Partial refund of " . number_format($toRefund, 2) . " on " . now()->format('Y-m-d H:i') .
                    ". Reason: {$reason}");
                $payment->save();

                $method = $payment->paymentMethod;
                if ($method) {
                    $method->current_balance -= $toRefund;
                    $method->save();
                    $this->recordOrderRefundTransaction($order, $method, $toRefund, $actor, $reason);
                }

                $affectedPayments[] = ['payment_id' => $payment->id, 'amount' => $toRefund];
                $remainingRefund -= $toRefund;
            }

            if ($remainingRefund > 0.01) {
                throw new \Exception('Could not allocate the full refund amount across the order\'s payments.');
            }

            // ── 2. Return items to stock + track refunded quantity ──
            $isSingleShop = tenant_is_single_shop($tenantId);

            foreach ($selections as $sel) {
                $item = $itemsById->get((int) $sel['order_item_id']);
                $variant = ProductVariant::find($item->variant_id);
                if (!$variant) continue;

                $product = $variant->product;
                if (!$product) continue;

                $strategy = $product->resolvedInventoryStrategy();

                $slice = clone $item;
                $slice->quantity = (float) $sel['quantity'];

                if ($strategy === 'recipe') {
                    $this->restoreRecipeIngredientsForRefund($variant, $slice, $order);
                } else {
                    $this->restoreInventoryByStrategyForRefund($variant, $slice, $order, $isSingleShop);
                }

                // ✅ Accumulate refunded quantity on the line item
                $item->refunded_quantity = (float) ($item->refunded_quantity ?? 0) + (float) $sel['quantity'];
                $item->save();
            }

            // ── 3. Update order ──
            $newPaidAmount = max(0, (float) $order->paid_amount - $refundTotal);
            $isFullRefund  = $newPaidAmount <= 0.01 && ($order->total - $refundTotal) <= 0.01;

            $order->notes = $this->appendNote(
                $order->notes,
                sprintf(
                    '%s refunded %s from order %s for %s%s. Reason: %s. Items: %d, Payments reversed: %d.',
                    $actor,
                    currency_symbol() . number_format($refundTotal, 2),
                    $order->order_number,
                    $customerName,
                    $context,
                    $reason,
                    $selections->count(),
                    count($affectedPayments)
                )
            );

            $order->update([
                'status'       => $isFullRefund ? 'refunded' : $order->status,
                'paid_amount'  => $newPaidAmount,
                'balance_due'  => $isFullRefund ? 0 : $order->balance_due,
                'notes'        => $order->notes,
            ]);

            // ── 4. Void the invoice on full refund ──
            $invoiceVoided = false;
            if ($isFullRefund) {
                $invoice = \App\Models\Invoice::where('order_id', $order->id)
                    ->where('tenant_id', $tenantId)
                    ->first();

                if ($invoice && !$invoice->isVoid()) {
                    $invoice->notes = $this->appendNote(
                        $invoice->notes,
                        sprintf('%s — invoice voided automatically because the order was fully refunded.', $actor)
                    );
                    $invoice->update([
                        'status'    => \App\Models\Invoice::STATUS_VOID,
                        'voided_at' => now(),
                        'voided_by' => $user->id,
                        'notes'     => $invoice->notes,
                    ]);
                    $invoiceVoided = true;
                }
            }

            DB::commit();

            Log::info("[Order] {$actor} refunded order {$order->order_number} for {$customerName}{$context}. "
                . "Amount: " . number_format($refundTotal, 2) . ". Reason: {$reason}. "
                . "Full refund: " . ($isFullRefund ? 'yes' : 'no') . ".", [
                'order_id'         => $order->id,
                'refund_total'     => $refundTotal,
                'selections'       => $selections->all(),
                'affected_payments'=> $affectedPayments,
            ]);

            return response()->json([
                'success'          => true,
                'message'          => $isFullRefund
                    ? 'Order refunded in full. Stock returned and payments reversed.'
                    : 'Partial refund processed. Selected items returned to stock.',
                'status'           => $order->status,
                'refunded_amount'  => $refundTotal,
                'is_full_refund'   => $isFullRefund,
                'invoice_voided'   => $invoiceVoided,
                'affected_payments'=> $affectedPayments,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order refund failed: ' . $e->getMessage(), ['order_id' => $id, 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Refund failed. Nothing was changed. ' . $e->getMessage(),
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────
    //  Inventory return helpers — mirror InvoiceController::void() logic
    // ─────────────────────────────────────────────────────────────────

    private function restoreInventoryByStrategyForRefund($variant, $item, $order, bool $isSingleShop): void
    {
        $product = $variant->product;
        if (!$product) return;

        $strategy = $product->resolvedInventoryStrategy();

        if ($isSingleShop) {
            match ($strategy) {
                'quantity' => $this->restoreSingleQuantity($variant, $item, $order),
                'batch'    => $this->restoreSingleBatch($variant, $item, $order),
                'serial'   => $this->restoreSingleSerial($variant, $item, $order),
                default    => null,
            };
        } else {
            match ($strategy) {
                'quantity' => $this->restoreMultiQuantity($variant, $item, $order),
                'batch'    => $this->restoreMultiBatch($variant, $item, $order),
                'serial'   => $this->restoreMultiSerial($variant, $item, $order),
                default    => null,
            };
        }
    }

    private function restoreSingleQuantity($variant, $item, $order): void
    {
        $before = (int) ($variant->overal_quantity_at_hand ?? 0);
        $after  = $before + (int) $item->quantity;
        $variant->update(['overal_quantity_at_hand' => $after]);

        SingleShopInventoryLog::create([
            'variant_id'      => $variant->id,
            'order_id'        => $order->id,
            'tenant_id'       => $order->tenant_id,
            'created_by'      => auth()->id(),
            'quantity_before' => $before,
            'quantity_after'  => $after,
            'quantity_change' => (int) $item->quantity,
            'reason'          => 'order_refunded',
            'notes'           => "Returned {$item->quantity} × {$variant->name} to stock on refund of order #{$order->order_number}",
            'source'          => 'order',
        ]);
    }

    private function restoreMultiQuantity($variant, $item, $order): void
    {
        $inventoryId = $this->extractInventoryId($item);
        $inventory = $inventoryId ? InventoryItems::find($inventoryId) : null;

        if (!$inventory) {
            $inventory = InventoryItems::where('variant_id', $variant->id)
                ->where('tenant_id', $order->tenant_id)
                ->first();
        }
        if (!$inventory) return;

        $before = (int) $inventory->quantity_allocated;
        $after  = $before + (int) $item->quantity;
        $inventory->update(['quantity_allocated' => $after]);

        InventoryTransactions::create([
            'quantity'       => (int) $item->quantity,
            'reference_id'   => $order->id,
            'reference_type' => 'order',
            'type'           => 'return',
            'notes'          => "Refund return — {$item->quantity} × {$variant->name}",
            'inventory_id'   => $inventory->id,
            'created_by'     => auth()->id(),
            'tenant_id'      => $order->tenant_id,
        ]);

        InventoryAdjustments::create([
            'quantity_before' => $before,
            'quantity_after'  => $after,
            'reason'          => 'order_refunded',
            'notes'           => "Refund return — {$item->quantity} × {$variant->name} on order #{$order->order_number}",
            'inventory_id'    => $inventory->id,
            'created_by'      => auth()->id(),
            'tenant_id'       => $order->tenant_id,
        ]);
    }

    private function restoreSingleBatch($variant, $item, $order): void
    {
        // Prefer the specific batch the item was sold from
        $batchId = $item->batch_id ?? null;
        if (!$batchId) {
            // Fallback: any batch row for this variant, highest remaining
            $batch = PurchaseReceiptItem::whereHas('purchaseOrderItem', fn($q) =>
                $q->where('product_variant_id', $variant->id)
            )->orderByDesc('quantity_remaining')->first();
            $batchId = $batch?->id;
        }
        if (!$batchId) return;

        $batch = PurchaseReceiptItem::where('tenant_id', $order->tenant_id)->find($batchId);
        if (!$batch) return;

        $before = (float) ($batch->quantity_remaining ?? 0);
        $after  = $before + (float) $item->quantity;
        $batch->update(['quantity_remaining' => $after]);

        BatchLog::create([
            'batch_id'        => $batch->id,
            'batch_number'    => $batch->batch_number,
            'variant_id'      => $variant->id,
            'variant_name'    => $variant->name,
            'variant_sku'     => $variant->sku,
            'type'            => BatchLog::TYPE_ADJUSTED,
            'quantity_change' => (float) $item->quantity,
            'quantity_before' => $before,
            'quantity_after'  => $after,
            'unit_cost'       => $batch->unit_cost ?? 0,
            'total_cost'      => (float) $item->quantity * (float) ($batch->unit_cost ?? 0),
            'order_id'        => $order->id,
            'order_number'    => $order->order_number,
            'tenant_id'       => $order->tenant_id,
            'location_id'     => $batch->location_id ?? $order->location_id,
            'department_id'   => $batch->department_id ?? $order->department_id,
            'expiry_date'     => $batch->expiry_date,
            'event_date'      => now(),
            'performed_by'    => auth()->id(),
            'metadata'        => [
                'reason'      => 'order_refunded',
                'narrative'   => "Refund return to batch {$batch->batch_number} on order #{$order->order_number}",
            ],
        ]);
    }

    private function restoreMultiBatch($variant, $item, $order): void
    {
        $this->restoreSingleBatch($variant, $item, $order);  
    }

    private function restoreSingleSerial($variant, $item, $order): void
    {
        $serialId = $item->serial_id ?? null;
        if ($serialId) {
            $serial = SerialNumber::where('id', $serialId)
                ->where('tenant_id', $order->tenant_id)
                ->first();
            if ($serial) {
                $serial->update([
                    'status'   => SerialNumber::STATUS_AVAILABLE,
                    'order_id' => null,
                    'sold_at'  => null,
                    'sold_by'  => null,
                ]);
            }
        } else {
            // FIFO by matching order_id
            SerialNumber::where('order_id', $order->id)
                ->where('variant_id', $variant->id)
                ->update([
                    'status'   => SerialNumber::STATUS_AVAILABLE,
                    'order_id' => null,
                    'sold_at'  => null,
                    'sold_by'  => null,
                ]);
        }

        // Bump overall tally back
        $before = (int) ($variant->overal_quantity_at_hand ?? 0);
        $after  = $before + (int) $item->quantity;
        $variant->update(['overal_quantity_at_hand' => $after]);

        SingleShopInventoryLog::create([
            'variant_id'      => $variant->id,
            'order_id'        => $order->id,
            'tenant_id'       => $order->tenant_id,
            'created_by'      => auth()->id(),
            'quantity_before' => $before,
            'quantity_after'  => $after,
            'quantity_change' => (int) $item->quantity,
            'reason'          => 'order_refunded',
            'notes'           => "Serial(s) returned to available — order #{$order->order_number}",
            'source'          => 'order',
        ]);
    }

    private function restoreMultiSerial($variant, $item, $order): void
    {
        $this->restoreSingleSerial($variant, $item, $order);
    }

    private function restoreRecipeIngredientsForRefund($variant, $item, $order): void
    {
        $product = $variant->product;
        if (!$product || !$product->hasRecipe()) return;

        $isSingleShop = tenant_is_single_shop($order->tenant_id);

        foreach ($product->recipe->ingredients as $ingredient) {
            $ingredientVariant = $ingredient->ingredientVariant;
            if (!$ingredientVariant) continue;

            $requiredQty = (float) $ingredient->quantity_required * (float) $item->quantity;

            // Build a synthetic item to reuse the strategy restorers
            $synthetic = (object) [
                'variant_id'  => $ingredientVariant->id,
                'quantity'    => $requiredQty,
                'batch_id'    => null,
                'serial_id'   => null,
                'inventory_data' => null,
            ];

            $this->restoreInventoryByStrategyForRefund($ingredientVariant, $synthetic, $order, $isSingleShop);
        }
    }

    private function recordOrderRefundTransaction($order, $method, float $amount, string $actor, string $reason): void
    {
        try {
            app('payment-transaction')->recordTransaction([
                'tenant_id'            => $order->tenant_id,
                'user_id'              => auth()->id(),
                'payment_method_id'    => $method->id,
                'transaction_type'     => 'WITHDRAWAL',
                'transaction_category' => 'REFUND',
                'amount'               => $amount,
                'currency_id'          => $method->currency_id ?? Currency::default()->id,
                'reference_table'      => 'orders',
                'reference_id'         => $order->id,
                'description'          => "{$actor} refunded " . currency_symbol() . number_format($amount, 2)
                                        . " for order {$order->order_number}. Reason: {$reason}",
                'notes'                => "Refund from {$method->name}",
                'metadata'             => [
                    'order_number'  => $order->order_number,
                    'order_id'      => $order->id,
                    'reason'        => $reason,
                    'transaction_nature' => 'ORDER_REFUND',
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to record order refund ledger entry: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'amount'   => $amount,
            ]);
        }
    }

    private function extractInventoryId($item): ?int
    {
        if (!is_object($item)) return null;
        $raw = $item->inventory_data ?? null;
        if (is_string($raw) && $raw !== '') $raw = json_decode($raw, true);
        return is_array($raw) ? ($raw['inventory_id'] ?? null) : null;
    }

    private function actorLabel($user = null): string
    {
        $user = $user ?? Auth::user();
        return $user ? "{$user->name} ({$user->email})" : 'System';
    }

    private function contextLabel(?Order $order): string
    {
        if (!$order) return '';
        $loc  = $order->location?->name ?? 'Unknown Location';
        $dept = $order->department?->name ?? 'Unknown Department';
        return " at {$loc} ({$dept})";
    }

    private function appendNote(?string $existing, string $line): string
    {
        $timestamp = now()->format('Y-m-d H:i:s');
        $entry     = "[{$timestamp}] {$line}";
        return $existing ? $existing . "\n" . $entry : $entry;
    }

// // ═══════════════════════════════════════════════════════════════════
// //  CANCEL — no payment reversal, but stock return if anything moved
// //  POST /pos-cancel/{id}   { status: 'cancelled' }
// //  (route already exists)
// // ═══════════════════════════════════════════════════════════════════
// public function cancel(Request $request, $id)
// {
//     $user     = Auth::user();
//     $tenantId = $user->tenant_id;

//     if (!$user->hasPermissionTo('cancel order')) {
//         return response()->json([
//             'success' => false,
//             'message' => __('payments.not_authorized'),
//         ], 403);
//     }

//     $order = Order::with(['orderItems', 'orderPayments'])
//         ->where('tenant_id', $tenantId)
//         ->findOrFail($id);

//     if (in_array($order->status, ['completed', 'cancelled', 'refunded'], true)) {
//         return response()->json([
//             'success' => false,
//             'message' => __('payments.cannot_cancel_status') ?? 'This order cannot be cancelled in its current state.',
//         ], 422);
//     }

//     // If any payment was recorded, this becomes a refund-flow issue —
//     // reject and tell the user to refund instead.
//     $hasPayments = $order->orderPayments->where('status', 'completed')->isNotEmpty();
//     if ($hasPayments) {
//         return response()->json([
//             'success' => false,
//             'message' => __('payments.use_refund_instead_of_cancel') ?? 'This order has payments. Use Refund to reverse them.',
//         ], 422);
//     }

//     DB::beginTransaction();
//     try {
//         $actor        = $this->actorLabel($user);
//         $customerName = $order->customer_name ?? 'the customer';
//         $context      = $this->contextLabel($order);

//         // If the order was POS-confirmed, stock may have been reserved
//         // but not yet depleted (depletion happens on completion). Nothing
//         // to restore in that case.
//         // If it somehow was depleted, restore it — the helpers are safe
//         // even if nothing was depleted (they no-op when inventory is
//         // already at the expected level).
//         $isSingleShop = tenant_is_single_shop($tenantId);

//         foreach ($order->orderItems as $item) {
//             $variant = ProductVariant::find($item->variant_id);
//             if (!$variant) continue;

//             $product = $variant->product;
//             if (!$product) continue;

//             $strategy = $product->resolvedInventoryStrategy();

//             if ($strategy === 'recipe') {
//                 $this->restoreRecipeIngredientsForRefund($variant, $item, $order);
//             } else {
//                 $this->restoreInventoryByStrategyForRefund($variant, $item, $order, $isSingleShop);
//             }
//         }

//         $order->notes = $this->appendNote(
//             $order->notes,
//             sprintf('%s cancelled order %s for %s%s.', $actor, $order->order_number, $customerName, $context)
//         );

//         $order->update([
//             'status' => 'cancelled',
//             'notes'  => $order->notes,
//         ]);

//         DB::commit();

//         return response()->json([
//             'success' => true,
//             'message' => __('passwords.success') ?? 'Order cancelled.',
//             'status'  => 'cancelled',
//         ]);

//     } catch (\Exception $e) {
//         DB::rollBack();
//         Log::error('Order cancel failed: ' . $e->getMessage(), ['order_id' => $id]);
//         return response()->json([
//             'success' => false,
//             'message' => __('passwords.cancel_failed') ?? 'Cancel failed.',
//         ], 500);
//     }
// }

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
        //
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
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function getPausedOrders(Request $request)
    {
        \Log::info('[PauseBuy] hit');

        try {
            $user = Auth::user();
            
            if (!$user) {
                \Log::warning('[PauseBuy] Unauthenticated request');
                return response()->json([
                    'orders' => [],
                    'message' => 'Unauthenticated',
                ], 401);
            }

            $tenantId = $user->tenant_id;
            $locationId = $user->location_id ?? 1;
            
            $selectedDepartmentId = $request->input('department', '');
            
            if (empty($selectedDepartmentId)) {
                return response()->json([
                    'orders' => [],
                    'message' => 'Please select a department first',
                ]);
            }

            $orders = Order::with(['orderItems.variant.product'])
                ->where('tenant_id', $tenantId)
                ->where('location_id', $locationId)
                ->where('source', 'pos')
                ->where('status', 'confirmed')
                ->where('paid_amount', 0)
                ->orderByDesc('created_at')
                ->limit(20)
                ->get();

            // \Log::info('[PauseBuy] found ' . $orders->count());

            $result = $orders->map(function ($order) use ($selectedDepartmentId, $locationId) {
                $items = $order->orderItems->map(function ($item) use ($selectedDepartmentId, $locationId, $order) { // ✅ Pass $order here
                    $variant = $item->variant;
                    $product = $variant?->product;

                    // ✅ Get inventory for this variant in the selected department
                    $inventoryId = null;
                    $departmentId = null;
                    $quantityAvailable = 0;

                    if ($variant) {
                        $inventory = $variant->inventory()
                            ->where('department_id', $selectedDepartmentId)
                            ->where('location_id', $locationId)
                            ->first();
                        
                        if ($inventory) {
                            $inventoryId = $inventory->id;
                            $departmentId = $inventory->department_id;
                            $quantityAvailable = (int) $inventory->quantity_allocated;
                        }
                    }

                    // Decode JSON blobs
                    $taxes = [];
                    if (!empty($item->tax_data)) {
                        $decoded = is_array($item->tax_data)
                            ? $item->tax_data
                            : json_decode($item->tax_data, true);
                        $taxes = is_array($decoded) ? $decoded : [];
                    }

                    $promotions = [];
                    if (!empty($item->promotion_data)) {
                        $decoded = is_array($item->promotion_data)
                            ? $item->promotion_data
                            : json_decode($item->promotion_data, true);
                        $promotions = is_array($decoded) ? $decoded : [];
                    }

                    // ✅ Get inventory strategy from product
                    $strategy = $product?->inventory_strategy ?? 'quantity';

                    // ✅ Get batch and serial data
                    $batchId = $item->batch_id ?? null;
                    $batchNumber = $item->batch_number ?? null;
                    $serialId = $item->serial_id ?? null;
                    $serialNumber = $item->serial_number ?? null;

                    // ✅ If serial_id exists, verify it's still available
                    $serialAvailable = true;
                    if ($serialId) {
                        $serial = \App\Models\SerialNumber::where('id', $serialId)
                            ->where('status', 'available')
                            ->where('tenant_id', $order->tenant_id) // ✅ Now $order is in scope
                            ->exists();
                        $serialAvailable = $serial;
                    }

                    // Image
                    $imageUrl = asset('assets/media/stock/ecommerce/2.png');
                    if ($variant) {
                        $raw = $variant->image_url ?? $product?->image_url ?? null;
                        if ($raw) {
                            try {
                                $imageUrl = productVariantImage($raw);
                            } catch (\Exception $e) {
                                // Keep default
                            }
                        }
                    }

                    return [
                        'id'                 => $item->id,
                        'variant_id'         => $item->variant_id,
                        'name'               => $item->item_name,
                        'item_name'          => $item->item_name,
                        'sku'                => $item->sku ?? '',
                        'unit_price'         => (float) $item->unit_price,
                        'price'              => (float) $item->unit_price,
                        'quantity'           => (int)   $item->quantity,
                        'quantity_available' => $quantityAvailable,
                        'image_url'          => $imageUrl,
                        'taxes'              => $taxes,
                        'promotions'         => $promotions,
                        'tax_amount'         => (float) $item->tax_amount,
                        'discount'           => (float) $item->discount,
                        'total'              => (float) $item->total_price,
                        'inventory_id'       => $inventoryId,
                        'department_id'      => $departmentId,
                        // ✅ NEW: Add strategy, batch, and serial data
                        'strategy'           => $strategy,
                        'batch_id'           => $batchId,
                        'batch_number'       => $batchNumber,
                        'serial_id'          => $serialId,
                        'serial_number'      => $serialNumber,
                        'serial_available'   => $serialAvailable,
                    ];
                })->values()->toArray();

                return [
                    'id'            => $order->id,
                    'order_id'      => $order->id,
                    'order_number'  => $order->order_number,
                    'customer_id'   => $order->customer_id,
                    'customer_name' => $order->customer_name ?? __('pagination.guest'),
                    'subtotal'      => (float) $order->subtotal,
                    'discount'      => (float) $order->discount_total,
                    'tax'           => (float) $order->tax_total,
                    'total'         => (float) $order->total,
                    'source'        => $order->source,
                    'created_at'    => $order->created_at?->toISOString(),
                    'items'         => $items,
                ];
            });

            return response()->json(['orders' => $result]);

        } catch (\Exception $e) {
            \Log::error('[PauseBuy] error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json([
                'orders' => [],
                'message' => __('pagination.error_loading_orders'),
                'debug' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }


}