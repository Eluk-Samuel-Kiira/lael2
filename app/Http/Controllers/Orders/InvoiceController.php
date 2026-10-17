<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Models\{ Invoice, Order, OrderPayment, ProductVariant, InventoryAdjustments,
                    InventoryTransactions, SingleShopInventoryLog, PaymentMethod, Currency, Location,
                    InvoicePayment, InventoryItems, PurchaseReceiptItem, BatchLog, SerialNumber };
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{ Auth, DB, Log, Mail };
use Illuminate\Support\Str;
use App\Services\MessagingService;

class InvoiceController extends Controller
{

    public function index(Request $request)
    {
        $user     = Auth::user();
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

        $isAdmin         = $user->hasAnyRole(['super_admin', 'admin']);
        $userLocationIds = $user->locations()->pluck('locations.id')->toArray();

        // ── Base query (filters applied below, mirrors data()) ─────────
        $query = Invoice::with([
                'order.location',
                'order.department.location',
                'customer',
                'createdBy:id,name',
            ])
            ->where('tenant_id', $tenantId)
            ->latest();

        if (!$isAdmin) {
            $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                ->orWhereHas('order', fn($oq) => $oq->where('created_by', $user->id));
            });
        }

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

        // ── Location filter — mirrors the `correct_location` accessor ──
        if ($request->filled('location_id')) {
            $requestedLocationId = (int) $request->input('location_id');

            $allowedToFilter = $isAdmin
                || in_array($requestedLocationId, $userLocationIds, true);

            if ($allowedToFilter) {
                $query->whereHas('order', function ($oq) use ($requestedLocationId) {
                    $oq->where(function ($inner) use ($requestedLocationId) {
                        $inner->whereHas('department', fn($dq) => $dq->where('location_id', $requestedLocationId))
                            ->orWhere('location_id', $requestedLocationId);
                    });
                });
            }
        }

        // ── NEW: issue-date range (matches data()) ─────────────────────
        if ($issueFrom = $request->input('issue_date_from')) {
            $query->whereDate('issue_date', '>=', $issueFrom);
        }
        if ($issueTo = $request->input('issue_date_to')) {
            $query->whereDate('issue_date', '<=', $issueTo);
        }

        // ── Mark overdue (scoped) — only on the actual page load ──────
        $this->markOverdueInvoices($tenantId, $user, $isAdmin);

        $perPage = $request->input('per_page', 15);
        $allowedPerPage = [15, 25, 50, 100];
        if (!in_array((int) $perPage, $allowedPerPage, true)) {
            $perPage = 15;
        }
        $perPage = (int) $perPage;

        $invoices = $query->paginate($perPage)
            ->appends([
                'search'          => $request->search,
                'status'          => $request->status,
                'location_id'     => $request->location_id,
                'issue_date_from' => $request->issue_date_from,
                'issue_date_to'   => $request->issue_date_to,
                'per_page'        => $perPage,
            ]);

        $locationsQuery = Location::where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->orderBy('name');

        if (!$isAdmin) {
            $locationsQuery->whereIn('id', $userLocationIds);
        }

        $locations = $locationsQuery->get();

        // ── Legacy LiveBlade partial reload (keep for backwards compat) ─
        $bladeToReload = $request->query('bladeFileToReload');
        if ($request->ajax() && $bladeToReload === 'reloadInvoiceComponent') {
            return view('orders.invoice.component', [
                'invoices'  => $invoices,
                'locations' => $locations,
            ])->render();
        }

        // ── Initial filter state for the JS-driven view ────────────────
        $initialFilters = [
            'search'          => (string) $request->get('search', ''),
            'status'          => (string) $request->get('status', ''),
            'location_id'     => (string) $request->get('location_id', ''),
            'issue_date_from' => (string) $request->get('issue_date_from', ''),
            'issue_date_to'   => (string) $request->get('issue_date_to', ''),
            'per_page'        => $perPage,
        ];

        return view('orders.invoice-index', [
            'invoices'        => $invoices,                        // kept for backward compat / legacy includes
            'initialInvoices' => $invoices->getCollection(),       // for the modals hub (if you use Option B)
            'locations'       => $locations,
            'initialFilters'  => $initialFilters,
        ]);
    }

    /**
     * Flip outstanding, past-due invoices to the OVERDUE status.
     *
     * Called from index() only — NOT from data(). The JSON list endpoint
     * must not mutate the DB on every debounced keystroke.
     *
     * Scoped by tenant and by the same visibility rule the list uses:
     * non-admins only flip invoices they created or whose order they created.
     */
    private function markOverdueInvoices(int $tenantId, $user, bool $isAdmin): void
    {
        $query = Invoice::where('tenant_id', $tenantId)
            ->outstanding()
            ->whereDate('due_date', '<', now());

        if (!$isAdmin) {
            $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                ->orWhereHas('order', fn($oq) => $oq->where('created_by', $user->id));
            });
        }

        $query->update(['status' => Invoice::STATUS_OVERDUE]);
    }

    /**
     * JSON list endpoint for the invoice table.
     *
     * Mirrors index()'s filters, visibility rules, and sort order, but
     * returns JS-shaped rows + summary + pagination. Does NOT mutate
     * anything — safe to call on every keystroke.
     *
     * GET /invoices/data
     */
    public function data(Request $request)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('view invoice')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ], 403);
        }

        $isAdmin         = $user->hasAnyRole(['super_admin', 'admin']);
        $userLocationIds = $user->locations()->pluck('locations.id')->toArray();

        // ── Filters ────────────────────────────────────────────────
        $search         = trim((string) $request->get('search', ''));
        $status         = (string) $request->get('status', '');
        $locationId     = $request->get('location_id');
        $issueFrom      = $request->get('issue_date_from');
        $issueTo        = $request->get('issue_date_to');

        $perPage = (int) $request->get('per_page', 15);
        if (!in_array($perPage, [15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        // ── Base query ─────────────────────────────────────────────
        $query = Invoice::with([
                'order.location',
                'order.department.location',
                'customer',
                'createdBy:id,name',
            ])
            ->where('tenant_id', $tenantId);

        if (!$isAdmin) {
            $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                ->orWhereHas('order', fn($oq) => $oq->where('created_by', $user->id));
            });
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                ->orWhere('billing_name', 'like', "%{$search}%")
                ->orWhere('billing_email', 'like', "%{$search}%")
                ->orWhere('billing_phone', 'like', "%{$search}%");
            });
        }

        if ($status !== '' && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($locationId) {
            $requestedLocationId = (int) $locationId;
            $allowedToFilter = $isAdmin || in_array($requestedLocationId, $userLocationIds, true);
            if ($allowedToFilter) {
                $query->whereHas('order', function ($oq) use ($requestedLocationId) {
                    $oq->where(function ($inner) use ($requestedLocationId) {
                        $inner->whereHas('department', fn($dq) => $dq->where('location_id', $requestedLocationId))
                            ->orWhere('location_id', $requestedLocationId);
                    });
                });
            }
        }

        if ($issueFrom) {
            $query->whereDate('issue_date', '>=', $issueFrom);
        }
        if ($issueTo) {
            $query->whereDate('issue_date', '<=', $issueTo);
        }

        // ── Summary from the FILTERED set (before pagination) ─────
        $allFiltered = (clone $query)->get([
            'id', 'status', 'total', 'balance_due', 'amount_paid',
        ]);

        $summary = [
            'total'            => $allFiltered->count(),
            'draft'            => $allFiltered->where('status', Invoice::STATUS_DRAFT)->count(),
            'sent'             => $allFiltered->where('status', Invoice::STATUS_SENT)->count(),
            'viewed'           => $allFiltered->where('status', Invoice::STATUS_VIEWED)->count(),
            'partially_paid'   => $allFiltered->where('status', Invoice::STATUS_PARTIALLY_PAID)->count(),
            'paid'             => $allFiltered->where('status', Invoice::STATUS_PAID)->count(),
            'overdue'          => $allFiltered->where('status', Invoice::STATUS_OVERDUE)->count(),
            'void'             => $allFiltered->where('status', Invoice::STATUS_VOID)->count(),
            'cancelled'        => $allFiltered->where('status', Invoice::STATUS_CANCELLED)->count(),
            'total_amount'     => (float) $allFiltered->sum('total'),
            'total_paid'       => (float) $allFiltered->sum('amount_paid'),
            'total_outstanding'=> (float) $allFiltered->sum('balance_due'),
        ];

        // ── Paginate ───────────────────────────────────────────────
        $paginator = $query->latest()->paginate($perPage);

        // ── Shape rows for JS ──────────────────────────────────────
        $rows = collect($paginator->items())->map(function (Invoice $invoice) {
            $locationName = $invoice->order?->correct_location?->name
                ?? $invoice->order?->location?->name
                ?? null;

            return [
                'id'                 => $invoice->id,
                'invoice_number'     => $invoice->invoice_number,
                'order_number'       => $invoice->order?->order_number,
                'order_id'           => $invoice->order_id,

                'customer_name'      => $invoice->billing_name ?? '—',
                'customer_email'     => $invoice->billing_email,
                'customer_initial'   => strtoupper(substr($invoice->billing_name ?? '?', 0, 1)),

                'location_name'      => $locationName ?? '—',
                'issue_date'         => optional($invoice->issue_date)->format('Y-m-d'),
                'issue_date_human'   => optional($invoice->issue_date)->format('d M Y'),
                'due_date'           => optional($invoice->due_date)->format('Y-m-d'),
                'due_date_human'     => optional($invoice->due_date)->format('d M Y'),

                'status'             => $invoice->status,
                'status_label'       => $invoice->status_label,
                'status_color'       => $invoice->status_color,
                'status_icon'        => $invoice->status_icon,
                'is_overdue'         => $invoice->isOverdue(),
                'is_draft'           => $invoice->isDraft(),
                'is_payable'         => $invoice->isPayable(),

                'total'              => (float) $invoice->total,
                'total_formatted'    => currency_symbol() . number_format((float) $invoice->total, 2),
                'amount_paid'        => (float) $invoice->amount_paid,
                'balance_due'        => (float) $invoice->balance_due,
                'balance_due_formatted' => currency_symbol() . number_format((float) $invoice->balance_due, 2),

                'created_by_name'    => $invoice->createdBy?->name ?? '—',
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
                'search'          => $search,
                'status'          => $status,
                'location_id'     => $locationId,
                'issue_date_from' => $issueFrom,
                'issue_date_to'   => $issueTo,
            ],
        ]);
    }

    /**
     * Full payload for the right-side drawer.
     *
     * Returns everything the drawer needs in one round-trip: customer,
     * order, line items, payments, sends, notes. No nested Blade includes,
     * no per-row modals in the DOM.
     *
     * GET /invoices/{id}/details
     */
    public function details(Request $request, $id)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('view invoice')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ], 403);
        }

        $isAdmin = $user->hasAnyRole(['super_admin', 'admin']);

        $query = Invoice::with([
            'order.orderItems.variant',
            'order.location',
            'order.department',
            'customer',
            'payments.paymentMethod',
            'payments.processedBy:id,name',
            'sends',
            'creator:id,name',
            'voidedBy:id,name',
        ])->where('tenant_id', $tenantId);

        if (!$isAdmin) {
            $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                ->orWhereHas('order', fn($oq) => $oq->where('created_by', $user->id));
            });
        }

        $invoice = $query->find($id);

        if (!$invoice) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ], 404);
        }

        // ── Items ─────────────────────────────────────────────────
        $items = ($invoice->order?->orderItems ?? collect())->map(function ($item) {
            return [
                'id'            => $item->id,
                'item_name'     => $item->item_name,
                'variant_name'  => $item->variant?->name,
                'sku'           => $item->sku ?? $item->variant?->sku,
                'quantity'      => (float) $item->quantity,
                'unit_price'    => (float) $item->unit_price,
                'unit_price_formatted' => currency_symbol() . number_format((float) $item->unit_price, 2),
                'discount'      => (float) ($item->discount ?? 0),
                'discount_formatted'   => currency_symbol() . number_format((float) ($item->discount ?? 0), 2),
                'tax_amount'    => (float) ($item->tax_amount ?? 0),
                'tax_formatted'        => currency_symbol() . number_format((float) ($item->tax_amount ?? 0), 2),
                'total_price'   => (float) $item->total_price,
                'total_formatted'      => currency_symbol() . number_format((float) $item->total_price, 2),
            ];
        })->values();

        // ── Payments ──────────────────────────────────────────────
        $payments = $invoice->payments->map(function ($p) {
            return [
                'id'                => $p->id,
                'amount'            => (float) $p->amount,
                'amount_formatted'  => currency_symbol() . number_format((float) $p->amount, 2),
                'method_name'       => $p->paymentMethod?->name,
                'transaction_id'    => $p->transaction_id,
                'status'            => $p->status,
                'status_label'      => $p->status_label ?? ucfirst($p->status),
                'status_color'      => $p->status_color ?? 'secondary',
                'type'              => $p->type,
                'type_label'        => $p->type_label ?? ucfirst($p->type ?? '—'),
                'processed_by_name' => $p->processedBy?->name,
                'payment_date'      => optional($p->payment_date ?? $p->created_at)->format('Y-m-d H:i'),
                'notes'             => $p->notes,
            ];
        })->values();

        // ── Sends ─────────────────────────────────────────────────
        $sends = $invoice->sends->map(function ($s) {
            return [
                'id'         => $s->id,
                'channel'    => $s->channel,
                'recipient'  => $s->recipient,
                'status'     => $s->status,
                'sent_at'    => optional($s->sent_at)->format('Y-m-d H:i'),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'invoice' => [
                'id'              => $invoice->id,
                'invoice_number'  => $invoice->invoice_number,
                'status'          => $invoice->status,
                'status_label'    => $invoice->status_label,
                'status_color'    => $invoice->status_color,
                'status_icon'     => $invoice->status_icon,
                'is_paid'         => $invoice->isPaid(),
                'is_partially_paid' => $invoice->isPartiallyPaid(),
                'is_overdue'      => $invoice->isOverdue(),
                'is_draft'        => $invoice->isDraft(),
                'is_payable'      => $invoice->isPayable(),
                'is_void'         => $invoice->isVoid(),

                'permissions' => [
                    'can_send'      => $user->can('send invoice')
                                        && !in_array($invoice->status, ['void', 'paid'], true),
                    'can_pay'       => $user->can('record invoice payment')
                                        && $invoice->isPayable(),
                    'can_void'      => $user->can('void invoice')
                                        && !in_array($invoice->status, ['void', 'paid', 'cancelled'], true),
                    'can_discount'  => $user->can('edit invoice')
                                        && $invoice->isDraft(),
                    'can_edit_date' => $user->can('edit invoice')
                                        && !in_array($invoice->status, ['paid', 'void', 'cancelled'], true),
                    'can_delete'    => $user->can('delete invoice')
                                        && in_array($invoice->status, ['draft', 'void'], true),
                    'can_download'  => $user->can('download invoice'),
                ],

                'billing' => [
                    'name'    => $invoice->billing_name,
                    'initial' => strtoupper(substr($invoice->billing_name ?? '?', 0, 1)),
                    'email'   => $invoice->billing_email,
                    'phone'   => $invoice->billing_phone,
                    'address' => $invoice->billing_address,
                    'tax_id'  => $invoice->tax_id,
                ],

                'issue_date'      => optional($invoice->issue_date)->format('Y-m-d'),
                'issue_date_human'=> optional($invoice->issue_date)->format('d M Y, h:i A'),
                'due_date'        => optional($invoice->due_date)->format('Y-m-d'),
                'due_date_human'  => optional($invoice->due_date)->format('d M Y'),
                'sent_at'         => optional($invoice->sent_at)->format('Y-m-d H:i'),
                'viewed_at'       => optional($invoice->viewed_at)->format('Y-m-d H:i'),
                'paid_at'         => optional($invoice->paid_at)->format('Y-m-d H:i'),
                'voided_at'       => optional($invoice->voided_at)->format('Y-m-d H:i'),

                'subtotal'        => (float) $invoice->subtotal,
                'subtotal_formatted'   => currency_symbol() . number_format((float) $invoice->subtotal, 2),
                'discount_total'  => (float) $invoice->discount_total,
                'discount_formatted'   => currency_symbol() . number_format((float) $invoice->discount_total, 2),
                'tax_total'       => (float) $invoice->tax_total,
                'tax_formatted'        => currency_symbol() . number_format((float) $invoice->tax_total, 2),
                'total'           => (float) $invoice->total,
                'total_formatted'      => currency_symbol() . number_format((float) $invoice->total, 2),
                'amount_paid'     => (float) $invoice->amount_paid,
                'amount_paid_formatted'=> currency_symbol() . number_format((float) $invoice->amount_paid, 2),
                'balance_due'     => (float) $invoice->balance_due,
                'balance_due_formatted'=> currency_symbol() . number_format((float) $invoice->balance_due, 2),

                'notes'           => $invoice->notes,
                'terms'           => $invoice->terms,
                'created_by_name' => $invoice->creator?->name,
                'voided_by_name'  => $invoice->voidedBy?->name,

                'order' => $invoice->order ? [
                    'id'             => $invoice->order->id,
                    'order_number'   => $invoice->order->order_number,
                    'status'         => $invoice->order->status,
                    'location_name'  => $invoice->order->correct_location?->name ?? $invoice->order->location?->name,
                    'department_name'=> $invoice->order->department?->name,
                    'show_url'       => route('orders.show', $invoice->order->id),
                ] : null,
            ],
            'items'    => $items,
            'payments' => $payments,
            'sends'    => $sends,
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

        // ✅ HARD BLOCK: paid / partially_paid must go through recordPayment()
        if (in_array($validated['status'], ['paid', 'partially_paid'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Paid statuses can only be set through the payment endpoint. '
                        . 'Use recordPayment() so the payment is logged against a real account.',
            ], 422);
        }

        // ✅ HARD BLOCK: draft invoices cannot be marked as paid/sent without sending
        if ($invoice->isDraft() && in_array($validated['status'], ['paid', 'partially_paid'], true)) {
            return response()->json([
                'success' => false,
                'message' => __('payments.invoice_not_sent_yet'),
            ], 422);
        }

        if ($invoice->isPaid() || $invoice->isVoid()) {
            return response()->json([
                'success' => false,
                'message' => __('payments.invoice_locked'),
            ], 422);
        }

        $actor          = $this->actorLabel($user);
        $previousStatus = $invoice->status;
        $newStatus      = $validated['status'];

        $invoice->notes = $this->appendNote(
            $invoice->notes,
            sprintf(
                '%s changed invoice %s status: %s → %s.',
                $actor,
                $invoice->invoice_number,
                $previousStatus,
                $newStatus
            )
        );

        $invoice->update([
            'status' => $newStatus,
            'notes'  => $invoice->notes,
        ]);

        Log::info("[Invoice] {$actor} changed status of invoice {$invoice->invoice_number} "
            . "from {$previousStatus} to {$newStatus}.", [
            'invoice_id' => $invoice->id,
        ]);

        return response()->json([
            'success'  => true,
            'message'  => __('payments.invoice_status_updated'),
            'status'   => $invoice->status,
            'narrative'=> "{$actor} changed invoice {$invoice->invoice_number}: {$previousStatus} → {$newStatus}.",
        ]);
    }

    /**
     * Build a human-readable sentence fragment for "who did what to whom".
     */
    private function actorLabel($user = null): string
    {
        $user = $user ?? Auth::user();
        return $user ? "{$user->name} ({$user->email})" : 'System';
    }

    /**
     * Build the narrative sentence used on InventoryTransactions,
     * InventoryAdjustments, SingleShopInventoryLog and BatchLog rows.
     *
     * @param  string       $verb      e.g. "sent invoice", "voided invoice", "restored"
     * @param  string       $direction 'out' or 'in'
     */
    private function buildInventoryNarrative(
        string $verb,
        string $direction,
        $variant,
        $item,
        $order,
        $inventory = null,
        ?int $before = null,
        ?int $after = null,
        ?Invoice $invoice = null
    ): string {
        $user         = Auth::user();
        $actor        = $this->actorLabel($user);
        $customerName = $order->customer_name
            ?? $order->customer?->name
            ?? $invoice?->billing_name
            ?? 'walk-in customer';

        // Location / department — prefer the inventory row, fall back to order
        $location = $inventory?->itemLocation?->name
            ?? $order->location?->name
            ?? 'Unknown Location';
        $department = $inventory?->departmentItem?->name
            ?? $order->department?->name
            ?? 'Unknown Department';

        $qty     = is_object($item) ? ($item->quantity ?? 0) : ($item['quantity'] ?? 0);
        $sku     = $variant->sku ?? 'N/A';
        $name    = $variant->name ?? 'Unknown Item';
        $arrow   = $direction === 'out' ? '↓' : '↑';

        $stateChange = ($before !== null && $after !== null)
            ? " Stock: {$before} → {$after} units."
            : '';

        $invPart = $invoice
            ? " for invoice {$invoice->invoice_number}"
            : " for order {$order->order_number}";

        $verbPhrase = match (true) {
            str_contains($verb, 'sent')      => "{$actor} sent invoice to {$customerName}",
            str_contains($verb, 'voided')    => "{$actor} voided invoice for {$customerName}",
            str_contains($verb, 'restored')  => "{$actor} restored stock after voiding invoice for {$customerName}",
            default                          => "{$actor} {$verb} for {$customerName}",
        };

        return sprintf(
            '%s %s %s unit(s) of "%s" (SKU: %s) %s %s (%s)%s.%s',
            $verbPhrase . ' —',
            $arrow,
            $qty,
            $name,
            $sku,
            $direction === 'out' ? 'out of' : 'back into',
            $location,
            $department,
            $invPart,
            $stateChange
        );
    }

    /**
     * Build a readable location/department suffix for notes.
     */
    private function contextLabel(?Order $order): string
    {
        if (!$order) return '';
        $loc = $order->location?->name ?? 'Unknown Location';
        $dept = $order->department?->name ?? 'Unknown Department';
        return " at {$loc} ({$dept})";
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
        $invoice = Invoice::with('order')
            ->where('tenant_id', $user->tenant_id)
            ->findOrFail($id);

        // Single gate — blocks draft, paid, void, cancelled, and anything
        // else not in the payable set. Message tells the user why.
        if ($reason = $invoice->paymentBlockReason()) {
            return response()->json([
                'success' => false,
                'message' => $reason,
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
            // ─── Who, what, where ─────────────────────────────────────────
            $actor         = $this->actorLabel($user);
            $customerName  = $invoice->billing_name ?? $invoice->customer?->name ?? 'the customer';
            $context       = $this->contextLabel($invoice->order);
            $orderNumber   = $invoice->order?->order_number ?? 'N/A';
            $methodName    = $paymentMethod->name ?? ('Method #' . $paymentMethod->id);
            $currency      = $invoice->currency ?? 'UGX';

            $balanceBefore = (float) $invoice->balance_due;
            $paidBefore    = (float) $invoice->amount_paid;
            $balanceAfter  = max(0, $balanceBefore - $amount);
            $paidAfter     = $paidBefore + $amount;
            $isFullyPaid   = $balanceAfter <= 0;

            // ─── The human-readable story ──────────────────────────────────
            $narrative = sprintf(
                '%s received %s %s from %s for invoice %s via %s%s. '
                . 'Invoice was %s before this payment; now %s. '
                . 'Amount paid: %s → %s. Balance: %s → %s. Order: %s.',
                $actor,
                number_format($amount, 2),
                $currency,
                $customerName,
                $invoice->invoice_number,
                $methodName,
                $context,
                $isFullyPaid ? 'partially paid' : ($paidBefore > 0 ? 'partially paid' : 'sent'),
                $isFullyPaid ? 'FULLY PAID' : 'still outstanding',
                number_format($paidBefore, 2),
                number_format($paidAfter, 2),
                number_format($balanceBefore, 2),
                number_format($balanceAfter, 2),
                $orderNumber
            );

            Log::info("[Invoice] {$narrative}", [
                'invoice_id'      => $invoice->id,
                'tenant_id'       => $user->tenant_id,
                'user_id'         => $user->id,
                'amount'          => $amount,
                'payment_method'  => $methodName,
                'balance_before'  => $balanceBefore,
                'balance_after'   => $balanceAfter,
                'fully_paid'      => $isFullyPaid,
            ]);

            // 1. Record deposit (ledger entry)
            $this->recordDeposit($invoice, $paymentMethod, $amount);

            // 2. Create invoice payment record — with explicit narrative
            $invoicePayment = InvoicePayment::create([
                'invoice_id'         => $invoice->id,
                'payment_method_id'  => $paymentMethod->id,
                'processed_by'       => $user->id,
                'amount'             => $amount,
                'transaction_id'     => $validated['transaction_id'] ?? (string) Str::uuid(),
                'status'             => 'completed',
                'type'               => $isFullyPaid ? 'full' : 'partial',
                'payment_date'       => now(),
                'currency_code'      => $currency,
                'notes'              => $validated['notes']
                    ?? sprintf(
                        '%s received %s %s from %s for invoice %s via %s. Balance after payment: %s %s.',
                        $actor,
                        number_format($amount, 2),
                        $currency,
                        $customerName,
                        $invoice->invoice_number,
                        $methodName,
                        number_format($balanceAfter, 2),
                        $currency
                    ),
            ]);

            // 3. Update payment method balance
            $paymentMethod->current_balance += $amount;
            $paymentMethod->save();

            // 4. Update invoice — narrate on the invoice notes too
            $invoice->notes = $this->appendNote($invoice->notes, $narrative);

            $invoice->update([
                'amount_paid' => $paidAfter,
                'balance_due' => $balanceAfter,
                'status'      => $isFullyPaid ? 'paid' : 'partially_paid',
                'paid_at'     => $isFullyPaid ? now() : null,
                'notes'       => $invoice->notes,
            ]);

            // 5. Update order if exists — narrate on order notes as well
            if ($invoice->order) {
                $order             = $invoice->order;
                $orderNewPaid      = $order->paid_amount + $amount;
                $orderNewBalance   = max(0, $order->total - $orderNewPaid);
                $orderIsFullyPaid  = $orderNewBalance <= 0;
                $previousStatus    = $order->status;

                $order->update([
                    'paid_amount'  => $orderNewPaid,
                    'balance_due'  => $orderNewBalance,
                    'status'       => $orderIsFullyPaid ? 'completed' : ($order->status ?: 'confirmed'),
                    'completed_at' => $orderIsFullyPaid ? now() : $order->completed_at,
                ]);

                if ($orderIsFullyPaid && $previousStatus !== 'completed') {
                    Log::info("[Order] {$actor} completed order {$order->order_number} via invoice payment of "
                        . number_format($amount, 2) . " {$currency} for invoice {$invoice->invoice_number}. "
                        . "Order status: {$previousStatus} → completed. Total paid: "
                        . number_format($orderNewPaid, 2) . ' / ' . number_format($order->total, 2) . ".", [
                        'order_id'       => $order->id,
                        'order_number'   => $order->order_number,
                        'invoice_id'     => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'total_paid'     => $orderNewPaid,
                        'total'          => $order->total,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success'         => true,
                'message'         => $isFullyPaid
                    ? __('payments.invoice_fully_paid', ['number' => $invoice->invoice_number])
                    : __('payments.partial_payment_recorded', ['number' => $invoice->invoice_number]),
                'narrative'       => $narrative,
                'status'          => $invoice->status,
                'amount_paid'     => $invoice->amount_paid,
                'balance_due'     => $invoice->balance_due,
                'order_status'    => $invoice->order?->status ?? null,
                'order_completed' => $invoice->order?->status === 'completed',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Invoice payment failed: ' . $e->getMessage(), [
                'invoice_id' => $id,
                'tenant_id'  => $user->tenant_id,
                'user_id'    => $user->id,
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
        $user         = Auth::user();
        $actor        = $this->actorLabel($user);
        $customerName = $invoice->billing_name ?? $invoice->customer?->name ?? 'the customer';
        $currency     = $invoice->currency ?? 'UGX';
        $context      = $this->contextLabel($invoice->order);

        $transactionData = [
            'tenant_id'           => $invoice->tenant_id,
            'user_id'             => $user->id,
            'payment_method_id'   => $paymentMethod->id,
            'transaction_type'    => 'DEPOSIT',
            'transaction_category'=> 'INVOICE',
            'amount'              => $amount,
            'currency_id'         => $paymentMethod->currency_id ?? Currency::default()->id,
            'reference_table'     => 'invoices',
            'reference_id'        => $invoice->id,
            'description'         => sprintf(
                '%s received %s %s from %s for invoice %s via %s%s.',
                $actor,
                number_format($amount, 2),
                $currency,
                $customerName,
                $invoice->invoice_number,
                $paymentMethod->name ?? 'payment method',
                $context
            ),
            'notes' => sprintf(
                'Deposit into %s. Invoice %s — customer: %s. Balance before: %s %s.',
                $paymentMethod->name ?? 'account',
                $invoice->invoice_number,
                $customerName,
                number_format((float) $invoice->balance_due, 2),
                $currency
            ),
            'metadata' => [
                'invoice_number' => $invoice->invoice_number,
                'order_number'   => $invoice->order?->order_number,
                'customer_name'  => $customerName,
                'customer_email' => $invoice->billing_email,
                'customer_phone' => $invoice->billing_phone,
                'recorded_by'    => $actor,
                'recorded_at'    => now()->toIso8601String(),
            ],
        ];

        app('payment-transaction')->recordTransaction($transactionData);
    }

    /**
     * Record an invoice discount as an ADJUSTMENT transaction in the ledger.
     *
     * ✅ Zero balance effect — a discount is not money entering or leaving an
     *    account. It reduces what the customer owes, so we log it for reporting
     *    (revenue leakage, discount reporting, audit trail) but we DO NOT touch
     *    the payment method's current_balance.
     *
     * Mirrors POSController::recordBargainDiscount() so reports treat both
     * sources of discounting the same way.
     *
     * @param  Invoice  $invoice
     * @param  float    $discountAmount   Positive number, in display currency
     * @param  string   $kind             'bargain' | 'promo' | 'manual'
     * @param  string|null $reason        Free-text reason the user gave
     * @param  string|null $accountKey    'invoice' (default) | 'order' — which
     *                                    row this discount is anchored to
     */
    private function recordDiscountAdjustment(
        Invoice $invoice,
        float $discountAmount,
        string $kind = 'manual',
        ?string $reason = null,
        string $accountKey = 'invoice'
    ): void {
        if ($discountAmount <= 0) {
            return;
        }

        try {
            $user         = Auth::user();
            $actor        = $this->actorLabel($user);
            $customerName = $invoice->billing_name
                ?? $invoice->customer?->name
                ?? __('pagination.walk_in_customer');
            $currency     = $invoice->currency ?? 'UGX';
            $context      = $this->contextLabel($invoice->order);
            $orderNumber  = $invoice->order?->order_number ?? 'N/A';

            // Anchor to the payment method if we can, so it shows up in
            // per-account reports. Fall back to the tenant's default.
            $paymentMethod = $invoice->payments()
                ->with('paymentMethod')
                ->latest()
                ->first()?->paymentMethod
                ?? PaymentMethod::where('tenant_id', $invoice->tenant_id)
                    ->orderBy('id')
                    ->first();

            if (!$paymentMethod) {
                Log::warning('[Invoice] Cannot record discount adjustment — no payment method available', [
                    'invoice_id' => $invoice->id,
                    'tenant_id'  => $invoice->tenant_id,
                ]);
                return;
            }

            // ✅ CRITICAL: freeze balance before and after to the SAME value.
            //    A discount has NO effect on the money sitting in an account.
            $currentBalance = $paymentMethod->current_balance;

            $subtotalBefore = $invoice->getRawSubtotal();
            $discountSoFar  = $invoice->getRawDiscountTotal();
            $taxTotal       = $invoice->getRawTaxTotal();
            $totalAfter     = $invoice->getRawTotal();

            $transactionData = [
                'tenant_id'            => $invoice->tenant_id,
                'user_id'              => $user->id,
                'payment_method_id'    => $paymentMethod->id,
                'transaction_type'     => 'ADJUSTMENT',
                'transaction_category' => 'ADJUSTMENT',
                'amount'               => $discountAmount,
                'currency_id'          => $paymentMethod->currency_id
                                        ?? Currency::default()->id,
                'reference_table'      => $accountKey === 'order' ? 'orders' : 'invoices',
                'reference_id'         => $accountKey === 'order'
                                        ? $invoice->order_id
                                        : $invoice->id,

                'description' => sprintf(
                    '%s granted a %s discount of %s %s to %s on invoice %s%s.',
                    $actor,
                    $kind,
                    number_format($discountAmount, 2),
                    $currency,
                    $customerName,
                    $invoice->invoice_number,
                    $context
                ),

                'notes' => $reason
                    ? "Reason: {$reason}"
                    : 'Discount applied to invoice — no money movement.',

                // ✅ No balance effect — this is purely informational
                'balance_before' => $currentBalance,
                'balance_after'  => $currentBalance,

                'metadata' => [
                    'invoice_id'               => $invoice->id,
                    'invoice_number'           => $invoice->invoice_number,
                    'order_id'                 => $invoice->order_id,
                    'order_number'             => $orderNumber,
                    'customer_id'              => $invoice->customer_id,
                    'customer_name'            => $customerName,
                    'subtotal_before_discount' => from_base_currency($subtotalBefore),
                    'total_discount_on_invoice'=> from_base_currency($discountSoFar),
                    'tax_total'                => from_base_currency($taxTotal),
                    'invoice_total_after'      => from_base_currency($totalAfter),
                    'discount_type'            => strtoupper($kind),
                    'discount_amount'          => $discountAmount,
                    'discount_reason'          => $reason,
                    'transaction_nature'       => 'INVOICE_DISCOUNT_RECORD',
                    'processed_by_id'          => $user->id,
                    'processed_by_name'        => $actor,
                    'is_discount'              => true,
                    'discount_category'        => $kind,
                    'balance_effect'           => 'none',  // ✅ explicit
                ],
            ];

            app('payment-transaction')->recordTransaction($transactionData);

            Log::info("[Invoice] {$actor} recorded a {$kind} discount of "
                . number_format($discountAmount, 2) . " {$currency} on invoice "
                . "{$invoice->invoice_number} for {$customerName}. "
                . "Ledger entry written as ADJUSTMENT — no balance effect.", [
                'invoice_id' => $invoice->id,
                'amount'     => $discountAmount,
                'kind'       => $kind,
            ]);

        } catch (\Throwable $e) {
            Log::error('Failed to record invoice discount adjustment: ' . $e->getMessage(), [
                'invoice_id' => $invoice->id,
                'amount'     => $discountAmount,
            ]);
            throw $e;
        }
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
            $actor        = $this->actorLabel($user);
            $customerName = $invoice->billing_name ?? $invoice->customer?->name ?? 'the customer';
            $context      = $this->contextLabel($invoice->order);
            $orderNumber  = $invoice->order?->order_number ?? 'N/A';
            $wasSent      = in_array($invoice->status, [
                Invoice::STATUS_SENT, Invoice::STATUS_VIEWED, Invoice::STATUS_OVERDUE,
            ]);
            $previousStatus = $invoice->status;
            $reason         = $request->input('reason', 'No reason provided');

            // Log the story BEFORE restocking
            Log::info("[Invoice] {$actor} is VOIDING invoice {$invoice->invoice_number} for {$customerName}{$context}. "
                . "Reason: {$reason}. Previous status: {$previousStatus}. "
                . ($wasSent ? 'Stock will be restored.' : 'No stock to restore (invoice was never sent).'), [
                'invoice_id'   => $invoice->id,
                'order_number' => $orderNumber,
                'reason'       => $reason,
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

            // Append the void story to notes
            $invoice->notes = $this->appendNote(
                $invoice->notes,
                sprintf(
                    '%s voided invoice %s for %s%s. Reason: %s. Previous status: %s → Void. %s',
                    $actor,
                    $invoice->invoice_number,
                    $customerName,
                    $context,
                    $reason,
                    $previousStatus,
                    $wasSent ? 'Stock was restored for all line items.' : 'No stock movement (invoice was never sent).'
                )
            );

            $invoice->update([
                'status'    => Invoice::STATUS_VOID,
                'voided_at' => now(),
                'voided_by' => $user->id,
                'notes'     => $invoice->notes,
            ]);

            if ($invoice->order) {
                $invoice->order->update(['status' => 'cancelled']);
            }

            DB::commit();

            return response()->json([
                'success'     => true,
                'reload'      => false,
                'componentId' => 'reloadInvoiceComponent',
                'refresh'     => true,
                'message'     => __('payments.invoice_voided'),
                'narrative'   => sprintf(
                    '%s voided invoice %s. Stock restored: %s.',
                    $actor,
                    $invoice->invoice_number,
                    $wasSent ? 'yes' : 'no'
                ),
                'redirect'    => route('invoices.index'),
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
        $inventoryData = $this->extractInventoryData($item);
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
    //     $inventoryData = $this->extractInventoryData($item);
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

            // ✅ Record discount in the financial ledger as an ADJUSTMENT
            //    (no balance effect on any account — pure reporting/audit trail)
            $this->recordDiscountAdjustment(
                $invoice->fresh(),
                $discountAmount,
                'manual',                                     // kind
                $validated['discount_notes'] ?? null,         // reason
                'invoice'                                     // anchor row
            );

            // ─── Update associated Order ─────────────────────────────────
            $order = Order::where('id', $invoice->order_id)
                        ->where('tenant_id', $tenantId)
                        ->first();

            if ($order) {
                $order->discount_total = ($order->discount_total ?? 0) + $discountAmount;
                $order->total = $order->subtotal + $order->tax_total - $order->discount_total;
                $order->balance_due = $order->total - $order->paid_amount;
                $order->save();

                Log::info('Discount applied to order from invoice', [
                    'order_id'         => $order->id,
                    'order_number'     => $order->order_number,
                    'invoice_id'       => $invoice->id,
                    'invoice_number'   => $invoice->invoice_number,
                    'discount_amount'  => $discountAmount,
                    'new_order_total'  => $order->total,
                    'applied_by'       => $user->id,
                ]);
            } else {
                Log::warning('No associated order found for invoice discount', [
                    'invoice_id' => $invoice->id,
                    'order_id'   => $invoice->order_id,
                ]);
            }

            DB::commit();

            return response()->json([
                'success'          => true,
                'message'          => __('payments.discount_applied', [
                    'amount' => number_format($discountAmount, 2),
                ]),
                'invoice'          => $invoice->fresh(),
                'order'            => $order ? $order->fresh() : null,
                'discount_amount'  => $discountAmount,
                'new_invoice_total'=> $invoice->total,
                'new_order_total'  => $order ? $order->total : null,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Discount apply failed: ' . $e->getMessage(), [
                'invoice_id' => $id,
                'tenant_id'  => $tenantId,
                'user_id'    => $user->id,
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

    private function appendNote(?string $existing, string $line): string
    {
        $timestamp = now()->format('Y-m-d H:i:s');
        $entry     = "[{$timestamp}] {$line}";
        return $existing ? $existing . "\n" . $entry : $entry;
    }



    /**
     * Send invoice via email or download - with full inventory strategy support
     */
    public function send(Request $request, $id, MessagingService $messaging)
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
            'phone'   => ['nullable', 'regex:/^\+[1-9]\d{7,14}$/'],
            'subject' => 'nullable|string|max:255',
            'message' => 'nullable|string',
        ], [
            'phone.regex' => __('payments.invalid_phone_format'),
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

        if (in_array($channel, ['whatsapp', 'sms'])) {
            $phoneToUse = $validated['phone'] ?? $invoice->billing_phone;
            if (!$phoneToUse) {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.customer_phone_required'),
                ], 422);
            }
            // Re-validate the fallback phone too — billing_phone on file might predate this validation
            if (!preg_match('/^\+[1-9]\d{7,14}$/', $phoneToUse)) {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.invalid_phone_format'),
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            $isFirstSend = $invoice->isDraft();

            if ($channel === 'email' && !empty($validated['email']) && $validated['email'] !== $invoice->billing_email) {
                $invoice->billing_email = $validated['email'];
            }

            // ─── FIRST SEND: reduce stock and narrate the event ─────────────
            if ($isFirstSend) {
                $isSingleShop = tenant_is_single_shop($tenantId);
                $actor        = $this->actorLabel($user);
                $customerName = $invoice->billing_name ?? $invoice->customer?->name ?? 'the customer';
                $context      = $this->contextLabel($invoice->order);

                // Log the human story BEFORE mutating
                Log::info("[Invoice] {$actor} is sending invoice {$invoice->invoice_number} to {$customerName} via {$channel}{$context}.", [
                    'invoice_id'   => $invoice->id,
                    'tenant_id'    => $tenantId,
                    'channel'      => $channel,
                    'customer'     => $customerName,
                    'order_number' => $invoice->order?->order_number,
                ]);

                foreach ($invoice->order->orderItems as $item) {
                    $variant = ProductVariant::find($item->variant_id);
                    if (!$variant) continue;

                    $product = $variant->product;
                    if (!$product) continue;

                    $strategy = $product->resolvedInventoryStrategy();

                    if ($strategy === 'recipe') {
                        $this->depleteRecipeIngredientsForInvoice($variant, $item, $invoice->order);
                    } else {
                        $this->depleteInventoryByStrategyForInvoice($variant, $item, $invoice->order, $isSingleShop);
                    }
                }

                // Build the story for the invoice itself
                $invoice->notes = $this->appendNote(
                    $invoice->notes,
                    sprintf(
                        '%s sent invoice %s to %s via %s%s. Invoice status changed from Draft → Sent. Stock was depleted for %d item(s).',
                        $actor,
                        $invoice->invoice_number,
                        $customerName,
                        strtoupper($channel),
                        $context,
                        $invoice->order->orderItems->count()
                    )
                );

                $invoice->status  = Invoice::STATUS_SENT;
                $invoice->sent_at = now();
                $invoice->save();

                Log::info("[Invoice] Invoice {$invoice->invoice_number} is now SENT. Stock has been depleted.", [
                    'invoice_id' => $invoice->id,
                ]);
            }

            // ─── SEND EMAIL — unchanged, exactly as before ────────
            if ($channel === 'email') {
                $emailToUse = $validated['email'] ?? $invoice->billing_email;
                $subject = $validated['subject'] ?? __('payments.invoice_subject', [
                    'number' => $invoice->invoice_number,
                    'app_name' => getUIOptions('app_name', $tenantId),
                ]);
                $customMessage = $validated['message'] ?? null;

                try {
                    Mail::to($emailToUse)
                        ->send(new \App\Mail\InvoiceMail($invoice, $subject, $customMessage));

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

            // ─── SEND WHATSAPP / SMS — via the service ────────────
            if (in_array($channel, ['whatsapp', 'sms'])) {
                $phoneToUse = $validated['phone'] ?? $invoice->billing_phone;

                $result = $channel === 'whatsapp'
                    ? $messaging->sendWhatsApp($invoice, $phoneToUse)
                    : $messaging->sendSms($invoice, $phoneToUse);

                $invoice->sends()->create([
                    'channel' => $channel,
                    'recipient' => $phoneToUse,
                    'status' => $result['success'] ? 'sent' : 'failed',
                    'provider' => 'messagebird',
                    'provider_message_id' => $result['provider_message_id'] ?? null,
                    'error_message' => $result['error'] ?? null,
                    'sent_by' => $user->id,
                    'sent_at' => $result['success'] ? now() : null,
                ]);

                if (!$result['success']) {
                    throw new \Exception($result['error'] ?? "Failed to send via {$channel}");
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
                'message' => match($channel) {
                    'email' => $isFirstSend ? __('payments.invoice_sent_and_stock_reduced') : __('payments.invoice_resent'),
                    'whatsapp', 'sms' => __('payments.invoice_sent'),
                    default => __('payments.invoice_ready_for_download'),
                },
                'invoice_id' => $invoice->id,
                'status' => $invoice->status,
                'download_url' => $channel === 'download' ? route('invoices.pdf', $invoice->id) : null,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Invoice send failed: ' . $e->getMessage(), [
                'invoice_id' => $id,
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

        // Log::info('[Invoice] Depleting inventory by strategy', [
        //     'variant_id' => $variant->id,
        //     'variant_name' => $variant->name,
        //     'strategy' => $strategy,
        //     'quantity' => is_object($item) ? ($item->quantity ?? 0) : ($item['quantity'] ?? 0),
        //     'is_single_shop' => $isSingleShop,
        //     'order_id' => $order->id,
        // ]);

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

        // ★ Snapshot pricing and log the sale
        $pricing = $this->resolveLogPricing(null, $variant);

        $before = (int) ($variant->overal_quantity_at_hand ?? 0) + $quantity;

        $narrative = $this->buildInventoryNarrative(
            'sent invoice (serial)', 'out', $variant, $item, $order, null,
            $before, $variant->overal_quantity_at_hand, $invoice ?? null
        );

        SingleShopInventoryLog::create([
            'variant_id'      => $variant->id,
            'order_id'        => $order->id,
            'tenant_id'       => $order->tenant_id,
            'created_by'      => auth()->id(),
            'quantity_before' => $before,
            'quantity_after'  => $variant->overal_quantity_at_hand,
            'quantity_change' => -$quantity,
            'reason'          => 'invoice_sent',
            'notes'           => $narrative,
            'source'          => 'invoice',

            'unit_cost_price'     => $pricing['cost'],
            'unit_selling_price'  => $pricing['selling'],
            'total_cost_value'    => -$quantity * $pricing['cost'],
            'total_selling_value' => -$quantity * $pricing['selling'],
            'pricing_source'      => $pricing['source'],

            'metadata' => [
                'strategy'      => 'serial',
                'serial_id'     => $serialId,
                'serial_number' => $serialNumber,
            ],
        ]);

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

        // Log::info('[Invoice] Depleting Multi Serial', [
        //     'variant_id' => $variant->id,
        //     'variant_name' => $variant->name,
        //     'serial_id' => $serialId,
        //     'serial_number' => $serialNumber,
        //     'quantity' => $quantity,
        //     'location_id' => $locationId,
        //     'department_id' => $departmentId,
        //     'order_id' => $order->id
        // ]);

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

        $before = (int) $inventory->quantity_allocated;
        $inventory->quantity_allocated = max(0, $before - $depletedQuantity);
        $inventory->save();
        $after = (int) $inventory->quantity_allocated;

        // Log inventory transaction
        // ★ Resolve pricing from the inventory row
        $pricing = $this->resolveLogPricing($inventory, $variant);

        $narrative = $this->buildInventoryNarrative(
            'sent invoice (serial)', 'out', $variant, $item, $order, $inventory,
            $before, $after, $invoice ?? null
        );

        InventoryTransactions::create([
            'quantity'       => -$depletedQuantity,
            'reference_id'   => $order->id,
            'reference_type' => 'order',
            'type'           => 'sale',
            'notes'          => $narrative,
            'inventory_id'   => $inventory->id,
            'created_by'     => auth()->id(),
            'tenant_id'      => $tenantId,

            'unit_cost_price'     => $pricing['cost'],
            'unit_selling_price'  => $pricing['selling'],
            'total_cost_value'    => -$depletedQuantity * $pricing['cost'],
            'total_selling_value' => -$depletedQuantity * $pricing['selling'],
        ]);

        InventoryAdjustments::create([
            'quantity_before'    => $before,
            'quantity_after'     => $after,
            'reason'             => 'invoice_sent',
            'notes'              => $narrative . " Serial(s): " . (
                $serialId
                    ? ($serialNumber ?? "#{$serialId}")
                    : ($serials->pluck('serial_number')->implode(', ') ?: 'N/A')
            ),
            'inventory_id'       => $inventory->id,
            'created_by'         => auth()->id(),
            'tenant_id'          => $tenantId,

            'unit_cost_price'    => $pricing['cost'],
            'unit_selling_price' => $pricing['selling'],
            'total_value_change' => -$depletedQuantity * $pricing['cost'],
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

        // Log::info('[Invoice] Depleting Recipe', [
        //     'product' => $product->name,
        //     'quantity' => $item->quantity,
        //     'order_id' => $order->id
        // ]);

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

        // ★ Resolve prices from the variant (single-shop has no InventoryItems row)
        $pricing = $this->resolveLogPricing(null, $variant);

        $narrative = $this->buildInventoryNarrative(
            'sent invoice', 'out', $variant, $item, $order, null,
            $before, $after, $invoice ?? null
        );

        SingleShopInventoryLog::create([
            'variant_id'      => $variant->id,
            'order_id'        => $order->id,
            'tenant_id'       => $order->tenant_id,
            'created_by'      => auth()->id(),
            'quantity_before' => $before,
            'quantity_after'  => $after,
            'quantity_change' => -$item->quantity,
            'reason'          => 'invoice_sent',
            'notes'           => $narrative,
            'source'          => 'invoice',

            'unit_cost_price'     => $pricing['cost'],
            'unit_selling_price'  => $pricing['selling'],
            'total_cost_value'    => -$item->quantity * $pricing['cost'],
            'total_selling_value' => -$item->quantity * $pricing['selling'],
            'pricing_source'      => $pricing['source'],

            'metadata'        => ['strategy' => 'quantity'],
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
        $quantityOriginal = (float) $item->quantity;   // ★
        $quantityNeeded   = $quantityOriginal;
        
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
            
            $variant->overal_quantity_at_hand = max(0, ($variant->overal_quantity_at_hand ?? 0) - $quantityOriginal);
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

        $variant->overal_quantity_at_hand = max(0, ($variant->overal_quantity_at_hand ?? 0) - $quantityOriginal);
        $variant->save();
    }



    // ─── MULTI SHOP QUANTITY ─────────────────────────────────────────────

    private function depleteMultiQuantityForInvoice($variant, $item, $order)
    {
        $user = Auth::user();
        $tenantId = $order->tenant_id;

        $inventoryData = $this->extractInventoryData($item);
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

        // ★ Resolve prices from the inventory row (multi-shop always has one here)
        $pricing = $this->resolveLogPricing($inventory, $variant);

        $narrative = $this->buildInventoryNarrative(
            'sent invoice', 'out', $variant, $item, $order, $inventory,
            $before, $after, $invoice ?? null
        );

        InventoryTransactions::create([
            'quantity'       => -$item->quantity,
            'reference_id'   => $order->id,
            'reference_type' => 'order',
            'type'           => 'sale',
            'notes'          => $narrative,
            'inventory_id'   => $inventory->id,
            'created_by'     => auth()->id(),
            'tenant_id'      => $tenantId,

            'unit_cost_price'     => $pricing['cost'],
            'unit_selling_price'  => $pricing['selling'],
            'total_cost_value'    => -$item->quantity * $pricing['cost'],
            'total_selling_value' => -$item->quantity * $pricing['selling'],
        ]);

        InventoryAdjustments::create([
            'quantity_before' => $before,
            'quantity_after'  => $after,
            'reason'          => 'invoice_sent',
            'notes'           => $narrative,
            'inventory_id'    => $inventory->id,
            'created_by'      => auth()->id(),
            'tenant_id'       => $tenantId,

            'unit_cost_price'    => $pricing['cost'],
            'unit_selling_price' => $pricing['selling'],
            'total_value_change' => -$item->quantity * $pricing['cost'],
        ]);

        // Log::info('[Invoice] Multi Shop Quantity depleted', [
        //     'variant' => $variant->name,
        //     'inventory_id' => $inventory->id,
        //     'location_id' => $inventory->location_id,
        //     'department_id' => $inventory->department_id,
        //     'before' => $before,
        //     'after' => $after
        // ]);
    }

    // ─── MULTI SHOP BATCH ────────────────────────────────────────────────

    private function depleteMultiBatchForInvoice($variant, $item, $order)
    {
        $user = Auth::user();
        $tenantId = $order->tenant_id;
        $quantityOriginal = (float) $item->quantity;   // ★ capture
        $quantityNeeded   = $quantityOriginal;
        
        // ✅ Get batch_id from the order item
        $batchId = $item->batch_id ?? null;
        
        // Log::info('[Invoice] Depleting Multi Batch', [
        //     'variant_id' => $variant->id,
        //     'variant_name' => $variant->name,
        //     'batch_id' => $batchId,
        //     'batch_number' => $item->batch_number ?? null,
        //     'quantity_needed' => $quantityNeeded,
        //     'order_id' => $order->id
        // ]);

        $inventoryData = $this->extractInventoryData($item);
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

        $before = (int) $inventory->quantity_allocated;
        $inventory->quantity_allocated = max(0, $before - $quantityOriginal);   // ★
        $inventory->save();
        $after = (int) $inventory->quantity_allocated;

        $pricing = $this->resolveLogPricing($inventory, $variant);

        $narrative = $this->buildInventoryNarrative(
            'sent invoice (batch)', 'out', $variant, $item, $order, $inventory,
            $before, $after, $invoice ?? null
        );

        InventoryTransactions::create([
            'quantity'       => -$quantityOriginal,
            'reference_id'   => $order->id,
            'reference_type' => 'order',
            'type'           => 'sale',
            'notes'          => $narrative,
            'inventory_id'   => $inventory->id,
            'created_by'     => auth()->id(),
            'tenant_id'      => $tenantId,

            'unit_cost_price'     => $pricing['cost'],
            'unit_selling_price'  => $pricing['selling'],
            'total_cost_value'    => -$quantityNeeded * $pricing['cost'],
            'total_selling_value' => -$quantityNeeded * $pricing['selling'],
        ]);

        // ✅ Also write the matching InventoryAdjustments row (you were missing this)
        InventoryAdjustments::create([
            'quantity_before'    => $before,
            'quantity_after'     => $after,
            'reason'             => 'invoice_sent',
            'notes'              => $narrative,
            'inventory_id'       => $inventory->id,
            'created_by'         => auth()->id(),
            'tenant_id'          => $tenantId,

            'unit_cost_price'    => $pricing['cost'],
            'unit_selling_price' => $pricing['selling'],
            'total_value_change' => -$quantityNeeded * $pricing['cost'],
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
        $beforeQty = (float) ($batch->quantity_remaining ?? $batch->quantity_received ?? 0) + $quantityDeducted;
        $afterQty  = (float) ($batch->quantity_remaining ?? $batch->quantity_received ?? 0);

        // ★ Try to resolve the inventory row this item is actually drawn from
        $inventory = null;
        $inventoryData = $this->extractInventoryData($item);
        if (!empty($inventoryData['inventory_id'])) {
            $inventory = InventoryItems::find($inventoryData['inventory_id']);
        }

        $frozenUnitPrice = is_object($item)
            ? (float) ($item->unit_price ?? 0)
            : (float) ($item['unit_price'] ?? 0);

        // ★ Pass the frozen price — batch unit_cost wins for cost side
        $pricing = $this->resolveLogPricing($inventory, $variant, $frozenUnitPrice);

        $unitCost = (float) ($batch->unit_cost ?? 0);
        if ($unitCost <= 0) $unitCost = $pricing['cost'];

        $narrative = $this->buildInventoryNarrative(
            'sent invoice (batch)', 'out', $variant, $item, $order, $inventory,
            (int) $beforeQty, (int) $afterQty, $invoice ?? null
        );

        BatchLog::create([
            'batch_id'        => $batch->id,
            'batch_number'    => $batch->batch_number,
            'variant_id'      => $variant->id,
            'variant_name'    => $variant->name,
            'variant_sku'     => $variant->sku,
            'type'            => BatchLog::TYPE_DEPLETED,
            'quantity_change' => -$quantityDeducted,
            'quantity_before' => $beforeQty,
            'quantity_after'  => $afterQty,

            'unit_cost'           => $unitCost,
            'total_cost'          => -$quantityDeducted * $unitCost,
            'unit_selling_price'  => $pricing['selling'],
            'total_selling_value' => -$quantityDeducted * $pricing['selling'],
            'gross_profit'        => $quantityDeducted * ($pricing['selling'] - $unitCost),
            'pricing_source'      => $batch->unit_cost ? 'batch' : $pricing['source'],

            'order_id'      => $order->id,
            'order_number'  => $order->order_number,
            'tenant_id'     => $order->tenant_id,
            'location_id'   => $batch->location_id ?? $order->location_id,
            'department_id' => $batch->department_id ?? $order->department_id,
            'expiry_date'   => $batch->expiry_date,
            'event_date'    => now(),
            'performed_by'  => auth()->id(),
            'metadata' => [
                'item_name'     => $item->name ?? $variant->name,
                'customer_name' => $order->customer_name,
                'unit_price'    => $item->price ?? 0,
                'source'        => 'invoice',
                'narrative'     => $narrative, // ✅ also stored on the row
            ],
        ]);
    }

    // ─── VOID / RESTOCK METHODS WITH STRATEGY SUPPORT ────────────────────

    private function restockSingleShopForVoid($variant, $item, $order)
    {
        $beforeQty = $variant->overal_quantity_at_hand;
        $afterQty = $beforeQty + $item->quantity;

        $variant->update(['overal_quantity_at_hand' => $afterQty]);

        $frozenUnitPrice = is_object($item)
            ? (float) ($item->unit_price ?? 0)
            : (float) ($item['unit_price'] ?? 0);

        $pricing = $this->resolveLogPricing(null, $variant, $frozenUnitPrice);

        $narrative = $this->buildInventoryNarrative(
            'voided invoice — restored stock', 'in', $variant, $item, $order, null,
            $beforeQty, $afterQty, $invoice ?? null
        );

        SingleShopInventoryLog::create([
            'variant_id'      => $variant->id,
            'order_id'        => $order->id,
            'tenant_id'       => $order->tenant_id,
            'created_by'      => auth()->id(),
            'quantity_before' => $beforeQty,
            'quantity_after'  => $afterQty,
            'quantity_change' => $item->quantity,
            'reason'          => 'invoice_voided',
            'notes'           => $narrative,
            'source'          => 'invoice',

            'unit_cost_price'     => $pricing['cost'],
            'unit_selling_price'  => $pricing['selling'],
            'total_cost_value'    => $item->quantity * $pricing['cost'],
            'total_selling_value' => $item->quantity * $pricing['selling'],
            'pricing_source'      => $pricing['source'],

            'metadata' => [
                'item_name'  => $item->item_name,
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
        $inventoryData = $this->extractInventoryData($item);
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

        
        // ★ Resolve pricing from the sale line (frozen unit_price is authoritative)
        $frozenUnitPrice = is_object($item)
            ? (float) ($item->unit_price ?? 0)
            : (float) ($item['unit_price'] ?? 0);

        $pricing = $this->resolveLogPricing($inventory, $variant, $frozenUnitPrice);

        $narrative = $this->buildInventoryNarrative(
            'voided invoice — restored stock', 'in', $variant, $item, $order, $inventory,
            $beforeQty, $afterQty, $invoice ?? null
        );

        InventoryAdjustments::create([
            'quantity_before'    => $beforeQty,
            'quantity_after'     => $afterQty,
            'reason'             => 'invoice_voided',
            'notes'              => $narrative,
            'inventory_id'       => $inventory->id,
            'created_by'         => auth()->id(),
            'tenant_id'          => $order->tenant_id,

            'unit_cost_price'    => $pricing['cost'],
            'unit_selling_price' => $pricing['selling'],
            'total_value_change' => $item->quantity * $pricing['cost'],
        ]);

        InventoryTransactions::create([
            'quantity'            => $item->quantity,
            'reference_id'        => $order->id,
            'reference_type'      => 'order',
            'type'                => 'return',
            'notes'               => $narrative,
            'inventory_id'        => $inventory->id,
            'created_by'          => auth()->id(),
            'tenant_id'           => $order->tenant_id,

            'unit_cost_price'     => $pricing['cost'],
            'unit_selling_price'  => $pricing['selling'],
            'total_cost_value'    => $item->quantity * $pricing['cost'],
            'total_selling_value' => $item->quantity * $pricing['selling'],
        ]);

        Log::info('[Invoice] Void - Stock restored (Multi Shop)', [
            'variant' => $variant->name,
            'inventory_id' => $inventory->id,
            'quantity' => $item->quantity,
            'order_id' => $order->id
        ]);
    }

    /**
     * Resolve cost + selling price to freeze onto an inventory log.
     *
     * Cascade:
     *   1. InventoryItems override at this location/department
     *      — only when has_custom_pricing = 1
     *   2. Variant's grand_total_cost_price / selling_price
     *   3. Recompute cost from variant components
     *
     * Returns [cost, selling, source]
     *   source ∈ { 'item', 'variant' }
     */
    private function resolveLogPricing(
        ?InventoryItems $inventory,
        ProductVariant $variant,
        ?float $frozenSellingPrice = null   // ★ from order_items.unit_price
    ): array {
        // ── Cost ─────────────────────────────────────────────────────
        $cost   = 0.0;
        $source = 'variant';

        if ($inventory && (float) ($inventory->grand_total_cost_price ?? 0) > 0) {
            $cost   = (float) $inventory->grand_total_cost_price;
            $source = 'item';
        } else {
            $grand = (float) ($variant->grand_total_cost_price ?? 0);
            if ($grand > 0) {
                $cost = $grand;
            } else {
                $cost = (float) ($variant->supplier_cost_price ?? 0)
                    + (float) ($variant->total_shipping_cost ?? 0)
                    + (float) ($variant->ura_taxes_applied   ?? 0)
                    + (float) ($variant->additional_expenses ?? 0);
            }
        }

        // ── Selling price ────────────────────────────────────────────
        // Priority:
        //   1. Frozen on the sale line — what the customer actually paid
        //   2. InventoryItems override at this location/department
        //   3. Variant default
        $selling = 0.0;

        if ($frozenSellingPrice !== null && $frozenSellingPrice > 0) {
            $selling = $frozenSellingPrice;
        } elseif ($inventory && (float) ($inventory->selling_price ?? 0) > 0) {
            $selling = (float) $inventory->selling_price;
        } else {
            $selling = (float) ($variant->selling_price ?? 0);
        }

        return ['cost' => $cost, 'selling' => $selling, 'source' => $source];
    }

    /**
     * Safely read $item->inventory_data. Handles Eloquent's array cast,
     * a raw JSON string, or a plain array.
     */
    private function extractInventoryData($item): array
    {
        if (is_object($item)) {
            $raw = $item->inventory_data ?? null;
        } elseif (is_array($item)) {
            $raw = $item['inventory_data'] ?? null;
        } else {
            return [];
        }

        if (is_array($raw)) return $raw;
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }


    /**
     * Update the issue date on an invoice.
     * Blocks updates once the invoice is paid or voided.
     */
    public function updateIssueDate(Request $request, $id)
    {
        $user = Auth::user();

        if (! $user->hasPermissionTo('edit invoice')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ], 403);
        }

        $validated = $request->validate([
            'issue_date' => 'required|date',
            'due_date'   => 'nullable|date|after_or_equal:issue_date',
        ]);

        $invoice = Invoice::where('tenant_id', $user->tenant_id)
            ->findOrFail($id);

        // Block editing on locked invoices
        if (in_array($invoice->status, ['paid', 'void', 'cancelled'], true)) {
            return response()->json([
                'success' => false,
                'message' => __('payments.invoice_locked'),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $update = ['issue_date' => $validated['issue_date']];

            // If a new due_date was supplied, use it. Otherwise recompute the
            // due_date from the ORIGINAL offset between the old issue and due dates.
            if (! empty($validated['due_date'])) {
                $update['due_date'] = $validated['due_date'];
            } elseif ($invoice->issue_date && $invoice->due_date) {
                $offset = $invoice->issue_date->diffInDays($invoice->due_date, false);
                $update['due_date'] = Carbon::parse($validated['issue_date'])
                    ->copy()
                    ->addDays($offset)
                    ->toDateString();
            }

            // Keep the linked order's created_at in sync so reports agree
            if ($invoice->order) {
                $invoice->order->update([
                    'created_at' => \Carbon\Carbon::parse($validated['issue_date'])->startOfDay(),
                ]);
            }

            $invoice->update($update);

            DB::commit();

            return response()->json([
                'success'     => true,
                'reload'      => true,
                'componentId' => 'reloadInvoiceComponent',
                'refresh'     => false,
                'redirect'    => route('invoices.index'),
                'message'     => __('payments.invoice_issue_date_updated'),
                'invoice'     => [
                    'id'               => $invoice->id,
                    'issue_date'       => $invoice->issue_date->format('Y-m-d'),
                    'issue_date_human' => $invoice->issue_date->format('d M Y'),
                    'due_date'         => optional($invoice->due_date)->format('Y-m-d'),
                    'due_date_human'   => optional($invoice->due_date)->format('d M Y'),
                ],
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('[Invoice] issue-date update failed', [
                'invoice_id' => $id,
                'error'      => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => __('auth.error_occurred') . ': ' . $e->getMessage(),
            ], 500);
        }
    }

}