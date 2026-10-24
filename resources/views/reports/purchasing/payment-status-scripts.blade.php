<script>
window.PaymentStatus = (function () {
    'use strict';

    const ROUTE        = window.PS_ROUTES.data;
    const DETAIL_ROUTE = window.PS_ROUTES.detail;
    const CURRENCY     = window.REPORT_CURRENCY || '';
    const BOOT         = window.PS_BOOTSTRAP || {};

    const state = {
        startDate:     BOOT.startDate     || '',
        endDate:       BOOT.endDate       || '',
        paymentStatus: BOOT.paymentStatus || 'all',
        supplierId:    BOOT.supplierId    || '',
        perPage:       Number(BOOT.perPage || 15),
        page:          1,
    };

    let chart    = null;
    let drawerBs = null;
    let inFlight = false;
    const $ = (id) => document.getElementById(id);

    document.addEventListener('DOMContentLoaded', () => {
        if (!DETAIL_ROUTE) console.error('[payment-status] REPORT_ROUTES.detail is undefined');

        $('psApplyBtn')?.addEventListener('click', () => { readFilters(); state.page = 1; load(); });
        $('psClearBtn')?.addEventListener('click', resetFilters);
        $('psExportBtn')?.addEventListener('click', exportCsv);

        $('psTableBody')?.addEventListener('click', (e) => {
            const row = e.target.closest('tr.ps-row[data-id]');
            if (!row) return;
            openDrawer(row.dataset.id);
        });

        document.getElementById('psDrawer')?.addEventListener('hidden.bs.offcanvas', () => {
            $('psDrawerContent').innerHTML = '';
            $('psDrawerSubtitle').textContent = '';
        });

        load();
    });

    function readFilters() {
        state.startDate     = $('psStartDate')?.value      || '';
        state.endDate       = $('psEndDate')?.value        || '';
        state.paymentStatus = $('psStatusFilter')?.value   || 'all';
        state.supplierId    = $('psSupplier')?.value       || '';
    }

    function resetFilters() {
        state.startDate = state.endDate = state.supplierId = '';
        state.paymentStatus = 'all';
        state.page = 1;
        if ($('psStartDate'))    $('psStartDate').value    = '';
        if ($('psEndDate'))      $('psEndDate').value      = '';
        if ($('psStatusFilter')) $('psStatusFilter').value = 'all';
        if (window.jQuery) jQuery('#psSupplier').val(null).trigger('change.select2');
        load();
    }

    async function load() {
        if (inFlight) return;
        inFlight = true;
        showSkeleton(true);

        const params = new URLSearchParams({ page: state.page, per_page: state.perPage });
        if (state.startDate)     params.set('start_date',     state.startDate);
        if (state.endDate)       params.set('end_date',       state.endDate);
        if (state.paymentStatus) params.set('payment_status', state.paymentStatus);
        if (state.supplierId)    params.set('supplier_id',    state.supplierId);

        try {
            const res = await fetch(`${ROUTE}?${params.toString()}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Failed');

            render(json.data);
            showSkeleton(false);
        } catch (err) {
            console.error('[payment-status] load failed:', err);
            window.toastr?.error('Failed to load payment status.');
            showSkeleton(false);
        } finally {
            inFlight = false;
        }
    }

    function showSkeleton(v) {
        $('psSkeleton')?.classList.toggle('d-none', !v);
        $('psContent')?.classList.toggle('d-none', v);
    }

    function render(p) {
        renderSummary(p.summary);
        renderChart(p.chart);
        renderTable(p.items, p.pagination);
    }

    function renderSummary(s) {
        const set = (id, v) => { const el = $(id); if (el) el.textContent = v; };

        set('sumDue',        money(s.total_amount_due));
        set('sumPaid',       money(s.total_amount_paid));
        set('sumOverdue',    money(s.overdue_amount));
        set('sumOverdueCount', s.overdue_count + ' overdue payments');
        set('sumTotalItems', fmtNum(s.total_items, 0));
    }

    function renderChart(rows) {
        const el = $('psChart');
        const empty = $('psChartEmpty');
        if (!el) return;

        if (!rows || rows.length === 0) {
            el.classList.add('d-none'); empty?.classList.remove('d-none');
            if (chart) { chart.destroy(); chart = null; }
            return;
        }
        el.classList.remove('d-none'); empty?.classList.add('d-none');

        $('psPeriodBadge').textContent = `${state.startDate} → ${state.endDate}`;

        const totalAmount = rows.reduce((s, r) => s + Number(r.value), 0);

        const options = {
            series: rows.map(r => Number(r.value)),
            chart:  { type: 'donut', height: 350, toolbar: { show: true }, fontFamily: 'inherit' },
            labels: rows.map(r => r.label),
            colors: rows.map(r => r.color),
            legend: {
                position: 'bottom', fontSize: '12px',
                formatter: (name, opts) => {
                    const total = opts.w.config.series.reduce((a, b) => a + b, 0);
                    const pct = total > 0 ? ((opts.w.config.series[opts.seriesIndex] / total) * 100).toFixed(1) : 0;
                    return `${name}: ${pct}%`;
                },
            },
            plotOptions: { pie: { donut: {
                size: '65%',
                labels: {
                    show: true,
                    total: {
                        show: true, label: 'Total',
                        formatter: () => CURRENCY + totalAmount.toLocaleString(),
                    },
                },
            }}},
            tooltip: {
                y: {
                    formatter: v => {
                        const pct = totalAmount > 0 ? ((v / totalAmount) * 100).toFixed(1) : 0;
                        return money(v) + ' (' + pct + '%)';
                    },
                },
            },
            dataLabels: {
                enabled: true,
                formatter: v => v.toFixed(1) + '%',
                style: { fontSize: '11px', fontWeight: 'bold' },
            },
        };

        if (chart) chart.updateOptions(options, false, true);
        else { chart = new ApexCharts(el, options); chart.render(); }
    }

    function renderTable(rows, pagination) {
        const body = $('psTableBody');
        const empty = $('psEmpty');
        const wrap = $('psPaginationWrap');
        const badge = $('psBadge');
        if (!body) return;

        if (!rows || rows.length === 0) {
            body.innerHTML = ''; empty?.classList.remove('d-none'); wrap?.classList.add('d-none');
            if (badge) badge.textContent = '';
            return;
        }
        empty?.classList.add('d-none');
        wrap?.classList.remove('d-none');
        if (badge) badge.textContent = `Showing ${pagination.from} of ${pagination.total}`;

        body.innerHTML = rows.map((r, i) => {
            const idx = (pagination.from || 1) + i;
            const rowClass = r.is_overdue ? 'ps-row is-overdue' : 'ps-row';

            return `
                <tr class="${rowClass}" data-id="${r.id}">
                    <td class="ps-4 fw-bold">${idx}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-35px me-2">
                                <div class="symbol-label bg-light-primary text-primary fw-bold">PO</div>
                            </div>
                            <div>
                                <div class="fw-bold">${esc(r.po_number || '—')}</div>
                                <small class="text-muted">${esc(r.po_created_at || '')}</small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="fw-bold">${esc(r.supplier_name || '—')}</div>
                        ${r.supplier_contact ? `<small class="text-muted">${esc(r.supplier_contact)}</small>` : ''}
                    </td>
                    <td>
                        <div class="fw-bold">${esc(r.product_name || '—')}</div>
                        <small class="text-muted">${esc(r.sku || '')}</small>
                    </td>
                    <td class="text-end">
                        <span class="fw-bold text-primary">${money(r.total_cost)}</span>
                        ${r.payment_status === 'partial' ? `<div class="text-muted fs-8">Paid: ${money(r.amount_paid)}</div>` : ''}
                    </td>
                    <td>
                        ${r.payment_date
                            ? `<span class="fw-bold">${esc(r.payment_date)}</span>`
                            : `<span class="text-muted">Not paid</span>`}
                    </td>
                    <td>
                        ${r.payment_method
                            ? `<span class="badge badge-light-info">${esc(r.payment_method)}</span>`
                            : `<span class="text-muted">Not specified</span>`}
                        ${r.payment_reference ? `<div class="text-muted fs-8 mt-1">${esc(r.payment_reference)}</div>` : ''}
                    </td>
                    <td>
                        ${r.due_date
                            ? `<span class="fw-bold ${r.is_overdue ? 'text-danger' : 'text-primary'}">${esc(r.due_date)}</span>`
                            : `<span class="text-muted">Not set</span>`}
                    </td>
                    <td class="text-center">
                        <span class="badge badge-light-${r.days_color}">${esc(r.days_info || '—')}</span>
                    </td>
                    <td class="text-end pe-4">
                        <span class="badge badge-light-${r.status_color}">${esc(r.status_label)}</span>
                    </td>
                </tr>
            `;
        }).join('');

        renderPagination(pagination);
    }

    function renderPagination(p) {
        const info = $('psPaginationInfo');
        const ul   = $('psPagination');
        if (!ul || !info) return;

        info.textContent = `Showing ${p.from} to ${p.to} of ${p.total}`;
        ul.innerHTML = '';
        const add = (page, label, active = false, disabled = false) => {
            const li = document.createElement('li');
            li.className = `page-item ${active ? 'active' : ''} ${disabled ? 'disabled' : ''}`;
            const a = document.createElement('a');
            a.className = 'page-link'; a.href = '#'; a.textContent = label;
            a.addEventListener('click', (e) => {
                e.preventDefault();
                if (disabled || active) return;
                state.page = page; load();
            });
            li.appendChild(a); ul.appendChild(li);
        };
        add(p.current_page - 1, 'Prev', false, p.current_page <= 1);
        for (let i = Math.max(1, p.current_page - 2); i <= Math.min(p.last_page, p.current_page + 2); i++) {
            add(i, i, i === p.current_page);
        }
        add(p.current_page + 1, 'Next', false, p.current_page >= p.last_page);
    }

    // ─── Drawer ──────────────────────────────────────────────
    function getDrawer() {
        const el = document.getElementById('psDrawer');
        if (!el) return null;
        drawerBs = drawerBs || bootstrap.Offcanvas.getOrCreateInstance(el);
        return drawerBs;
    }

    async function openDrawer(id) {
        const inst = getDrawer();
        if (!inst) return;

        if (!DETAIL_ROUTE) {
            window.toastr?.error('Detail endpoint not configured.');
            return;
        }

        $('psDrawerSkeleton')?.classList.remove('d-none');
        const content = $('psDrawerContent');
        if (content) { content.classList.add('d-none'); content.innerHTML = ''; }
        $('psDrawerSubtitle').textContent = '';
        inst.show();

        try {
            const res = await fetch(DETAIL_ROUTE.replace('__ID__', id), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await res.json();
            if (!res.ok || !json.success) throw new Error(json.message || 'Failed');
            renderDrawer(json.data);
        } catch (err) {
            console.error('[ps-drawer] load failed:', err);
            $('psDrawerSkeleton')?.classList.add('d-none');
            if (content) {
                content.classList.remove('d-none');
                content.innerHTML = `<div class="alert alert-danger">${esc(err.message || 'Failed to load')}</div>`;
            }
        }
    }

    function renderDrawer(d) {
        $('psDrawerSkeleton')?.classList.add('d-none');
        $('psDrawerSubtitle').textContent = `${d.po?.po_number || '—'} · ${d.product_name || ''}`;

        const content = $('psDrawerContent');
        if (!content) return;
        content.classList.remove('d-none');

        const balanceCls = Number(d.balance) > 0 ? 'warning' : 'success';

        content.innerHTML = `
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h4 class="fw-bold mb-1">${esc(d.product_name || '—')}</h4>
                    <div class="text-muted fs-7">${esc(d.sku || '')} · ${esc(d.variant_product || '')}</div>
                </div>
                <span class="badge badge-light-${d.status_color} fs-6 py-2 px-3">${esc(d.status_label)}</span>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="card card-flush bg-light-secondary border border-secondary border-dashed">
                        <div class="card-body text-center py-4">
                            <span class="text-muted fs-8">Total</span>
                            <div class="fs-4 fw-bold">${money(d.total_cost)}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card card-flush bg-light-success border border-success border-dashed">
                        <div class="card-body text-center py-4">
                            <span class="text-muted fs-8">Paid</span>
                            <div class="fs-4 fw-bold text-success">${money(d.amount_paid)}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card card-flush bg-light-${balanceCls} border border-${balanceCls} border-dashed">
                        <div class="card-body text-center py-4">
                            <span class="text-muted fs-8">Balance</span>
                            <div class="fs-4 fw-bold text-${balanceCls}">${money(d.balance)}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card card-flush bg-light-primary border border-primary border-dashed">
                        <div class="card-body text-center py-4">
                            <span class="text-muted fs-8">Quantity</span>
                            <div class="fs-4 fw-bold text-primary">${fmtNum(d.quantity, 0)}</div>
                        </div>
                    </div>
                </div>
            </div>

            <h6 class="fw-bold mb-3"><i class="bi bi-credit-card me-2 text-primary"></i>Payment</h6>
            ${!d.has_real_payment && d.payment_status === 'paid' ? `
                <div class="alert alert-warning py-2 px-3 mb-3 fs-8">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Status reports <strong>paid</strong>, but no payment transaction was found.
                </div>
            ` : ''}
            <table class="table table-sm table-bordered mb-4">
                <tbody>
                    <tr><th class="w-50">Status</th><td><span class="badge badge-light-${d.status_color}">${esc(d.status_label)}</span></td></tr>
                    <tr><th>Amount paid</th><td class="text-success fw-bold">${money(d.amount_paid)}</td></tr>
                    <tr><th>Balance</th><td class="text-warning fw-bold">${money(d.balance)}</td></tr>
                    <tr><th>Payment method</th><td>${esc(d.payment_method || 'Not specified')}</td></tr>
                    <tr><th>Payment date</th><td>${esc(d.payment_date || 'Not paid')}</td></tr>
                    <tr><th>Due date</th><td>${esc(d.due_date || 'Not set')}</td></tr>
                    ${d.payment_reference
                        ? `<tr><th>Reference</th><td><span class="badge badge-light-dark">${esc(d.payment_reference)}</span></td></tr>`
                        : ''}
                    <tr><th>Unit cost</th><td class="text-end">${money(d.unit_cost)}</td></tr>
                </tbody>
            </table>

            ${d.po ? `
                <h6 class="fw-bold mb-3"><i class="bi bi-clipboard me-2 text-primary"></i>Purchase Order</h6>
                <table class="table table-sm table-bordered mb-4">
                    <tbody>
                        <tr><th class="w-50">PO number</th><td>${esc(d.po.po_number || '—')}</td></tr>
                        <tr><th>PO status</th><td>${esc(d.po.status_label)}</td></tr>
                        <tr><th>Location</th><td>${esc(d.po.location_name)}</td></tr>
                        <tr><th>Created at</th><td>${esc(d.po.created_at || '—')}</td></tr>
                        <tr><th>Created by</th><td>${esc(d.po.created_by)}</td></tr>
                    </tbody>
                </table>

                <h6 class="fw-bold mb-3"><i class="bi bi-shop me-2 text-primary"></i>Supplier</h6>
                <div class="border rounded p-3 mb-4">
                    <div class="fw-bold">${esc(d.po.supplier.name || '—')}</div>
                    ${d.po.supplier.contact_person ? `<div class="text-muted fs-7">${esc(d.po.supplier.contact_person)}</div>` : ''}
                    ${d.po.supplier.email ? `<div class="text-muted fs-7"><i class="bi bi-envelope me-1"></i>${esc(d.po.supplier.email)}</div>` : ''}
                    ${d.po.supplier.phone ? `<div class="text-muted fs-7"><i class="bi bi-telephone me-1"></i>${esc(d.po.supplier.phone)}</div>` : ''}
                </div>
            ` : ''}

            ${d.notes ? `
                <h6 class="fw-bold mb-3"><i class="bi bi-chat-left-text me-2 text-primary"></i>Notes</h6>
                <div class="border rounded p-3 mb-4 text-gray-800">${esc(d.notes)}</div>
            ` : ''}
        `;
    }

    function exportCsv() {
        const params = new URLSearchParams();
        if (state.startDate)     params.set('start_date',     state.startDate);
        if (state.endDate)       params.set('end_date',       state.endDate);
        if (state.paymentStatus) params.set('payment_status', state.paymentStatus);
        if (state.supplierId)    params.set('supplier_id',    state.supplierId);
        window.location.href = `/reports/purchasing/payment-status?${params.toString()}&export=1`;
    }

    function esc(t) {
        if (t === null || t === undefined) return '';
        const d = document.createElement('div');
        d.textContent = String(t); return d.innerHTML;
    }
    function fmtNum(n, d = 0) {
        return Number(n ?? 0).toLocaleString('en-US', {
            minimumFractionDigits: d, maximumFractionDigits: d,
        });
    }
    function money(n) { return CURRENCY + fmtNum(n, 2); }

    return { load };
})();
</script>