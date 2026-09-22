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

        // ─── Date Range ──────────────────────────────────────────
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);

        // ─── Filters ─────────────────────────────────────────────
        $locationId   = $request->get('location_id');
        $departmentId = $request->get('department_id');
        $statusFilter = $request->get('status_filter', 'all');
        $customerId   = $request->get('customer_id');
        $search       = $request->get('search');
        $perPage      = (int) $request->get('per_page', 25);

        // ─── Build Query ─────────────────────────────────────────
        $query = Invoice::where('tenant_id', $tenantId)
            ->whereBetween('issue_date', [$startDate, $endDate])
            ->with(['order.location', 'order.department', 'customer', 'creator']);

        if ($locationId) {
            $query->whereHas('order', fn($q) => $q->where('location_id', $locationId));
        }
        if ($departmentId) {
            $query->whereHas('order', fn($q) => $q->where('department_id', $departmentId));
        }
        if ($statusFilter && $statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }
        if ($customerId) {
            $query->where('customer_id', $customerId);
        }
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhere('billing_name', 'like', "%{$search}%")
                  ->orWhere('billing_email', 'like', "%{$search}%");
            });
        }

        $invoices = $query->orderByDesc('issue_date')->orderByDesc('id')->get();

        // ─── Summary Statistics ──────────────────────────────────
        $summary = $this->buildInvoiceSummary($invoices);

        // ─── Status Breakdown ────────────────────────────────────
        $statusBreakdown = $invoices
            ->groupBy('status')
            ->map(function ($items, $status) use ($invoices) {
                return (object) [
                    'status'        => $status,
                    'label'         => Invoice::STATUS_LABELS[$status] ?? ucfirst($status),
                    'color'         => Invoice::STATUS_COLORS[$status] ?? 'secondary',
                    'icon'          => Invoice::STATUS_ICONS[$status] ?? 'bi-file-earmark',
                    'count'         => $items->count(),
                    'total_amount'  => $items->sum('total'),
                    'balance_due'   => $items->sum('balance_due'),
                    'percentage'    => $invoices->count() > 0
                        ? ($items->count() / $invoices->count()) * 100
                        : 0,
                ];
            })
            ->sortByDesc('total_amount')
            ->values();

        // ─── Aging Buckets (on outstanding invoices only) ────────
        $agingBuckets = $this->buildAgingBuckets($invoices);

        // ─── Monthly Trend ───────────────────────────────────────
        $monthlyTrend = $invoices
            ->groupBy(fn($inv) => $inv->issue_date->format('Y-m'))
            ->map(function ($items, $month) {
                return (object) [
                    'month'          => $month,
                    'month_label'    => Carbon::parse($month . '-01')->format('M Y'),
                    'invoice_count'  => $items->count(),
                    'total_amount'   => $items->sum('total'),
                    'paid_amount'    => $items->sum('amount_paid'),
                    'outstanding'    => $items->sum('balance_due'),
                ];
            })
            ->sortKeys()
            ->values();

        // ─── Top Customers by Invoice Value ──────────────────────
        $topCustomers = $invoices
            ->groupBy(fn($inv) => $inv->customer_id ?? 'guest_' . $inv->billing_name)
            ->map(function ($items) {
                $first = $items->first();
                return (object) [
                    'customer_id'    => $first->customer_id,
                    'customer_name'  => $first->customer_id
                        ? ($first->customer->full_name ?? $first->billing_name)
                        : $first->billing_name,
                    'invoice_count'  => $items->count(),
                    'total_billed'   => $items->sum('total'),
                    'total_paid'     => $items->sum('amount_paid'),
                    'outstanding'    => $items->sum('balance_due'),
                ];
            })
            ->sortByDesc('total_billed')
            ->take(10)
            ->values();

        // ─── Pagination ──────────────────────────────────────────
        $invoicesPaginated = $this->paginateCollection($invoices, $perPage, 'page');

        // ─── Filter Options ──────────────────────────────────────
        $locations   = Location::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $departments = Department::where('tenant_id', $tenantId)->where('isActive', true)->orderBy('name')->get(['id', 'name']);
        $customers   = Customer::where('tenant_id', $tenantId)->orderBy('first_name')->get(['id', 'first_name', 'last_name']);
        $statuses    = collect(Invoice::STATUS_LABELS)->map(fn($label, $value) => ['value' => $value, 'label' => $label])->values();

        return view('reports.invoices.summary', compact(
            'invoices', 'invoicesPaginated', 'summary', 'statusBreakdown', 'agingBuckets',
            'monthlyTrend', 'topCustomers', 'locations', 'departments', 'customers', 'statuses',
            'startDate', 'endDate', 'locationId', 'departmentId', 'statusFilter',
            'customerId', 'search', 'perPage'
        ));
    }

    // ============================================================
    // REPORT 2 — OUTSTANDING / AGING
    // ============================================================
    public function outstanding(Request $request)
    {
        $tenantId = $this->authorizeReports();

        // Outstanding = money not collected. Date range here is the ISSUE date
        // window we're looking at (not "when it was paid"), because we want
        // to see what's currently owed from invoices issued in that window.
        $startDate = $request->get('start_date', Carbon::now()->subMonths(6)->format('Y-m-d'));
        $endDate   = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);

        $locationId   = $request->get('location_id');
        $departmentId = $request->get('department_id');
        $customerId   = $request->get('customer_id');
        $agingBucket  = $request->get('aging_bucket', 'all');
        $onlyOverdue  = $request->boolean('only_overdue', false);
        $perPage      = (int) $request->get('per_page', 25);

        // ─── Only unpaid / partially-paid / overdue invoices ────
        $query = Invoice::where('tenant_id', $tenantId)
            ->whereBetween('issue_date', [$startDate, $endDate])
            ->whereNotIn('status', [Invoice::STATUS_PAID, Invoice::STATUS_VOID, Invoice::STATUS_CANCELLED])
            ->where('balance_due', '>', 0)
            ->with(['order.location', 'order.department', 'customer', 'creator']);

        if ($locationId)   $query->whereHas('order', fn($q) => $q->where('location_id', $locationId));
        if ($departmentId) $query->whereHas('order', fn($q) => $q->where('department_id', $departmentId));
        if ($customerId)   $query->where('customer_id', $customerId);

        $invoices = $query->orderBy('due_date')->orderByDesc('id')->get();

        // ─── Enrich with days overdue + aging bucket ────────────
        $invoices = $invoices->map(function ($inv) {
            $daysPastDue = $inv->due_date
                ? max(0, $inv->due_date->diffInDays(Carbon::now(), false))
                : 0;

            $inv->days_past_due = $inv->due_date && $inv->due_date->isPast()
                ? abs($daysPastDue)
                : 0;

            $inv->aging_bucket = $this->resolveAgingBucket($inv->days_past_due);
            $inv->is_overdue   = $inv->due_date && $inv->due_date->isPast() && $inv->balance_due > 0;

            return $inv;
        });

        // ─── Apply aging + overdue filters ──────────────────────
        if ($agingBucket && $agingBucket !== 'all') {
            $invoices = $invoices->where('aging_bucket', $agingBucket)->values();
        }
        if ($onlyOverdue) {
            $invoices = $invoices->where('is_overdue', true)->values();
        }

        // ─── Aging Bucket Breakdown ─────────────────────────────
        $agingSummary = $this->buildAgingBuckets($invoices, true);

        // ─── Summary Statistics ─────────────────────────────────
        $summary = (object) [
            'total_outstanding'      => $invoices->sum('balance_due'),
            'total_invoiced'         => $invoices->sum('total'),
            'total_collected'        => $invoices->sum('amount_paid'),
            'total_invoices'         => $invoices->count(),
            'overdue_invoices'       => $invoices->where('is_overdue', true)->count(),
            'overdue_amount'         => $invoices->where('is_overdue', true)->sum('balance_due'),
            'current_invoices'       => $invoices->where('is_overdue', false)->count(),
            'current_amount'         => $invoices->where('is_overdue', false)->sum('balance_due'),
            'average_outstanding'    => $invoices->count() > 0
                ? $invoices->sum('balance_due') / $invoices->count()
                : 0,
            'average_days_overdue'   => $invoices->where('is_overdue', true)->avg('days_past_due') ?? 0,
            'collection_rate'        => $invoices->sum('total') > 0
                ? ($invoices->sum('amount_paid') / $invoices->sum('total')) * 100
                : 0,
        ];

        // ─── Top Debtors ────────────────────────────────────────
        $topDebtors = $invoices
            ->groupBy(fn($inv) => $inv->customer_id ?? 'guest_' . $inv->billing_name)
            ->map(function ($items) {
                $first = $items->first();
                $overdue = $items->where('is_overdue', true);
                return (object) [
                    'customer_id'          => $first->customer_id,
                    'customer_name'        => $first->customer_id
                        ? ($first->customer->full_name ?? $first->billing_name)
                        : $first->billing_name,
                    'invoice_count'        => $items->count(),
                    'total_outstanding'    => $items->sum('balance_due'),
                    'overdue_count'        => $overdue->count(),
                    'overdue_amount'       => $overdue->sum('balance_due'),
                    'oldest_days_overdue'  => $items->max('days_past_due') ?? 0,
                ];
            })
            ->sortByDesc('total_outstanding')
            ->take(10)
            ->values();

        // ─── By Location ────────────────────────────────────────
        $byLocation = $invoices
            ->groupBy(fn($inv) => $inv->order->correct_location->name ?? 'Unknown')
            ->map(function ($items, $location) {
                return (object) [
                    'location'        => $location,
                    'invoice_count'   => $items->count(),
                    'outstanding'     => $items->sum('balance_due'),
                    'overdue_amount'  => $items->where('is_overdue', true)->sum('balance_due'),
                ];
            })
            ->sortByDesc('outstanding')
            ->values();

        // ─── Pagination ─────────────────────────────────────────
        $invoicesPaginated = $this->paginateCollection($invoices, $perPage, 'page');

        // ─── Filter Options ─────────────────────────────────────
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
            'invoices', 'invoicesPaginated', 'summary', 'agingSummary', 'topDebtors', 'byLocation',
            'locations', 'departments', 'customers', 'agingBucketOptions',
            'startDate', 'endDate', 'locationId', 'departmentId', 'customerId',
            'agingBucket', 'onlyOverdue', 'perPage'
        ));
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
        $recordedVia = $request->get('recorded_via', 'all');
        $statusFilter  = $request->get('status', 'all');
        $perPage       = (int) $request->get('per_page', 25);

        // ─── Query: invoice_payments is the money-in table for the invoice flow ──
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
                'paymentMethod',
                'processedBy',
            ])
            ->whereBetween('payment_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

        if ($paymentMethod) {
            $query->where('payment_method_id', $paymentMethod);
        }

        if ($statusFilter && $statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        $payments = $query->orderByDesc('payment_date')->get();

        // ─── Enrich each payment ────────────────────────────────
        $payments = $payments->map(function ($p) {
            return (object) [
                'id'                  => $p->id,
                'invoice_number'      => $p->invoice->invoice_number ?? 'N/A',
                'invoice_id'          => $p->invoice_id,
                'order_number'        => $p->invoice->order->order_number ?? 'N/A',
                'customer_name'       => $p->invoice->billing_name
                    ?? $p->invoice->order->customer_display_name
                    ?? 'Guest',
                'amount'              => $p->amount,
                'payment_method'      => $p->paymentMethod->name ?? ($p->payment_method_name ?? 'Unknown'),
                'payment_method_type' => $p->paymentMethod->type ?? 'other',
                'recorded_via'        => $p->order_payment_id ? 'pos' : 'manual',
                'status'              => $p->status,
                'reference'           => $p->reference_number ?? $p->transaction_id ?? 'N/A',
                'processed_at'        => $p->payment_date,
                'processed_by'        => $p->processedBy->name ?? 'System',
            ];
        });

        // ─── Summary ────────────────────────────────────────────
        $summary = (object) [
            'total_payments'   => $payments->count(),
            'total_amount'     => $payments->sum('amount'),
            'average_payment'  => $payments->count() > 0
                ? $payments->sum('amount') / $payments->count()
                : 0,
            'largest_payment'  => $payments->max('amount') ?? 0,
            'completed_count'  => $payments->where('status', 'completed')->count(),
            'completed_amount' => $payments->where('status', 'completed')->sum('amount'),
            'failed_count'     => $payments->where('status', 'failed')->count(),
            'failed_amount'    => $payments->where('status', 'failed')->sum('amount'),
            'refunded_count'   => $payments->where('status', 'refunded')->count(),
            'refunded_amount'  => $payments->where('status', 'refunded')->sum('amount'),
        ];

        // ─── By Payment Method ──────────────────────────────────
        $byMethod = $payments
            ->groupBy('payment_method')
            ->map(function ($items, $method) {
                return (object) [
                    'method'         => $method,
                    'type'           => $items->first()->payment_method_type,
                    'count'          => $items->count(),
                    'total_amount'   => $items->sum('amount'),
                    'average_amount' => $items->count() > 0
                        ? $items->sum('amount') / $items->count()
                        : 0,
                ];
            })
            ->sortByDesc('total_amount')
            ->values();

        // ─── By Recording Channel ──────────────────────────────
        $byChannel = $payments
            ->groupBy('recorded_via')
            ->map(function ($items, $channel) {
                return (object) [
                    'channel'      => $channel,
                    'count'        => $items->count(),
                    'total_amount' => $items->sum('amount'),
                ];
            })
            ->sortByDesc('total_amount')
            ->values();

        // ─── Daily Trend ────────────────────────────────────────
        $dailyTrend = $payments
            ->groupBy(fn($p) => $p->processed_at->format('Y-m-d'))
            ->map(function ($items, $date) {
                return (object) [
                    'date'          => $date,
                    'payment_count' => $items->count(),
                    'total_amount'  => $items->sum('amount'),
                ];
            })
            ->sortKeys()
            ->values();

        // ─── Pagination ─────────────────────────────────────────
        $paymentsPaginated = $this->paginateCollection($payments, $perPage, 'page');

        // ─── Filter Options ─────────────────────────────────────
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
            ['value' => 'all',                 'label' => 'All Statuses'],
            ['value' => 'completed',           'label' => 'Completed'],
            ['value' => 'pending',             'label' => 'Pending'],
            ['value' => 'failed',              'label' => 'Failed'],
            ['value' => 'refunded',            'label' => 'Refunded'],
            ['value' => 'pending_verification','label' => 'Pending Verification'],
        ];

        return view('reports.invoices.payments', compact(
            'payments', 'paymentsPaginated', 'summary', 'byMethod', 'byChannel', 'dailyTrend',
            'locations', 'departments', 'paymentMethods', 'channels', 'statuses',
            'startDate', 'endDate', 'locationId', 'departmentId', 'paymentMethod',
            'recordedVia', 'statusFilter', 'perPage'
        ));
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

        // ─── Query sends via invoices in range ──────────────────
        $query = InvoiceSend::where('tenant_id', $tenantId)
            ->whereHas('invoice', function ($q) use ($tenantId, $startDate, $endDate, $locationId, $departmentId) {
                $q->where('tenant_id', $tenantId)
                  ->whereBetween('issue_date', [$startDate, $endDate]);

                if ($locationId)   $q->whereHas('order', fn($o) => $o->where('location_id', $locationId));
                if ($departmentId) $q->whereHas('order', fn($o) => $o->where('department_id', $departmentId));
            })
            ->with(['invoice.order', 'invoice.customer', 'sentBy']);

        if ($channel && $channel !== 'all') {
            $query->where('channel', $channel);
        }
        if ($sendStatus && $sendStatus !== 'all') {
            $query->where('status', $sendStatus);
        }

        $sends = $query->orderByDesc('created_at')->get();

        // ─── Summary ────────────────────────────────────────────
        $summary = (object) [
            'total_sends'       => $sends->count(),
            'unique_invoices'   => $sends->pluck('invoice_id')->unique()->count(),
            'sent_count'        => $sends->where('status', 'sent')->count(),
            'delivered_count'   => $sends->where('status', 'delivered')->count(),
            'failed_count'      => $sends->where('status', 'failed')->count(),
            'bounced_count'     => $sends->where('status', 'bounced')->count(),
            'pending_count'     => $sends->where('status', 'pending')->count(),
            'delivery_rate'     => $sends->count() > 0
                ? ($sends->whereIn('status', ['sent', 'delivered'])->count() / $sends->count()) * 100
                : 0,
            'failure_rate'      => $sends->count() > 0
                ? ($sends->whereIn('status', ['failed', 'bounced'])->count() / $sends->count()) * 100
                : 0,
        ];

        // ─── By Channel ─────────────────────────────────────────
        $byChannel = $sends
            ->groupBy('channel')
            ->map(function ($items, $channel) {
                return (object) [
                    'channel'         => $channel,
                    'total'           => $items->count(),
                    'sent'            => $items->where('status', 'sent')->count(),
                    'delivered'       => $items->where('status', 'delivered')->count(),
                    'failed'          => $items->where('status', 'failed')->count(),
                    'bounced'         => $items->where('status', 'bounced')->count(),
                    'pending'         => $items->where('status', 'pending')->count(),
                    'success_rate'    => $items->count() > 0
                        ? ($items->whereIn('status', ['sent', 'delivered'])->count() / $items->count()) * 100
                        : 0,
                ];
            })
            ->values();

        // ─── Failure Reasons ────────────────────────────────────
        $failureReasons = $sends
            ->whereIn('status', ['failed', 'bounced'])
            ->whereNotNull('error_message')
            ->groupBy('error_message')
            ->map(function ($items, $reason) {
                return (object) [
                    'reason' => \Illuminate\Support\Str::limit($reason, 120),
                    'count'  => $items->count(),
                ];
            })
            ->sortByDesc('count')
            ->take(10)
            ->values();

        // ─── Daily Trend ────────────────────────────────────────
        $dailyTrend = $sends
            ->groupBy(fn($s) => $s->created_at->format('Y-m-d'))
            ->map(function ($items, $date) {
                return (object) [
                    'date'      => $date,
                    'sent'      => $items->where('status', 'sent')->count(),
                    'delivered' => $items->where('status', 'delivered')->count(),
                    'failed'    => $items->whereIn('status', ['failed', 'bounced'])->count(),
                ];
            })
            ->sortKeys()
            ->values();

        // ─── Pagination ─────────────────────────────────────────
        $sendsPaginated = $this->paginateCollection($sends, $perPage, 'page');

        // ─── Filter Options ─────────────────────────────────────
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
            'sends', 'sendsPaginated', 'summary', 'byChannel', 'failureReasons', 'dailyTrend',
            'locations', 'departments', 'channels', 'sendStatuses',
            'startDate', 'endDate', 'locationId', 'departmentId', 'channel', 'sendStatus', 'perPage'
        ));
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

        // ─── Query ──────────────────────────────────────────────
        $query = Invoice::where('tenant_id', $tenantId)
            ->whereBetween('issue_date', [$startDate, $endDate])
            ->with(['order.location', 'order.department', 'customer']);

        if ($locationId)   $query->whereHas('order', fn($q) => $q->where('location_id', $locationId));
        if ($departmentId) $query->whereHas('order', fn($q) => $q->where('department_id', $departmentId));

        $invoices = $query->orderByDesc('issue_date')->get();

        // ─── Enrich with time-to-payment metrics ────────────────
        $invoices = $invoices->map(function ($inv) {
            $inv->days_to_send  = ($inv->issue_date && $inv->sent_at)
                ? $inv->issue_date->diffInDays($inv->sent_at)
                : null;
            $inv->days_to_view  = ($inv->sent_at && $inv->viewed_at)
                ? $inv->sent_at->diffInDays($inv->viewed_at)
                : null;
            $inv->days_to_pay   = ($inv->issue_date && $inv->paid_at)
                ? $inv->issue_date->diffInDays($inv->paid_at)
                : null;
            $inv->days_to_due   = ($inv->issue_date && $inv->due_date)
                ? $inv->issue_date->diffInDays($inv->due_date)
                : null;
            return $inv;
        });

        // ─── Summary ────────────────────────────────────────────
        $paidInvoices = $invoices->whereNotNull('paid_at');

        $summary = (object) [
            'total_invoices'          => $invoices->count(),
            'paid_count'              => $paidInvoices->count(),
            'unpaid_count'            => $invoices->whereNull('paid_at')->count(),
            'void_count'              => $invoices->where('status', Invoice::STATUS_VOID)->count(),
            'avg_days_to_send'        => round($invoices->whereNotNull('days_to_send')->avg('days_to_send') ?? 0, 1),
            'avg_days_to_view'        => round($invoices->whereNotNull('days_to_view')->avg('days_to_view') ?? 0, 1),
            'avg_days_to_pay'         => round($paidInvoices->avg('days_to_pay') ?? 0, 1),
            'median_days_to_pay'      => $this->median($paidInvoices->pluck('days_to_pay')->filter()->values()->toArray()),
            'fastest_payment_days'    => $paidInvoices->min('days_to_pay') ?? 0,
            'slowest_payment_days'    => $paidInvoices->max('days_to_pay') ?? 0,
            'paid_within_terms'       => $invoices->filter(fn($i) =>
                $i->days_to_pay !== null && $i->days_to_due !== null && $i->days_to_pay <= $i->days_to_due
            )->count(),
            'paid_late'               => $invoices->filter(fn($i) =>
                $i->days_to_pay !== null && $i->days_to_due !== null && $i->days_to_pay > $i->days_to_due
            )->count(),
        ];

        // ─── Status Funnel ──────────────────────────────────────
        $statusFunnel = collect([
            (object) ['status' => 'draft',           'label' => 'Draft',           'count' => $invoices->where('status', Invoice::STATUS_DRAFT)->count()],
            (object) ['status' => 'sent',            'label' => 'Sent',            'count' => $invoices->where('status', Invoice::STATUS_SENT)->count()],
            (object) ['status' => 'viewed',          'label' => 'Viewed',          'count' => $invoices->where('status', Invoice::STATUS_VIEWED)->count()],
            (object) ['status' => 'partially_paid',  'label' => 'Partially Paid',  'count' => $invoices->where('status', Invoice::STATUS_PARTIALLY_PAID)->count()],
            (object) ['status' => 'paid',            'label' => 'Paid',            'count' => $invoices->where('status', Invoice::STATUS_PAID)->count()],
            (object) ['status' => 'overdue',         'label' => 'Overdue',         'count' => $invoices->where('status', Invoice::STATUS_OVERDUE)->count()],
            (object) ['status' => 'void',            'label' => 'Void',            'count' => $invoices->where('status', Invoice::STATUS_VOID)->count()],
        ])->filter(fn($s) => $s->count > 0)->values();

        // ─── Group Trend (monthly / weekly / daily) ─────────────
        $groupKey = match ($groupBy) {
            'daily'  => fn($inv) => $inv->issue_date->format('Y-m-d'),
            'weekly' => fn($inv) => $inv->issue_date->format('o-\WW'),
            default  => fn($inv) => $inv->issue_date->format('Y-m'),
        };

        $trend = $invoices
            ->groupBy($groupKey)
            ->map(function ($items, $period) {
                return (object) [
                    'period'              => $period,
                    'invoice_count'       => $items->count(),
                    'paid_count'          => $items->whereNotNull('paid_at')->count(),
                    'total_value'         => $items->sum('total'),
                    'paid_value'          => $items->sum('amount_paid'),
                    'outstanding_value'   => $items->sum('balance_due'),
                    'avg_days_to_pay'     => round($items->whereNotNull('days_to_pay')->avg('days_to_pay') ?? 0, 1),
                    'collection_rate'     => $items->sum('total') > 0
                        ? ($items->sum('amount_paid') / $items->sum('total')) * 100
                        : 0,
                ];
            })
            ->sortKeysDesc()
            ->values();

        // ─── Time-to-Pay Distribution ───────────────────────────
        $paymentSpeedBuckets = [
            'same_day'    => 0,
            '1_7_days'    => 0,
            '8_14_days'   => 0,
            '15_30_days'  => 0,
            '31_60_days'  => 0,
            '60_plus'     => 0,
        ];

        foreach ($paidInvoices as $inv) {
            $days = $inv->days_to_pay ?? 0;
            if ($days <= 0)         $paymentSpeedBuckets['same_day']++;
            elseif ($days <= 7)     $paymentSpeedBuckets['1_7_days']++;
            elseif ($days <= 14)    $paymentSpeedBuckets['8_14_days']++;
            elseif ($days <= 30)    $paymentSpeedBuckets['15_30_days']++;
            elseif ($days <= 60)    $paymentSpeedBuckets['31_60_days']++;
            else                    $paymentSpeedBuckets['60_plus']++;
        }

        // ─── Pagination ─────────────────────────────────────────
        $invoicesPaginated = $this->paginateCollection($invoices, $perPage, 'page');

        // ─── Filter Options ─────────────────────────────────────
        $locations   = Location::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $departments = Department::where('tenant_id', $tenantId)->where('isActive', true)->orderBy('name')->get(['id', 'name']);

        $groupOptions = [
            ['value' => 'daily',   'label' => 'Daily'],
            ['value' => 'weekly',  'label' => 'Weekly'],
            ['value' => 'monthly', 'label' => 'Monthly'],
        ];

        return view('reports.invoices.status-trends', compact(
            'invoices', 'invoicesPaginated', 'summary', 'statusFunnel', 'trend',
            'paymentSpeedBuckets', 'locations', 'departments', 'groupOptions',
            'startDate', 'endDate', 'locationId', 'departmentId', 'groupBy', 'perPage'
        ));
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