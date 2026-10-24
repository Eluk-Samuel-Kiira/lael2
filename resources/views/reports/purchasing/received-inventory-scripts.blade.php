<script>
window.ReceivedInventory = (function () {
    'use strict';

    const ROUTE    = window.RI_ROUTES.data;
    const CURRENCY = window.REPORT_CURRENCY || '';
    const BOOT     = window.RI_BOOTSTRAP || {};
    const I18N     = window.RI_I18N || {};

    const state = {
        startDate:       BOOT.startDate       || '',
        endDate:         BOOT.endDate         || '',
        supplierId:      BOOT.supplierId      || '',
        variantId:       BOOT.variantId       || '',
        batchNumber:     BOOT.batchNumber     || '',
        includeExpiring: !!BOOT.includeExpiring,
        perPage:         Number(BOOT.perPage  || 15),
        page:            1,
    };

    let inFlight = false;
    const $ = (id) => document.getElementById(id);

    document.addEventListener('DOMContentLoaded', () => {
        $('riApplyBtn')?.addEventListener('click', () => { readFilters(); state.page = 1; load(); });
        $('riClearBtn')?.addEventListener('click', resetFilters);
        $('riExportBtn')?.addEventListener('click', exportCsv);
        load();
    });

    function readFilters() {
        state.startDate       = $('riStartDate')?.value   || '';
        state.endDate         = $('riEndDate')?.value     || '';
        state.supplierId      = $('riSupplier')?.value    || '';
        state.variantId       = $('riVariant')?.value     || '';
        state.batchNumber     = $('riBatch')?.value       || '';
        state.includeExpiring = !!$('riExpiring')?.checked;
    }

    function resetFilters() {
        state.startDate = state.endDate = state.supplierId = state.variantId = state.batchNumber = '';
        state.includeExpiring = false;
        state.page = 1;
        if ($('riStartDate'))  $('riStartDate').value = '';
        if ($('riEndDate'))    $('riEndDate').value = '';
        if ($('riExpiring'))   $('riExpiring').checked = false;
        if (window.jQuery) {
            jQuery('#riSupplier').val(null).trigger('change.select2');
            jQuery('#riVariant').val(null).trigger('change.select2');
            jQuery('#riBatch').val(null).trigger('change.select2');
        }
        load();
    }

    async function load() {
        if (inFlight) return;
        inFlight = true;
        showSkeleton(true);

        const params = new URLSearchParams({ page: state.page, per_page: state.perPage });
        if (state.startDate)       params.set('start_date', state.startDate);
        if (state.endDate)         params.set('end_date', state.endDate);
        if (state.supplierId)      params.set('supplier_id', state.supplierId);
        if (state.variantId)       params.set('variant_id', state.variantId);
        if (state.batchNumber)     params.set('batch_number', state.batchNumber);
        if (state.includeExpiring) params.set('include_expiring', '1');

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
            console.error('[received-inventory] load failed:', err);
            window.toastr?.error('Failed to load received inventory.');
            showSkeleton(false);
        } finally {
            inFlight = false;
        }
    }

    function showSkeleton(v) {
        $('riSkeleton')?.classList.toggle('d-none', !v);
        $('riContent')?.classList.toggle('d-none', v);
    }

    function render(p) {
        renderSummary(p.summary);
        renderBatch(p.batch_analysis, p.meta);
        renderTable(p.items, p.pagination, p.meta);
    }

    function renderSummary(s) {
        const cards = [
            { title: I18N.totalReceivedItems, value: fmtNum(s.total_items, 0),   color: 'primary', icon: 'bi-box-seam',       desc: I18N.itemsReceived },
            { title: I18N.totalQuantity,      value: fmtNum(s.total_quantity, 0), color: 'success', icon: 'bi-arrow-up',       desc: I18N.unitsReceived },
            { title: I18N.totalValue,         value: money(s.total_value),       color: 'info',    icon: 'bi-currency-dollar', desc: I18N.totalReceivedValue },
            { title: I18N.expiringSoonLabel,  value: fmtNum(s.expiring_soon, 0), color: 'warning', icon: 'bi-clock',          desc: I18N.itemsExpiring30 },
        ];

        $('riSummaryCards').innerHTML = cards.map(c => `
            <div class="col-md-6 col-lg-3">
                <div class="card card-flush bg-light-${c.color} border border-${c.color} border-dashed h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-4">
                            <div class="symbol symbol-60px flex-shrink-0">
                                <div class="symbol-label bg-${c.color} bg-opacity-10">
                                    <i class="bi ${c.icon} fs-2x text-${c.color}"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="text-gray-600 fw-semibold fs-7">${esc(c.title)}</div>
                                <div class="fs-2 fw-bold text-${c.color} lh-1 mt-1" style="word-break:break-word;">${esc(c.value)}</div>
                                <div class="text-muted fs-8 mt-1">${esc(c.desc)}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    }

    function renderBatch(batches) {
        const wrap  = $('riBatchWrap');
        const body  = $('riBatchBody');
        const badge = $('riBatchBadge');

        if (!batches || batches.length === 0) {
            wrap?.classList.add('d-none');
            return;
        }
        wrap?.classList.remove('d-none');
        if (badge) badge.textContent = `${batches.length} batches`;

        body.innerHTML = batches.map(b => {
            const days = b.days_to_expiry;
            let color = 'success', text = I18N.notExpiring;
            if (days !== null) {
                if (days <= 0) {
                    color = 'danger';  text = I18N.expired;
                } else if (days <= 30) {
                    color = 'warning'; text = I18N.expiringSoon;
                } else {
                    color = 'success'; text = I18N.notExpiringSoon;
                }
            }
            const rowCls = days !== null && days <= 0 ? 'table-danger'
                         : (days !== null && days <= 30 ? 'table-warning' : '');
            return `
                <tr class="${rowCls}">
                    <td class="ps-4 fw-bold">${esc(b.batch_number || I18N.noBatch)}</td>
                    <td>${b.expiry_date
                        ? `<span class="fw-semibold">${esc(b.expiry_date)}</span>`
                        : `<span class="text-muted">${esc(I18N.noExpiry)}</span>`}</td>
                    <td>${days !== null
                        ? `<span class="badge badge-light-${color}">${Math.abs(days)} days</span>`
                        : '-'}</td>
                    <td><span class="fw-bold text-primary">${fmtNum(b.total_quantity, 0)}</span></td>
                    <td><span class="fw-bold text-success">${money(b.total_value)}</span></td>
                    <td class="pe-4"><span class="badge badge-${color}">${esc(text)}</span></td>
                </tr>
            `;
        }).join('');
    }

    function renderTable(rows, pagination) {
        const body  = $('riTableBody');
        const empty = $('riEmpty');
        const wrap  = $('riPaginationWrap');
        const badge = $('riBadge');

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
            return `
                <tr>
                    <td class="ps-4 fw-bold">${idx}</td>
                    <td>
                        <div class="fw-bold">${esc(r.po_number || '—')}</div>
                        <small class="text-muted">${esc(r.po_date || '')}</small>
                    </td>
                    <td>
                        <div class="fw-bold">${esc(r.product_name || '—')}</div>
                        <small class="text-muted">${esc(r.sku || '')}</small>
                        ${r.variant_product ? `<div class="text-muted fs-8">${esc(r.variant_product)}</div>` : ''}
                    </td>
                    <td>
                        <div class="fw-bold">${esc(r.supplier_name || '—')}</div>
                        ${r.supplier_contact ? `<small class="text-muted">${esc(r.supplier_contact)}</small>` : ''}
                    </td>
                    <td class="text-end"><span class="fw-bold">${fmtNum(r.quantity, 0)}</span></td>
                    <td class="text-end"><span class="text-primary fw-bold">${money(r.unit_cost)}</span></td>
                    <td class="text-end"><span class="text-success fw-bold">${money(r.total_cost)}</span></td>
                    <td>${r.batch_number
                        ? `<span class="badge badge-light-info">${esc(r.batch_number)}</span>`
                        : `<span class="text-muted">${esc(I18N.noBatch)}</span>`}</td>
                    <td>${r.expiry_date
                        ? `<span class="fw-semibold ${r.expiry_color !== 'success' ? 'text-' + r.expiry_color : ''}">${esc(r.expiry_date)}</span>`
                        : `<span class="text-muted">${esc(I18N.noExpiry)}</span>`}</td>
                    <td>${r.received_by
                        ? `<div class="fw-bold">${esc(r.received_by)}</div>
                           ${r.received_by_email ? `<small class="text-muted">${esc(r.received_by_email)}</small>` : ''}`
                        : `<span class="text-muted">—</span>`}</td>
                    <td><span class="text-muted">${esc(r.received_at || '—')}</span></td>
                    <td class="text-end pe-4">
                        ${r.days_to_expiry !== null
                            ? `<span class="badge badge-${r.expiry_color}">${esc(r.expiry_text)}</span>
                               <div class="text-muted fs-8 mt-1">${Math.abs(r.days_to_expiry)} days</div>`
                            : '<span class="text-muted">-</span>'}
                    </td>
                </tr>
            `;
        }).join('');

        renderPagination(pagination);
    }

    function renderPagination(p) {
        const info = $('riPaginationInfo');
        const ul   = $('riPagination');
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

    function exportCsv() {
        const params = new URLSearchParams();
        if (state.startDate)       params.set('start_date', state.startDate);
        if (state.endDate)         params.set('end_date', state.endDate);
        if (state.supplierId)      params.set('supplier_id', state.supplierId);
        if (state.variantId)       params.set('variant_id', state.variantId);
        if (state.batchNumber)     params.set('batch_number', state.batchNumber);
        if (state.includeExpiring) params.set('include_expiring', '1');
        window.location.href = `/reports/purchasing/received-inventory?${params.toString()}&export=csv`;
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