<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\InvoiceSend;
use App\Models\InvoicePaymentWebhook;
use App\Models\Location;
use App\Models\Department;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class InvoiceReportsController extends Controller
{
    // ============================================================
    // GATE — permissions + tenant plan
    // ============================================================
    private function authorizeReports(): int
    {
        $user = auth()->user();

        if (!$user->hasPermissionTo('invoice reports')) {
            abort(403, __('payments.not_authorized'));
        }
        // if (!tenant_can('invoice_reports')) {
        //     abort(403, __('payments.feature_not_available_in_plan'));
        // }

        return $user->tenant_id;
    }

    // ============================================================
    // REPORT 1 — INVOICE SUMMARY
    // ============================================================
    public function summary(Request $request)
    {
        $tenantId = $this->authorizeReports();

        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);

        $locationId   = $request->get('location_id');
        $departmentId = $request->get('department_id');
        $statusFilter = $request->get('status_filter', 'all');
        $customerId   = $request->get('customer_id');
        $search       = $request->get('search');
        $perPage      = (int) $request->get('per_page', 25);

        $locations   = Location::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $departments = Department::where('tenant_id', $tenantId)->where('isActive', true)->orderBy('name')->get(['id', 'name']);
        $customers   = Customer::where('tenant_id', $tenantId)->orderBy('first_name')->get(['id', 'first_name', 'last_name']);
        $statuses    = collect(Invoice::STATUS_LABELS)->map(fn($label, $value) => ['value' => $value, 'label' => $label])->values();

        return view('reports.invoices.summary', compact(
            'locations', 'departments', 'customers', 'statuses',
            'startDate', 'endDate', 'locationId', 'departmentId', 'statusFilter',
            'customerId', 'search', 'perPage'
        ));
    }

    public function summaryData(Request $request)
    {
        $tenantId = $this->authorizeReports();

        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);

        $locationId   = $request->get('location_id');
        $departmentId = $request->get('department_id');
        $statusFilter = $request->get('status_filter', 'all');
        $customerId   = $request->get('customer_id');
        $search       = $request->get('search');
        $perPage      = (int) $request->get('per_page', 25);
        $page         = max(1, (int) $request->get('page', 1));

        $query = Invoice::where('tenant_id', $tenantId)
            ->whereBetween('issue_date', [$startDate, $endDate])
            ->with(['order.location', 'order.department', 'order.department.location', 'customer', 'creator:id,name']);

        if ($locationId)   $query->whereHas('order', fn($q) => $q->where('location_id', $locationId));
        if ($departmentId) $query->whereHas('order', fn($q) => $q->where('department_id', $departmentId));
        if ($statusFilter && $statusFilter !== 'all') $query->where('status', $statusFilter);
        if ($customerId)   $query->where('customer_id', $customerId);
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                ->orWhere('billing_name', 'like', "%{$search}%")
                ->orWhere('billing_email', 'like', "%{$search}%");
            });
        }

        $invoices = $query->orderByDesc('issue_date')->orderByDesc('id')->get();

        // ── Summary ──────────────────────────────────────────────
        $summary = $this->buildInvoiceSummary($invoices);

        // ── Status breakdown ────────────────────────────────────
        $statusBreakdown = $invoices->groupBy('status')->map(function ($items, $status) use ($invoices) {
            return [
                'status'       => $status,
                'label'        => Invoice::STATUS_LABELS[$status] ?? ucfirst($status),
                'color'        => Invoice::STATUS_COLORS[$status] ?? 'secondary',
                'icon'         => Invoice::STATUS_ICONS[$status] ?? 'bi-file-earmark',
                'count'        => $items->count(),
                'total_amount' => round((float) $items->sum('total'), 2),
                'balance_due'  => round((float) $items->sum('balance_due'), 2),
                'percentage'   => $invoices->count() > 0
                    ? round(($items->count() / $invoices->count()) * 100, 2) : 0,
            ];
        })->sortByDesc('total_amount')->values();

        // ── Aging buckets ───────────────────────────────────────
        $agingBuckets = $this->buildAgingBuckets($invoices);
        $agingArray = [];
        foreach (['current', '1_30', '31_60', '61_90', '90_plus'] as $key) {
            $bucket = $agingBuckets->$key;
            $agingArray[$key] = [
                'label'  => $bucket['label'],
                'count'  => $bucket['count'],
                'amount' => round((float) $bucket['amount'], 2),
                'color'  => $bucket['color'],
            ];
        }

        // ── Monthly trend ───────────────────────────────────────
        $monthlyTrend = $invoices->groupBy(fn($inv) => $inv->issue_date->format('Y-m'))
            ->map(fn($items, $month) => [
                'month'          => $month,
                'month_label'    => Carbon::parse($month . '-01')->format('M Y'),
                'invoice_count'  => $items->count(),
                'total_amount'   => round((float) $items->sum('total'), 2),
                'paid_amount'    => round((float) $items->sum('amount_paid'), 2),
                'outstanding'    => round((float) $items->sum('balance_due'), 2),
            ])->sortKeys()->values();

        // ── Top customers ───────────────────────────────────────
        $topCustomers = $invoices
            ->groupBy(fn($inv) => $inv->customer_id ?? 'guest_' . $inv->billing_name)
            ->map(function ($items) {
                $first = $items->first();
                return [
                    'customer_id'   => $first->customer_id,
                    'customer_name' => $first->customer_id
                        ? ($first->customer?->full_name ?? $first->billing_name)
                        : $first->billing_name,
                    'invoice_count' => $items->count(),
                    'total_billed'  => round((float) $items->sum('total'), 2),
                    'total_paid'    => round((float) $items->sum('amount_paid'), 2),
                    'outstanding'   => round((float) $items->sum('balance_due'), 2),
                ];
            })->sortByDesc('total_billed')->take(10)->values();

        // ── Pagination ──────────────────────────────────────────
        $total    = $invoices->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page     = min($page, $lastPage);
        $offset   = ($page - 1) * $perPage;

        $rows = $invoices->slice($offset, $perPage)->values()->map(function ($inv) {
            $locName = $inv->order?->correct_location?->name
                    ?? $inv->order?->location?->name ?? 'N/A';

            return [
                'id'             => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'issue_date'     => $inv->issue_date?->format('Y-m-d'),
                'issue_human'    => $inv->issue_date?->format('M d, Y'),
                'due_date'       => $inv->due_date?->format('Y-m-d'),
                'due_human'      => $inv->due_date?->format('M d, Y'),
                'is_overdue_due' => $inv->due_date
                    && $inv->due_date->isPast() && $inv->balance_due > 0,
                'billing_name'   => $inv->billing_name,
                'billing_email'  => $inv->billing_email,
                'status'         => $inv->status,
                'status_label'   => $inv->status_label,
                'status_color'   => $inv->status_color,
                'total'          => round((float) $inv->total, 2),
                'amount_paid'    => round((float) $inv->amount_paid, 2),
                'balance_due'    => round((float) $inv->balance_due, 2),
                'location_name'  => $locName,
            ];
        });

        $pageTotals = [
            'total'        => round((float) $rows->sum('total'), 2),
            'amount_paid'  => round((float) $rows->sum('amount_paid'), 2),
            'balance_due'  => round((float) $rows->sum('balance_due'), 2),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'summary'            => $summary,
                'status_breakdown'   => $statusBreakdown,
                'aging_buckets'      => $agingArray,
                'monthly_trend'      => $monthlyTrend,
                'top_customers'      => $topCustomers,
                'invoices'           => $rows,
                'page_totals'        => $pageTotals,
                'pagination' => [
                    'current_page' => $page,
                    'last_page'    => $lastPage,
                    'total'        => $total,
                    'from'         => $total > 0 ? $offset + 1 : 0,
                    'to'           => min($offset + $perPage, $total),
                    'per_page'     => $perPage,
                ],
                'meta' => [
                    'start_date'    => $startDate,
                    'end_date'      => $endDate,
                    'location_id'   => $locationId,
                    'department_id' => $departmentId,
                    'status_filter' => $statusFilter,
                    'customer_id'   => $customerId,
                    'search'        => $search,
                ],
            ],
        ]);
    }

    public function summaryDetail($id)
    {
        $tenantId = $this->authorizeReports();

        $invoice = Invoice::where('tenant_id', $tenantId)
            ->with([
                'order.orderItems.variant',
                'order.location',
                'order.department',
                'customer:id,first_name,last_name,email,phone',
                'payments.paymentMethod:id,name,type',
                'payments.processedBy:id,name',
                'sends',
                'creator:id,name',
                'voidedBy:id,name',
            ])->findOrFail($id);

        $items = ($invoice->order?->orderItems ?? collect())->map(fn($it) => [
            'id'              => $it->id,
            'name'            => $it->item_name ?? $it->variant?->name ?? 'Unknown',
            'sku'             => $it->sku ?? $it->variant?->sku ?? 'N/A',
            'quantity'        => round((float) $it->quantity, 2),
            'unit_price'      => round((float) $it->unit_price, 2),
            'discount'        => round((float) ($it->discount ?? 0), 2),
            'tax_amount'      => round((float) ($it->tax_amount ?? 0), 2),
            'total_price'     => round((float) $it->total_price, 2),
        ])->values();

        $payments = $invoice->payments->map(fn($p) => [
            'id'             => $p->id,
            'amount'         => round((float) $p->amount, 2),
            'method_name'    => $p->paymentMethod?->name ?? '—',
            'transaction_id' => $p->transaction_id,
            'status'         => $p->status,
            'status_label'   => $p->status_label ?? ucfirst($p->status),
            'status_color'   => $p->status_color ?? 'secondary',
            'type_label'     => $p->type_label ?? ucfirst($p->type ?? '—'),
            'processed_by'   => $p->processedBy?->name,
            'payment_date'   => ($p->payment_date ?? $p->created_at)?->format('Y-m-d H:i'),
            'notes'          => $p->notes,
        ])->values();

        $sends = $invoice->sends->map(fn($s) => [
            'id'        => $s->id,
            'channel'   => $s->channel,
            'recipient' => $s->recipient,
            'status'    => $s->status,
            'sent_at'   => $s->sent_at?->format('Y-m-d H:i'),
            'error'     => $s->error_message,
        ])->values();

        return response()->json([
            'success' => true,
            'data' => [
                'id'              => $invoice->id,
                'invoice_number'  => $invoice->invoice_number,
                'status'          => $invoice->status,
                'status_label'    => $invoice->status_label,
                'status_color'    => $invoice->status_color,
                'billing' => [
                    'name'    => $invoice->billing_name,
                    'email'   => $invoice->billing_email,
                    'phone'   => $invoice->billing_phone,
                    'address' => $invoice->billing_address,
                ],
                'issue_date'     => $invoice->issue_date?->format('Y-m-d'),
                'due_date'       => $invoice->due_date?->format('Y-m-d'),
                'sent_at'        => $invoice->sent_at?->format('Y-m-d H:i'),
                'viewed_at'      => $invoice->viewed_at?->format('Y-m-d H:i'),
                'paid_at'        => $invoice->paid_at?->format('Y-m-d H:i'),
                'voided_at'      => $invoice->voided_at?->format('Y-m-d H:i'),
                'subtotal'       => round((float) $invoice->subtotal, 2),
                'discount'       => round((float) $invoice->discount_total, 2),
                'tax'            => round((float) $invoice->tax_total, 2),
                'total'          => round((float) $invoice->total, 2),
                'amount_paid'    => round((float) $invoice->amount_paid, 2),
                'balance_due'    => round((float) $invoice->balance_due, 2),
                'notes'          => $invoice->notes,
                'terms'          => $invoice->terms,
                'created_by'     => $invoice->creator?->name,
                'voided_by'      => $invoice->voidedBy?->name,
                'order' => $invoice->order ? [
                    'id'              => $invoice->order->id,
                    'order_number'    => $invoice->order->order_number,
                    'status'          => $invoice->order->status,
                    'location_name'   => $invoice->order->correct_location?->name
                                        ?? $invoice->order->location?->name,
                    'department_name' => $invoice->order->department?->name,
                ] : null,
                'items'    => $items,
                'payments' => $payments,
                'sends'    => $sends,
            ],
        ]);
    }

    // ============================================================
    // REPORT 2 — OUTSTANDING / AGING
    // ============================================================
    public function outstanding(Request $request)
    {
        $tenantId = $this->authorizeReports();

        $startDate = $request->get('start_date', Carbon::now()->subMonths(6)->format('Y-m-d'));
        $endDate   = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);

        $locationId   = $request->get('location_id');
        $departmentId = $request->get('department_id');
        $customerId   = $request->get('customer_id');
        $agingBucket  = $request->get('aging_bucket', 'all');
        $onlyOverdue  = $request->boolean('only_overdue', false);
        $perPage      = (int) $request->get('per_page', 25);

        $locations   = Location::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $departments = Department::where('tenant_id', $tenantId)->where('isActive', true)->orderBy('name')->get(['id', 'name']);
        $customers   = Customer::where('tenant_id', $tenantId)->orderBy('first_name')->get(['id', 'first_name', 'last_name']);

        $agingBucketOptions = [
            ['value' => 'all',        'label' => 'All Buckets'],
            ['value' => 'current',    'label' => 'Current (not yet due)'],
            ['value' => '1_30',       'label' => '1–30 days overdue'],
            ['value' => '31_60',      'label' => '31–60 days overdue'],
            ['value' => '61_90',      'label' => '61–90 days overdue'],
            ['value' => '90_plus',    'label' => '90+ days overdue'],
        ];

        return view('reports.invoices.outstanding', compact(
            'locations', 'departments', 'customers', 'agingBucketOptions',
            'startDate', 'endDate', 'locationId', 'departmentId', 'customerId',
            'agingBucket', 'onlyOverdue', 'perPage'
        ));
    }

    public function outstandingData(Request $request)
    {
        $tenantId = $this->authorizeReports();

        $startDate = $request->get('start_date', Carbon::now()->subMonths(6)->format('Y-m-d'));
        $endDate   = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);

        $locationId   = $request->get('location_id');
        $departmentId = $request->get('department_id');
        $customerId   = $request->get('customer_id');
        $agingBucket  = $request->get('aging_bucket', 'all');
        $onlyOverdue  = $request->boolean('only_overdue', false);
        $perPage      = (int) $request->get('per_page', 25);
        $page         = max(1, (int) $request->get('page', 1));

        // ── Query (outstanding only) ─────────────────────────────
        $query = Invoice::where('tenant_id', $tenantId)
            ->whereBetween('issue_date', [$startDate, $endDate])
            ->whereNotIn('status', [Invoice::STATUS_PAID, Invoice::STATUS_VOID, Invoice::STATUS_CANCELLED])
            ->where('balance_due', '>', 0)
            ->with(['order.location', 'order.department', 'order.department.location', 'customer', 'creator:id,name']);

        if ($locationId)   $query->whereHas('order', fn($q) => $q->where('location_id', $locationId));
        if ($departmentId) $query->whereHas('order', fn($q) => $q->where('department_id', $departmentId));
        if ($customerId)   $query->where('customer_id', $customerId);

        $invoices = $query->orderBy('due_date')->orderByDesc('id')->get();

        // ── Enrich with days overdue + aging bucket ─────────────
        $invoices = $invoices->map(function ($inv) {
            $daysPastDue = $inv->due_date ? max(0, $inv->due_date->diffInDays(Carbon::now(), false)) : 0;
            $inv->days_past_due = ($inv->due_date && $inv->due_date->isPast()) ? abs($daysPastDue) : 0;
            $inv->aging_bucket  = $this->resolveAgingBucket($inv->days_past_due);
            $inv->is_overdue    = $inv->due_date && $inv->due_date->isPast() && $inv->balance_due > 0;
            return $inv;
        });

        // ── Apply aging + overdue filters ───────────────────────
        if ($agingBucket && $agingBucket !== 'all') {
            $invoices = $invoices->where('aging_bucket', $agingBucket)->values();
        }
        if ($onlyOverdue) {
            $invoices = $invoices->where('is_overdue', true)->values();
        }

        // ── Aging summary ────────────────────────────────────────
        $agingSummary = $this->buildAgingBuckets($invoices, true);
        $agingArray = [];
        foreach (['current', '1_30', '31_60', '61_90', '90_plus'] as $key) {
            $bucket = $agingSummary->$key;
            $agingArray[$key] = [
                'label'  => $bucket['label'],
                'count'  => $bucket['count'],
                'amount' => round((float) $bucket['amount'], 2),
                'color'  => $bucket['color'],
            ];
        }

        // ── Summary ──────────────────────────────────────────────
        $summary = [
            'total_outstanding'   => round((float) $invoices->sum('balance_due'), 2),
            'total_invoiced'      => round((float) $invoices->sum('total'), 2),
            'total_collected'     => round((float) $invoices->sum('amount_paid'), 2),
            'total_invoices'      => $invoices->count(),
            'overdue_invoices'    => $invoices->where('is_overdue', true)->count(),
            'overdue_amount'      => round((float) $invoices->where('is_overdue', true)->sum('balance_due'), 2),
            'current_invoices'    => $invoices->where('is_overdue', false)->count(),
            'current_amount'      => round((float) $invoices->where('is_overdue', false)->sum('balance_due'), 2),
            'average_outstanding' => $invoices->count() > 0
                ? round($invoices->sum('balance_due') / $invoices->count(), 2) : 0,
            'average_days_overdue'=> round((float) ($invoices->where('is_overdue', true)->avg('days_past_due') ?? 0), 1),
            'collection_rate'     => $invoices->sum('total') > 0
                ? round(($invoices->sum('amount_paid') / $invoices->sum('total')) * 100, 2) : 0,
        ];

        // ── Top debtors ─────────────────────────────────────────
        $topDebtors = $invoices
            ->groupBy(fn($inv) => $inv->customer_id ?? 'guest_' . $inv->billing_name)
            ->map(function ($items) {
                $first   = $items->first();
                $overdue = $items->where('is_overdue', true);
                return [
                    'customer_id'        => $first->customer_id,
                    'customer_name'      => $first->customer_id
                        ? ($first->customer?->full_name ?? $first->billing_name)
                        : $first->billing_name,
                    'invoice_count'      => $items->count(),
                    'total_outstanding'  => round((float) $items->sum('balance_due'), 2),
                    'overdue_count'      => $overdue->count(),
                    'overdue_amount'     => round((float) $overdue->sum('balance_due'), 2),
                    'oldest_days_overdue'=> (int) ($items->max('days_past_due') ?? 0),
                ];
            })
            ->sortByDesc('total_outstanding')
            ->take(10)
            ->values();

        // ── By location ────────────────────────────────────────
        $byLocation = $invoices
            ->groupBy(fn($inv) => $inv->order?->correct_location?->name ?? 'Unknown')
            ->map(fn($items, $loc) => [
                'location'       => $loc,
                'invoice_count'  => $items->count(),
                'outstanding'    => round((float) $items->sum('balance_due'), 2),
                'overdue_amount' => round((float) $items->where('is_overdue', true)->sum('balance_due'), 2),
            ])
            ->sortByDesc('outstanding')
            ->values();

        // ── Paginate ───────────────────────────────────────────
        $total    = $invoices->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page     = min($page, $lastPage);
        $offset   = ($page - 1) * $perPage;

        $bucketMeta = [
            'current'  => ['label' => 'Current',    'color' => 'success'],
            '1_30'     => ['label' => '1–30 days',  'color' => 'info'],
            '31_60'    => ['label' => '31–60 days', 'color' => 'warning'],
            '61_90'    => ['label' => '61–90 days', 'color' => 'danger'],
            '90_plus'  => ['label' => '90+ days',   'color' => 'dark'],
        ];

        $rows = $invoices->slice($offset, $perPage)->values()->map(function ($inv) use ($bucketMeta) {
            $locName = $inv->order?->correct_location?->name
                    ?? $inv->order?->location?->name ?? 'N/A';
            $meta = $bucketMeta[$inv->aging_bucket] ?? ['label' => '—', 'color' => 'secondary'];

            return [
                'id'             => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'billing_name'   => $inv->billing_name,
                'billing_phone'  => $inv->billing_phone,
                'issue_date'     => $inv->issue_date?->format('Y-m-d'),
                'issue_human'    => $inv->issue_date?->format('M d, Y'),
                'due_date'       => $inv->due_date?->format('Y-m-d'),
                'due_human'      => $inv->due_date?->format('M d, Y'),
                'is_due_past'    => (bool) ($inv->due_date && $inv->due_date->isPast()),
                'days_past_due'  => (int) $inv->days_past_due,
                'aging_bucket'   => $inv->aging_bucket,
                'bucket_label'   => $meta['label'],
                'bucket_color'   => $meta['color'],
                'total'          => round((float) $inv->total, 2),
                'amount_paid'    => round((float) $inv->amount_paid, 2),
                'balance_due'    => round((float) $inv->balance_due, 2),
                'location_name'  => $locName,
            ];
        });

        $pageTotals = [
            'total'       => round((float) $rows->sum('total'), 2),
            'amount_paid' => round((float) $rows->sum('amount_paid'), 2),
            'balance_due' => round((float) $rows->sum('balance_due'), 2),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'summary'         => $summary,
                'aging_buckets'   => $agingArray,
                'top_debtors'     => $topDebtors,
                'by_location'     => $byLocation,
                'invoices'        => $rows,
                'page_totals'     => $pageTotals,
                'pagination' => [
                    'current_page' => $page,
                    'last_page'    => $lastPage,
                    'total'        => $total,
                    'from'         => $total > 0 ? $offset + 1 : 0,
                    'to'           => min($offset + $perPage, $total),
                    'per_page'     => $perPage,
                ],
                'meta' => [
                    'start_date'    => $startDate,
                    'end_date'      => $endDate,
                    'location_id'   => $locationId,
                    'department_id' => $departmentId,
                    'customer_id'   => $customerId,
                    'aging_bucket'  => $agingBucket,
                    'only_overdue'  => $onlyOverdue,
                ],
            ],
        ]);
    }

    public function outstandingDetail($id)
    {
        $tenantId = $this->authorizeReports();

        $invoice = Invoice::where('tenant_id', $tenantId)
            ->with([
                'order.orderItems.variant',
                'order.location',
                'order.department',
                'customer:id,first_name,last_name,email,phone',
                'payments.paymentMethod:id,name,type',
                'payments.processedBy:id,name',
                'sends',
                'creator:id,name',
                'voidedBy:id,name',
            ])->findOrFail($id);

        $daysPastDue = ($invoice->due_date && $invoice->due_date->isPast())
            ? (int) abs($invoice->due_date->diffInDays(Carbon::now(), false))
            : 0;

        $items = ($invoice->order?->orderItems ?? collect())->map(fn($it) => [
            'id'          => $it->id,
            'name'        => $it->item_name ?? $it->variant?->name ?? 'Unknown',
            'sku'         => $it->sku ?? $it->variant?->sku ?? 'N/A',
            'quantity'    => round((float) $it->quantity, 2),
            'unit_price'  => round((float) $it->unit_price, 2),
            'discount'    => round((float) ($it->discount ?? 0), 2),
            'tax_amount'  => round((float) ($it->tax_amount ?? 0), 2),
            'total_price' => round((float) $it->total_price, 2),
        ])->values();

        $payments = $invoice->payments->map(fn($p) => [
            'id'             => $p->id,
            'amount'         => round((float) $p->amount, 2),
            'method_name'    => $p->paymentMethod?->name ?? '—',
            'transaction_id' => $p->transaction_id,
            'status'         => $p->status,
            'status_label'   => $p->status_label ?? ucfirst($p->status),
            'status_color'   => $p->status_color ?? 'secondary',
            'type_label'     => $p->type_label ?? ucfirst($p->type ?? '—'),
            'processed_by'   => $p->processedBy?->name,
            'payment_date'   => ($p->payment_date ?? $p->created_at)?->format('Y-m-d H:i'),
            'notes'          => $p->notes,
        ])->values();

        $sends = $invoice->sends->map(fn($s) => [
            'id'        => $s->id,
            'channel'   => $s->channel,
            'recipient' => $s->recipient,
            'status'    => $s->status,
            'sent_at'   => $s->sent_at?->format('Y-m-d H:i'),
            'error'     => $s->error_message,
        ])->values();

        return response()->json([
            'success' => true,
            'data' => [
                'id'             => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'status'         => $invoice->status,
                'status_label'   => $invoice->status_label,
                'status_color'   => $invoice->status_color,
                'days_past_due'  => $daysPastDue,
                'is_overdue'     => $invoice->due_date && $invoice->due_date->isPast() && $invoice->balance_due > 0,
                'billing' => [
                    'name'    => $invoice->billing_name,
                    'email'   => $invoice->billing_email,
                    'phone'   => $invoice->billing_phone,
                    'address' => $invoice->billing_address,
                ],
                'issue_date'     => $invoice->issue_date?->format('Y-m-d'),
                'due_date'       => $invoice->due_date?->format('Y-m-d'),
                'sent_at'        => $invoice->sent_at?->format('Y-m-d H:i'),
                'viewed_at'      => $invoice->viewed_at?->format('Y-m-d H:i'),
                'paid_at'        => $invoice->paid_at?->format('Y-m-d H:i'),
                'voided_at'      => $invoice->voided_at?->format('Y-m-d H:i'),
                'subtotal'       => round((float) $invoice->subtotal, 2),
                'discount'       => round((float) $invoice->discount_total, 2),
                'tax'            => round((float) $invoice->tax_total, 2),
                'total'          => round((float) $invoice->total, 2),
                'amount_paid'    => round((float) $invoice->amount_paid, 2),
                'balance_due'    => round((float) $invoice->balance_due, 2),
                'notes'          => $invoice->notes,
                'terms'          => $invoice->terms,
                'created_by'     => $invoice->creator?->name,
                'voided_by'      => $invoice->voidedBy?->name,
                'order' => $invoice->order ? [
                    'id'              => $invoice->order->id,
                    'order_number'    => $invoice->order->order_number,
                    'status'          => $invoice->order->status,
                    'location_name'   => $invoice->order->correct_location?->name
                                        ?? $invoice->order->location?->name,
                    'department_name' => $invoice->order->department?->name,
                ] : null,
                'items'    => $items,
                'payments' => $payments,
                'sends'    => $sends,
            ],
        ]);
    }


    // ============================================================
    // REPORT 3 — INVOICE PAYMENTS
    // ============================================================
    public function payments(Request $request)
    {
        $tenantId = $this->authorizeReports();

        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);

        $locationId    = $request->get('location_id');
        $departmentId  = $request->get('department_id');
        $paymentMethod = $request->get('payment_method_id');
        $recordedVia   = $request->get('recorded_via', 'all');
        $statusFilter  = $request->get('status', 'all');
        $perPage       = (int) $request->get('per_page', 25);

        $locations      = Location::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $departments    = Department::where('tenant_id', $tenantId)->where('isActive', true)->orderBy('name')->get(['id', 'name']);
        $paymentMethods = PaymentMethod::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get(['id', 'name', 'type']);

        $channels = [
            ['value' => 'all',     'label' => 'All Channels'],
            ['value' => 'manual',  'label' => 'Manual Entry'],
            ['value' => 'pos',     'label' => 'POS'],
            ['value' => 'webhook', 'label' => 'Webhook'],
        ];

        $statuses = [
            ['value' => 'all',                  'label' => 'All Statuses'],
            ['value' => 'completed',            'label' => 'Completed'],
            ['value' => 'pending',              'label' => 'Pending'],
            ['value' => 'failed',               'label' => 'Failed'],
            ['value' => 'refunded',             'label' => 'Refunded'],
            ['value' => 'pending_verification', 'label' => 'Pending Verification'],
        ];

        return view('reports.invoices.payments', compact(
            'locations', 'departments', 'paymentMethods', 'channels', 'statuses',
            'startDate', 'endDate', 'locationId', 'departmentId', 'paymentMethod',
            'recordedVia', 'statusFilter', 'perPage'
        ));
    }

    public function paymentsData(Request $request)
    {
        $tenantId = $this->authorizeReports();

        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);

        $locationId    = $request->get('location_id');
        $departmentId  = $request->get('department_id');
        $paymentMethod = $request->get('payment_method_id');
        $recordedVia   = $request->get('recorded_via', 'all');
        $statusFilter  = $request->get('status', 'all');
        $perPage       = (int) $request->get('per_page', 25);
        $page          = max(1, (int) $request->get('page', 1));

        // ── Query (payments only) ───────────────────────────────
        $query = InvoicePayment::where('tenant_id', $tenantId)
            ->whereHas('invoice', function ($q) use ($tenantId, $startDate, $endDate, $locationId, $departmentId) {
                $q->where('tenant_id', $tenantId)
                ->whereBetween('issue_date', [$startDate, $endDate]);
                if ($locationId)   $q->whereHas('order', fn($o) => $o->where('location_id', $locationId));
                if ($departmentId) $q->whereHas('order', fn($o) => $o->where('department_id', $departmentId));
            })
            ->with([
                'invoice.order.location',
                'invoice.order.department',
                'invoice.customer',
                'paymentMethod:id,name,type',
                'processedBy:id,name',
            ])
            ->whereBetween('payment_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

        if ($paymentMethod) $query->where('payment_method_id', $paymentMethod);
        if ($statusFilter && $statusFilter !== 'all') $query->where('status', $statusFilter);

        $all = $query->orderByDesc('payment_date')->get();

        // ── Enrich ───────────────────────────────────────────────
        $all = $all->map(function ($p) {
            $recordedVia = $p->order_payment_id ? 'pos' : 'manual';
            return (object) [
                'id'                  => $p->id,
                'invoice_number'      => $p->invoice?->invoice_number ?? 'N/A',
                'invoice_id'          => $p->invoice_id,
                'order_number'        => $p->invoice?->order?->order_number ?? 'N/A',
                'customer_name'       => $p->invoice?->billing_name
                                        ?? $p->invoice?->order?->customer_display_name
                                        ?? 'Guest',
                'amount'              => round((float) $p->amount, 2),
                'payment_method'      => $p->paymentMethod?->name ?? 'Unknown',
                'payment_method_type' => $p->paymentMethod?->type ?? 'other',
                'recorded_via'        => $recordedVia,
                'status'              => $p->status,
                'status_label'        => $p->status_label ?? ucfirst($p->status),
                'status_color'        => $p->status_color ?? 'secondary',
                'reference'           => $p->reference_number ?? $p->transaction_id ?? 'N/A',
                'processed_at'        => $p->payment_date,
                'processed_by'        => $p->processedBy?->name ?? 'System',
            ];
        });

        // Apply recorded_via filter after derivation
        if ($recordedVia && $recordedVia !== 'all') {
            $all = $all->where('recorded_via', $recordedVia)->values();
        }

        // ── Summary ──────────────────────────────────────────────
        $summary = [
            'total_payments'   => $all->count(),
            'total_amount'     => round((float) $all->sum('amount'), 2),
            'average_payment'  => $all->count() > 0 ? round($all->sum('amount') / $all->count(), 2) : 0,
            'largest_payment'  => round((float) ($all->max('amount') ?? 0), 2),
            'completed_count'  => $all->where('status', 'completed')->count(),
            'completed_amount' => round((float) $all->where('status', 'completed')->sum('amount'), 2),
            'failed_count'     => $all->where('status', 'failed')->count(),
            'failed_amount'    => round((float) $all->where('status', 'failed')->sum('amount'), 2),
            'refunded_count'   => $all->where('status', 'refunded')->count(),
            'refunded_amount'  => round((float) $all->where('status', 'refunded')->sum('amount'), 2),
        ];

        // ── By method ───────────────────────────────────────────
        $byMethod = $all->groupBy('payment_method')
            ->map(fn($items, $method) => [
                'method'         => $method,
                'type'           => $items->first()->payment_method_type,
                'count'          => $items->count(),
                'total_amount'   => round((float) $items->sum('amount'), 2),
                'average_amount' => $items->count() > 0 ? round($items->sum('amount') / $items->count(), 2) : 0,
            ])
            ->sortByDesc('total_amount')->values();

        // ── By channel ──────────────────────────────────────────
        $byChannel = $all->groupBy('recorded_via')
            ->map(fn($items, $channel) => [
                'channel'      => $channel,
                'count'        => $items->count(),
                'total_amount' => round((float) $items->sum('amount'), 2),
            ])
            ->sortByDesc('total_amount')->values();

        // ── Daily trend ────────────────────────────────────────
        $dailyTrend = $all->groupBy(fn($p) => $p->processed_at->format('Y-m-d'))
            ->map(fn($items, $date) => [
                'date'          => $date,
                'payment_count' => $items->count(),
                'total_amount'  => round((float) $items->sum('amount'), 2),
            ])
            ->sortKeys()->values();

        // ── Paginate ───────────────────────────────────────────
        $total    = $all->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page     = min($page, $lastPage);
        $offset   = ($page - 1) * $perPage;

        $rows = $all->slice($offset, $perPage)->values()->map(fn($p) => [
            'id'                  => $p->id,
            'invoice_id'          => $p->invoice_id,
            'invoice_number'      => $p->invoice_number,
            'order_number'        => $p->order_number,
            'customer_name'       => $p->customer_name,
            'amount'              => $p->amount,
            'payment_method'      => $p->payment_method,
            'payment_method_type' => $p->payment_method_type,
            'recorded_via'        => $p->recorded_via,
            'status'              => $p->status,
            'status_label'        => $p->status_label,
            'status_color'        => $p->status_color,
            'reference'           => $p->reference,
            'processed_at'        => $p->processed_at?->format('Y-m-d H:i'),
            'processed_at_human'  => $p->processed_at?->format('M d, Y'),
            'processed_at_time'   => $p->processed_at?->format('H:i A'),
            'processed_by'        => $p->processed_by,
        ]);

        $pageTotals = [
            'amount' => round((float) $rows->sum('amount'), 2),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'summary'      => $summary,
                'by_method'    => $byMethod,
                'by_channel'   => $byChannel,
                'daily_trend'  => $dailyTrend,
                'payments'     => $rows,
                'page_totals'  => $pageTotals,
                'pagination' => [
                    'current_page' => $page,
                    'last_page'    => $lastPage,
                    'total'        => $total,
                    'from'         => $total > 0 ? $offset + 1 : 0,
                    'to'           => min($offset + $perPage, $total),
                    'per_page'     => $perPage,
                ],
                'meta' => [
                    'start_date'    => $startDate,
                    'end_date'      => $endDate,
                    'location_id'   => $locationId,
                    'department_id' => $departmentId,
                    'payment_method_id' => $paymentMethod,
                    'recorded_via'  => $recordedVia,
                    'status'        => $statusFilter,
                ],
            ],
        ]);
    }

    public function paymentsDetail($id)
    {
        $tenantId = $this->authorizeReports();

        $payment = InvoicePayment::where('tenant_id', $tenantId)
            ->with([
                'invoice:id,invoice_number,status,total,amount_paid,balance_due,billing_name,billing_email,issue_date,due_date,order_id',
                'invoice.order:id,order_number,customer_name',
                'paymentMethod:id,name,type,current_balance',
                'processedBy:id,name,email',
            ])->findOrFail($id);

        $recordedVia = $payment->order_payment_id ? 'pos' : 'manual';

        return response()->json([
            'success' => true,
            'data' => [
                'id'             => $payment->id,
                'amount'         => round((float) $payment->amount, 2),
                'status'         => $payment->status,
                'status_label'   => $payment->status_label ?? ucfirst($payment->status),
                'status_color'   => $payment->status_color ?? 'secondary',
                'type'           => $payment->type,
                'type_label'     => $payment->type_label ?? ucfirst($payment->type ?? '—'),
                'recorded_via'   => $recordedVia,
                'transaction_id' => $payment->transaction_id,
                'reference_number'=> $payment->reference_number,
                'payment_date'   => $payment->payment_date?->format('Y-m-d H:i'),
                'payment_date_human' => $payment->payment_date?->format('M d, Y H:i A'),
                'verified_at'    => $payment->verified_at?->format('Y-m-d H:i'),
                'refunded_at'    => $payment->refunded_at?->format('Y-m-d H:i'),
                'notes'          => $payment->notes,
                'currency_code'  => $payment->currency_code,
                'payment_method' => [
                    'id'              => $payment->paymentMethod?->id,
                    'name'            => $payment->paymentMethod?->name ?? 'Unknown',
                    'type'            => $payment->paymentMethod?->type ?? 'other',
                    'current_balance' => round((float) ($payment->paymentMethod?->current_balance ?? 0), 2),
                ],
                'processed_by' => [
                    'name'  => $payment->processedBy?->name ?? 'System',
                    'email' => $payment->processedBy?->email,
                ],
                'invoice' => $payment->invoice ? [
                    'id'             => $payment->invoice->id,
                    'invoice_number' => $payment->invoice->invoice_number,
                    'status'         => $payment->invoice->status,
                    'billing_name'   => $payment->invoice->billing_name,
                    'billing_email'  => $payment->invoice->billing_email,
                    'issue_date'     => $payment->invoice->issue_date?->format('Y-m-d'),
                    'due_date'       => $payment->invoice->due_date?->format('Y-m-d'),
                    'total'          => round((float) $payment->invoice->total, 2),
                    'amount_paid'    => round((float) $payment->invoice->amount_paid, 2),
                    'balance_due'    => round((float) $payment->invoice->balance_due, 2),
                    'order_number'   => $payment->invoice->order?->order_number,
                ] : null,
            ],
        ]);
    }


    // ============================================================
    // REPORT 4 — DELIVERY / SEND STATUS
    // ============================================================
    public function delivery(Request $request)
    {
        $tenantId = $this->authorizeReports();

        $startDate = $request->get('start_date', Carbon::now()->subMonths(3)->format('Y-m-d'));
        $endDate   = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);

        $locationId   = $request->get('location_id');
        $departmentId = $request->get('department_id');
        $channel      = $request->get('channel', 'all');
        $sendStatus   = $request->get('send_status', 'all');
        $perPage      = (int) $request->get('per_page', 25);

        $locations   = Location::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $departments = Department::where('tenant_id', $tenantId)->where('isActive', true)->orderBy('name')->get(['id', 'name']);

        $channels = [
            ['value' => 'all',         'label' => 'All Channels'],
            ['value' => 'email',       'label' => 'Email'],
            ['value' => 'sms',         'label' => 'SMS'],
            ['value' => 'whatsapp',    'label' => 'WhatsApp'],
            ['value' => 'portal_link', 'label' => 'Portal Link'],
            ['value' => 'print',       'label' => 'Print'],
        ];

        $sendStatuses = [
            ['value' => 'all',       'label' => 'All Statuses'],
            ['value' => 'pending',   'label' => 'Pending'],
            ['value' => 'sent',      'label' => 'Sent'],
            ['value' => 'delivered', 'label' => 'Delivered'],
            ['value' => 'failed',    'label' => 'Failed'],
            ['value' => 'bounced',   'label' => 'Bounced'],
        ];

        return view('reports.invoices.delivery', compact(
            'locations', 'departments', 'channels', 'sendStatuses',
            'startDate', 'endDate', 'locationId', 'departmentId',
            'channel', 'sendStatus', 'perPage'
        ));
    }

    public function deliveryData(Request $request)
    {
        $tenantId = $this->authorizeReports();

        $startDate = $request->get('start_date', Carbon::now()->subMonths(3)->format('Y-m-d'));
        $endDate   = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);

        $locationId   = $request->get('location_id');
        $departmentId = $request->get('department_id');
        $channel      = $request->get('channel', 'all');
        $sendStatus   = $request->get('send_status', 'all');
        $perPage      = (int) $request->get('per_page', 25);
        $page         = max(1, (int) $request->get('page', 1));

        // ── Query ────────────────────────────────────────────────
        $query = InvoiceSend::where('tenant_id', $tenantId)
            ->whereHas('invoice', function ($q) use ($tenantId, $startDate, $endDate, $locationId, $departmentId) {
                $q->where('tenant_id', $tenantId)
                ->whereBetween('issue_date', [$startDate, $endDate]);
                if ($locationId)   $q->whereHas('order', fn($o) => $o->where('location_id', $locationId));
                if ($departmentId) $q->whereHas('order', fn($o) => $o->where('department_id', $departmentId));
            })
            ->with(['invoice:id,invoice_number,billing_name,billing_email,billing_phone,issue_date,due_date,total,balance_due,status,order_id', 'invoice.order:id,order_number', 'sentBy:id,name']);

        if ($channel && $channel !== 'all')          $query->where('channel', $channel);
        if ($sendStatus && $sendStatus !== 'all')    $query->where('status', $sendStatus);

        $all = $query->orderByDesc('created_at')->get();

        // ── Summary ──────────────────────────────────────────────
        $summary = [
            'total_sends'     => $all->count(),
            'unique_invoices' => $all->pluck('invoice_id')->unique()->count(),
            'sent_count'      => $all->where('status', 'sent')->count(),
            'delivered_count' => $all->where('status', 'delivered')->count(),
            'failed_count'    => $all->where('status', 'failed')->count(),
            'bounced_count'   => $all->where('status', 'bounced')->count(),
            'pending_count'   => $all->where('status', 'pending')->count(),
            'delivery_rate'   => $all->count() > 0
                ? round(($all->whereIn('status', ['sent', 'delivered'])->count() / $all->count()) * 100, 2) : 0,
            'failure_rate'    => $all->count() > 0
                ? round(($all->whereIn('status', ['failed', 'bounced'])->count() / $all->count()) * 100, 2) : 0,
        ];

        // ── By channel ───────────────────────────────────────────
        $byChannel = $all->groupBy('channel')->map(fn($items, $ch) => [
            'channel'      => $ch,
            'total'        => $items->count(),
            'sent'         => $items->where('status', 'sent')->count(),
            'delivered'    => $items->where('status', 'delivered')->count(),
            'failed'       => $items->where('status', 'failed')->count(),
            'bounced'      => $items->where('status', 'bounced')->count(),
            'pending'      => $items->where('status', 'pending')->count(),
            'success_rate' => $items->count() > 0
                ? round(($items->whereIn('status', ['sent', 'delivered'])->count() / $items->count()) * 100, 2) : 0,
        ])->values();

        // ── Failure reasons ─────────────────────────────────────
        $failureReasons = $all
            ->whereIn('status', ['failed', 'bounced'])
            ->whereNotNull('error_message')
            ->groupBy('error_message')
            ->map(fn($items, $reason) => [
                'reason' => \Illuminate\Support\Str::limit($reason, 120),
                'count'  => $items->count(),
            ])
            ->sortByDesc('count')->take(10)->values();

        // ── Daily trend ─────────────────────────────────────────
        $dailyTrend = $all->groupBy(fn($s) => $s->created_at->format('Y-m-d'))
            ->map(fn($items, $date) => [
                'date'      => $date,
                'sent'      => $items->where('status', 'sent')->count(),
                'delivered' => $items->where('status', 'delivered')->count(),
                'failed'    => $items->whereIn('status', ['failed', 'bounced'])->count(),
            ])->sortKeys()->values();

        // ── Paginate ────────────────────────────────────────────
        $total    = $all->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page     = min($page, $lastPage);
        $offset   = ($page - 1) * $perPage;

        $statusColors = [
            'pending'   => 'warning',
            'sent'      => 'info',
            'delivered' => 'success',
            'failed'    => 'danger',
            'bounced'   => 'dark',
        ];
        $channelColors = [
            'email'       => 'primary',
            'sms'         => 'info',
            'whatsapp'    => 'success',
            'portal_link' => 'warning',
            'print'       => 'secondary',
        ];

        $rows = $all->slice($offset, $perPage)->values()->map(function ($s) use ($statusColors, $channelColors) {
            return [
                'id'              => $s->id,
                'invoice_id'      => $s->invoice_id,
                'invoice_number'  => $s->invoice?->invoice_number ?? 'N/A',
                'billing_name'    => $s->invoice?->billing_name,
                'recipient'       => $s->recipient,
                'channel'         => $s->channel,
                'channel_label'   => ucfirst(str_replace('_', ' ', $s->channel ?? '')),
                'channel_color'   => $channelColors[$s->channel] ?? 'secondary',
                'status'          => $s->status,
                'status_label'    => ucfirst($s->status ?? ''),
                'status_color'    => $statusColors[$s->status] ?? 'secondary',
                'sent_at'         => $s->sent_at?->format('Y-m-d H:i'),
                'sent_at_human'   => $s->sent_at?->format('M d, Y'),
                'sent_at_time'    => $s->sent_at?->format('H:i A'),
                'delivered_at'    => $s->delivered_at?->format('Y-m-d H:i'),
                'delivered_at_human' => $s->delivered_at?->format('M d, Y'),
                'delivered_at_time'  => $s->delivered_at?->format('H:i A'),
                'provider'        => $s->provider,
                'sent_by'         => $s->sentBy?->name ?? 'System',
                'error_message'   => $s->error_message,
                'error_preview'   => $s->error_message ? \Illuminate\Support\Str::limit($s->error_message, 60) : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'summary'         => $summary,
                'by_channel'      => $byChannel,
                'failure_reasons' => $failureReasons,
                'daily_trend'     => $dailyTrend,
                'sends'           => $rows,
                'pagination' => [
                    'current_page' => $page,
                    'last_page'    => $lastPage,
                    'total'        => $total,
                    'from'         => $total > 0 ? $offset + 1 : 0,
                    'to'           => min($offset + $perPage, $total),
                    'per_page'     => $perPage,
                ],
                'meta' => [
                    'start_date'    => $startDate,
                    'end_date'      => $endDate,
                    'location_id'   => $locationId,
                    'department_id' => $departmentId,
                    'channel'       => $channel,
                    'send_status'   => $sendStatus,
                ],
            ],
        ]);
    }

    public function deliveryDetail($id)
    {
        $tenantId = $this->authorizeReports();

        $send = InvoiceSend::where('tenant_id', $tenantId)
            ->with([
                'invoice:id,invoice_number,billing_name,billing_email,billing_phone,issue_date,due_date,total,amount_paid,balance_due,status,order_id',
                'invoice.order:id,order_number',
                'sentBy:id,name,email',
            ])->findOrFail($id);

        // All sends for the same invoice — to show retries
        $history = InvoiceSend::where('invoice_id', $send->invoice_id)
            ->with('sentBy:id,name')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($s) => [
                'id'         => $s->id,
                'channel'    => $s->channel,
                'recipient'  => $s->recipient,
                'status'     => $s->status,
                'sent_at'    => $s->sent_at?->format('Y-m-d H:i'),
                'error'      => $s->error_message,
                'is_current' => $s->id === $send->id,
            ])->values();

        return response()->json([
            'success' => true,
            'data' => [
                'id'            => $send->id,
                'channel'       => $send->channel,
                'channel_label' => ucfirst(str_replace('_', ' ', $send->channel ?? '')),
                'recipient'     => $send->recipient,
                'status'        => $send->status,
                'status_label'  => ucfirst($send->status ?? ''),
                'provider'      => $send->provider,
                'provider_message_id' => $send->provider_message_id,
                'sent_at'       => $send->sent_at?->format('Y-m-d H:i'),
                'delivered_at'  => $send->delivered_at?->format('Y-m-d H:i'),
                'error_message' => $send->error_message,
                'sent_by' => [
                    'name'  => $send->sentBy?->name ?? 'System',
                    'email' => $send->sentBy?->email,
                ],
                'invoice' => $send->invoice ? [
                    'id'             => $send->invoice->id,
                    'invoice_number' => $send->invoice->invoice_number,
                    'billing_name'   => $send->invoice->billing_name,
                    'billing_email'  => $send->invoice->billing_email,
                    'billing_phone'  => $send->invoice->billing_phone,
                    'issue_date'     => $send->invoice->issue_date?->format('Y-m-d'),
                    'due_date'       => $send->invoice->due_date?->format('Y-m-d'),
                    'status'         => $send->invoice->status,
                    'total'          => round((float) $send->invoice->total, 2),
                    'amount_paid'    => round((float) $send->invoice->amount_paid, 2),
                    'balance_due'    => round((float) $send->invoice->balance_due, 2),
                    'order_number'   => $send->invoice->order?->order_number,
                ] : null,
                'history' => $history,
            ],
        ]);
    }


    // ============================================================
    // REPORT 5 — STATUS TRENDS / TIME-TO-PAYMENT
    // ============================================================
    public function statusTrends(Request $request)
    {
        $tenantId = $this->authorizeReports();

        $startDate = $request->get('start_date', Carbon::now()->subMonths(6)->format('Y-m-d'));
        $endDate   = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);

        $locationId   = $request->get('location_id');
        $departmentId = $request->get('department_id');
        $groupBy      = $request->get('group_by', 'monthly');
        $perPage      = (int) $request->get('per_page', 25);

        $locations   = Location::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $departments = Department::where('tenant_id', $tenantId)->where('isActive', true)->orderBy('name')->get(['id', 'name']);

        $groupOptions = [
            ['value' => 'daily',   'label' => 'Daily'],
            ['value' => 'weekly',  'label' => 'Weekly'],
            ['value' => 'monthly', 'label' => 'Monthly'],
        ];

        return view('reports.invoices.status-trends', compact(
            'locations', 'departments', 'groupOptions',
            'startDate', 'endDate',
            'locationId', 'departmentId', 'groupBy', 'perPage'
        ));
    }

    public function statusTrendsData(Request $request)
    {
        $tenantId = $this->authorizeReports();

        $startDate = $request->get('start_date', Carbon::now()->subMonths(6)->format('Y-m-d'));
        $endDate   = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);

        $locationId   = $request->get('location_id');
        $departmentId = $request->get('department_id');
        $groupBy      = $request->get('group_by', 'monthly');
        $perPage      = (int) $request->get('per_page', 25);
        $page         = max(1, (int) $request->get('page', 1));

        $query = Invoice::where('tenant_id', $tenantId)
            ->whereBetween('issue_date', [$startDate, $endDate])
            ->with(['order.location', 'order.department', 'customer:id,first_name,last_name']);

        if ($locationId)   $query->whereHas('order', fn($q) => $q->where('location_id', $locationId));
        if ($departmentId) $query->whereHas('order', fn($q) => $q->where('department_id', $departmentId));

        $invoices = $query->orderByDesc('issue_date')->get();

        // ── Enrich with time metrics ────────────────────────────
        $invoices = $invoices->map(function ($inv) {
            $inv->days_to_send = ($inv->issue_date && $inv->sent_at) ? $inv->issue_date->diffInDays($inv->sent_at) : null;
            $inv->days_to_view = ($inv->sent_at && $inv->viewed_at)  ? $inv->sent_at->diffInDays($inv->viewed_at)   : null;
            $inv->days_to_pay  = ($inv->issue_date && $inv->paid_at) ? $inv->issue_date->diffInDays($inv->paid_at)  : null;
            $inv->days_to_due  = ($inv->issue_date && $inv->due_date)? $inv->issue_date->diffInDays($inv->due_date) : null;
            return $inv;
        });

        // ── Summary ────────────────────────────────────────────
        $paidInvoices = $invoices->whereNotNull('paid_at');

        $summary = [
            'total_invoices'       => $invoices->count(),
            'paid_count'           => $paidInvoices->count(),
            'unpaid_count'         => $invoices->whereNull('paid_at')->count(),
            'void_count'           => $invoices->where('status', Invoice::STATUS_VOID)->count(),
            'avg_days_to_send'     => round((float) ($invoices->whereNotNull('days_to_send')->avg('days_to_send') ?? 0), 1),
            'avg_days_to_view'     => round((float) ($invoices->whereNotNull('days_to_view')->avg('days_to_view') ?? 0), 1),
            'avg_days_to_pay'      => round((float) ($paidInvoices->avg('days_to_pay') ?? 0), 1),
            'median_days_to_pay'   => $this->median($paidInvoices->pluck('days_to_pay')->filter()->values()->toArray()),
            'fastest_payment_days' => (int) ($paidInvoices->min('days_to_pay') ?? 0),
            'slowest_payment_days' => (int) ($paidInvoices->max('days_to_pay') ?? 0),
            'paid_within_terms'    => $invoices->filter(fn($i) =>
                $i->days_to_pay !== null && $i->days_to_due !== null && $i->days_to_pay <= $i->days_to_due
            )->count(),
            'paid_late'            => $invoices->filter(fn($i) =>
                $i->days_to_pay !== null && $i->days_to_due !== null && $i->days_to_pay > $i->days_to_due
            )->count(),
        ];

        // ── Status funnel ──────────────────────────────────────
        $funnelDef = [
            ['status' => 'draft',          'label' => 'Draft',          'const' => Invoice::STATUS_DRAFT],
            ['status' => 'sent',           'label' => 'Sent',           'const' => Invoice::STATUS_SENT],
            ['status' => 'viewed',         'label' => 'Viewed',         'const' => Invoice::STATUS_VIEWED],
            ['status' => 'partially_paid', 'label' => 'Partially Paid', 'const' => Invoice::STATUS_PARTIALLY_PAID],
            ['status' => 'paid',           'label' => 'Paid',           'const' => Invoice::STATUS_PAID],
            ['status' => 'overdue',        'label' => 'Overdue',        'const' => Invoice::STATUS_OVERDUE],
            ['status' => 'void',           'label' => 'Void',           'const' => Invoice::STATUS_VOID],
        ];

        $statusFunnel = collect($funnelDef)->map(fn($f) => [
            'status' => $f['status'],
            'label'  => $f['label'],
            'color'  => Invoice::STATUS_COLORS[$f['const']] ?? 'secondary',
            'count'  => $invoices->where('status', $f['const'])->count(),
        ])->filter(fn($s) => $s['count'] > 0)->values();

        // ── Trend ──────────────────────────────────────────────
        $groupKey = match ($groupBy) {
            'daily'  => fn($inv) => $inv->issue_date->format('Y-m-d'),
            'weekly' => fn($inv) => $inv->issue_date->format('o-\WW'),
            default  => fn($inv) => $inv->issue_date->format('Y-m'),
        };

        $trend = $invoices->groupBy($groupKey)->map(fn($items, $period) => [
            'period'            => $period,
            'invoice_count'     => $items->count(),
            'paid_count'        => $items->whereNotNull('paid_at')->count(),
            'total_value'       => round((float) $items->sum('total'), 2),
            'paid_value'        => round((float) $items->sum('amount_paid'), 2),
            'outstanding_value' => round((float) $items->sum('balance_due'), 2),
            'avg_days_to_pay'   => round((float) ($items->whereNotNull('days_to_pay')->avg('days_to_pay') ?? 0), 1),
            'collection_rate'   => $items->sum('total') > 0
                ? round(($items->sum('amount_paid') / $items->sum('total')) * 100, 2) : 0,
        ])->sortKeysDesc()->values();

        // ── Payment speed buckets ──────────────────────────────
        $buckets = ['same_day' => 0, '1_7_days' => 0, '8_14_days' => 0, '15_30_days' => 0, '31_60_days' => 0, '60_plus' => 0];
        foreach ($paidInvoices as $inv) {
            $days = $inv->days_to_pay ?? 0;
            if ($days <= 0)         $buckets['same_day']++;
            elseif ($days <= 7)     $buckets['1_7_days']++;
            elseif ($days <= 14)    $buckets['8_14_days']++;
            elseif ($days <= 30)    $buckets['15_30_days']++;
            elseif ($days <= 60)    $buckets['31_60_days']++;
            else                    $buckets['60_plus']++;
        }

        // ── Paginate ──────────────────────────────────────────
        $total    = $invoices->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page     = min($page, $lastPage);
        $offset   = ($page - 1) * $perPage;

        $rows = $invoices->slice($offset, $perPage)->values()->map(function ($inv) {
            $paidLate = $inv->days_to_pay !== null
                    && $inv->days_to_due !== null
                    && $inv->days_to_pay > $inv->days_to_due;

            $locName = $inv->order?->correct_location?->name
                    ?? $inv->order?->location?->name ?? 'N/A';

            return [
                'id'             => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'billing_name'   => $inv->billing_name,
                'customer_id'    => $inv->customer_id,
                'issue_date'     => $inv->issue_date?->format('Y-m-d'),
                'issue_human'    => $inv->issue_date?->format('M d, Y'),
                'status'         => $inv->status,
                'status_label'   => $inv->status_label,
                'status_color'   => $inv->status_color,
                'days_to_send'   => $inv->days_to_send,
                'days_to_view'   => $inv->days_to_view,
                'days_to_pay'    => $inv->days_to_pay,
                'paid_late'      => $paidLate,
                'total'          => round((float) $inv->total, 2),
                'balance_due'    => round((float) $inv->balance_due, 2),
                'location_name'  => $locName,
            ];
        });

        // ── Grand totals for trend foot ────────────────────────
        $trendTotals = [
            'invoice_count'     => (int) $trend->sum('invoice_count'),
            'paid_count'        => (int) $trend->sum('paid_count'),
            'total_value'       => round((float) $trend->sum('total_value'), 2),
            'paid_value'        => round((float) $trend->sum('paid_value'), 2),
            'outstanding_value' => round((float) $trend->sum('outstanding_value'), 2),
            'avg_days_to_pay'   => round((float) ($trend->avg('avg_days_to_pay') ?? 0), 1),
            'collection_rate'   => round((float) ($trend->avg('collection_rate') ?? 0), 2),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'summary'         => $summary,
                'status_funnel'   => $statusFunnel,
                'trend'           => $trend,
                'trend_totals'    => $trendTotals,
                'speed_buckets'   => $buckets,
                'invoices'        => $rows,
                'pagination' => [
                    'current_page' => $page,
                    'last_page'    => $lastPage,
                    'total'        => $total,
                    'from'         => $total > 0 ? $offset + 1 : 0,
                    'to'           => min($offset + $perPage, $total),
                    'per_page'     => $perPage,
                ],
                'meta' => [
                    'start_date'    => $startDate,
                    'end_date'      => $endDate,
                    'location_id'   => $locationId,
                    'department_id' => $departmentId,
                    'group_by'      => $groupBy,
                ],
            ],
        ]);
    }

    public function statusTrendsDetail($id)
    {
        $tenantId = $this->authorizeReports();

        $invoice = Invoice::where('tenant_id', $tenantId)
            ->with([
                'order.orderItems.variant',
                'order.location',
                'order.department',
                'customer:id,first_name,last_name,email,phone',
                'payments.paymentMethod:id,name,type',
                'payments.processedBy:id,name',
                'sends',
                'creator:id,name',
                'voidedBy:id,name',
            ])->findOrFail($id);

        $daysToSend = ($invoice->issue_date && $invoice->sent_at)   ? $invoice->issue_date->diffInDays($invoice->sent_at)   : null;
        $daysToView = ($invoice->sent_at && $invoice->viewed_at)    ? $invoice->sent_at->diffInDays($invoice->viewed_at)    : null;
        $daysToPay  = ($invoice->issue_date && $invoice->paid_at)   ? $invoice->issue_date->diffInDays($invoice->paid_at)   : null;
        $daysToDue  = ($invoice->issue_date && $invoice->due_date)  ? $invoice->issue_date->diffInDays($invoice->due_date)  : null;

        $items = ($invoice->order?->orderItems ?? collect())->map(fn($it) => [
            'id'          => $it->id,
            'name'        => $it->item_name ?? $it->variant?->name ?? 'Unknown',
            'sku'         => $it->sku ?? $it->variant?->sku ?? 'N/A',
            'quantity'    => round((float) $it->quantity, 2),
            'unit_price'  => round((float) $it->unit_price, 2),
            'discount'    => round((float) ($it->discount ?? 0), 2),
            'tax_amount'  => round((float) ($it->tax_amount ?? 0), 2),
            'total_price' => round((float) $it->total_price, 2),
        ])->values();

        $payments = $invoice->payments->map(fn($p) => [
            'id'             => $p->id,
            'amount'         => round((float) $p->amount, 2),
            'method_name'    => $p->paymentMethod?->name ?? '—',
            'transaction_id' => $p->transaction_id,
            'status'         => $p->status,
            'status_label'   => $p->status_label ?? ucfirst($p->status),
            'status_color'   => $p->status_color ?? 'secondary',
            'processed_by'   => $p->processedBy?->name,
            'payment_date'   => ($p->payment_date ?? $p->created_at)?->format('Y-m-d H:i'),
        ])->values();

        $sends = $invoice->sends->map(fn($s) => [
            'id'        => $s->id,
            'channel'   => $s->channel,
            'recipient' => $s->recipient,
            'status'    => $s->status,
            'sent_at'   => $s->sent_at?->format('Y-m-d H:i'),
            'error'     => $s->error_message,
        ])->values();

        return response()->json([
            'success' => true,
            'data' => [
                'id'             => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'status'         => $invoice->status,
                'status_label'   => $invoice->status_label,
                'status_color'   => $invoice->status_color,
                'billing' => [
                    'name'    => $invoice->billing_name,
                    'email'   => $invoice->billing_email,
                    'phone'   => $invoice->billing_phone,
                    'address' => $invoice->billing_address,
                ],
                'issue_date'     => $invoice->issue_date?->format('Y-m-d'),
                'due_date'       => $invoice->due_date?->format('Y-m-d'),
                'sent_at'        => $invoice->sent_at?->format('Y-m-d H:i'),
                'viewed_at'      => $invoice->viewed_at?->format('Y-m-d H:i'),
                'paid_at'        => $invoice->paid_at?->format('Y-m-d H:i'),
                'voided_at'      => $invoice->voided_at?->format('Y-m-d H:i'),
                'days_to_send'   => $daysToSend,
                'days_to_view'   => $daysToView,
                'days_to_pay'    => $daysToPay,
                'days_to_due'    => $daysToDue,
                'paid_late'      => ($daysToPay !== null && $daysToDue !== null && $daysToPay > $daysToDue),
                'subtotal'       => round((float) $invoice->subtotal, 2),
                'discount'       => round((float) $invoice->discount_total, 2),
                'tax'            => round((float) $invoice->tax_total, 2),
                'total'          => round((float) $invoice->total, 2),
                'amount_paid'    => round((float) $invoice->amount_paid, 2),
                'balance_due'    => round((float) $invoice->balance_due, 2),
                'notes'          => $invoice->notes,
                'terms'          => $invoice->terms,
                'created_by'     => $invoice->creator?->name,
                'voided_by'      => $invoice->voidedBy?->name,
                'order' => $invoice->order ? [
                    'id'              => $invoice->order->id,
                    'order_number'    => $invoice->order->order_number,
                    'location_name'   => $invoice->order->correct_location?->name
                                        ?? $invoice->order->location?->name,
                    'department_name' => $invoice->order->department?->name,
                ] : null,
                'items'    => $items,
                'payments' => $payments,
                'sends'    => $sends,
            ],
        ]);
    }


    // ============================================================
    // SHARED HELPERS
    // ============================================================

    private function validateAndFormatDates($startDate, $endDate): array
    {
        try {
            $start = Carbon::parse($startDate)->format('Y-m-d');
            $end   = Carbon::parse($endDate)->format('Y-m-d');

            if ($start > $end) {
                [$start, $end] = [$end, $start];
            }
        } catch (\Exception $e) {
            $start = Carbon::now()->subDays(30)->format('Y-m-d');
            $end   = Carbon::now()->format('Y-m-d');
        }

        return [$start, $end];
    }

    private function paginateCollection($collection, int $perPage = 25, string $pageName = 'page'): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage($pageName);
        $perPage = max(1, min($perPage, max($collection->count(), 1)));
        $items = $collection->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $items,
            $collection->count(),
            $perPage,
            $page,
            [
                'path'     => request()->url(),
                'pageName' => $pageName,
                'query'    => request()->except($pageName),
            ]
        );
    }

    private function buildInvoiceSummary($invoices): object
    {
        $outstanding = $invoices->whereNotIn('status', [
            Invoice::STATUS_PAID,
            Invoice::STATUS_VOID,
            Invoice::STATUS_CANCELLED,
        ]);

        return (object) [
            'total_invoices'         => $invoices->count(),
            'total_invoiced'         => $invoices->sum('total'),
            'total_paid'             => $invoices->sum('amount_paid'),
            'total_outstanding'      => $invoices->sum('balance_due'),
            'total_tax'              => $invoices->sum('tax_total'),
            'total_discount'         => $invoices->sum('discount_total'),
            'average_invoice_value'  => $invoices->count() > 0
                ? $invoices->avg('total')
                : 0,
            'largest_invoice'        => $invoices->max('total') ?? 0,
            'collection_rate'        => $invoices->sum('total') > 0
                ? ($invoices->sum('amount_paid') / $invoices->sum('total')) * 100
                : 0,
            'paid_count'             => $invoices->where('status', Invoice::STATUS_PAID)->count(),
            'partially_paid_count'   => $invoices->where('status', Invoice::STATUS_PARTIALLY_PAID)->count(),
            'outstanding_count'      => $outstanding->count(),
            'overdue_count'          => $invoices->filter(fn($i) =>
                $i->due_date && $i->due_date->isPast() && $i->balance_due > 0
            )->count(),
            'void_count'             => $invoices->where('status', Invoice::STATUS_VOID)->count(),
            'cancelled_count'        => $invoices->where('status', Invoice::STATUS_CANCELLED)->count(),
            'draft_count'            => $invoices->where('status', Invoice::STATUS_DRAFT)->count(),
            'sent_count'             => $invoices->where('status', Invoice::STATUS_SENT)->count(),
            'viewed_count'           => $invoices->where('status', Invoice::STATUS_VIEWED)->count(),
        ];
    }

    private function buildAgingBuckets($invoices, bool $onlyOutstanding = false): object
    {
        $buckets = [
            'current'  => ['label' => 'Current',         'count' => 0, 'amount' => 0, 'color' => 'success'],
            '1_30'     => ['label' => '1–30 days',       'count' => 0, 'amount' => 0, 'color' => 'info'],
            '31_60'    => ['label' => '31–60 days',      'count' => 0, 'amount' => 0, 'color' => 'warning'],
            '61_90'    => ['label' => '61–90 days',      'count' => 0, 'amount' => 0, 'color' => 'danger'],
            '90_plus'  => ['label' => '90+ days',        'count' => 0, 'amount' => 0, 'color' => 'dark'],
        ];

        foreach ($invoices as $inv) {
            if ($onlyOutstanding && $inv->balance_due <= 0) continue;

            $days = $inv->days_past_due ?? $this->computeDaysPastDue($inv);
            $bucket = $this->resolveAgingBucket($days);

            $buckets[$bucket]['count']++;
            $buckets[$bucket]['amount'] += $inv->balance_due ?? 0;
        }

        return (object) $buckets;
    }

    private function computeDaysPastDue($invoice): int
    {
        if (!$invoice->due_date || !$invoice->due_date->isPast()) {
            return 0;
        }
        return abs($invoice->due_date->diffInDays(Carbon::now(), false));
    }

    private function resolveAgingBucket(int $daysPastDue): string
    {
        if ($daysPastDue <= 0)   return 'current';
        if ($daysPastDue <= 30)  return '1_30';
        if ($daysPastDue <= 60)  return '31_60';
        if ($daysPastDue <= 90)  return '61_90';
        return '90_plus';
    }

    private function median(array $values): float
    {
        if (empty($values)) return 0;
        sort($values);
        $count = count($values);
        $mid = (int) floor($count / 2);
        return $count % 2 === 0
            ? ($values[$mid - 1] + $values[$mid]) / 2
            : $values[$mid];
    }
}