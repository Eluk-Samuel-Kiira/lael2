<script>
window.PurchaseReceipts = (function () {
    'use strict';

    const ROUTE        = window.PR_ROUTES.data;
    const DETAIL_ROUTE = window.PR_ROUTES.detail;
    const CURRENCY     = window.REPORT_CURRENCY || '';
    const BOOT         = window.PR_BOOTSTRAP || {};

    const state = {
        startDate:  BOOT.startDate  || '',
        endDate:    BOOT.endDate    || '',
        supplierId: BOOT.supplierId || '',
        locationId: BOOT.locationId || '',
        perPage:    Number(BOOT.perPage || 15),
        page:       1,
    };

    let drawerBs = null;
    let inFlight = false;
    const $ = (id) => document.getElementById(id);

    document.addEventListener('DOMContentLoaded', () => {
        if (!DETAIL_ROUTE) console.error('[purchasing-receipts] REPORT_ROUTES.detail is undefined');

        $('prApplyBtn')?.addEventListener('click', () => { readFilters(); state.page = 1; load(); });
        $('prClearBtn')?.addEventListener('click', resetFilters);
        $('prExportBtn')?.addEventListener('click', exportCsv);

        $('prTableBody')?.addEventListener('click', (e) => {
            const row = e.target.closest('tr.pr-row[data-id]');
            if (!row) return;
            openDrawer(row.dataset.id);
        });

        document.getElementById('prDrawer')?.addEventListener('hidden.bs.offcanvas', () => {
            $('prDrawerContent').innerHTML = '';
            $('prDrawerSubtitle').textContent = '';
        });

        load();
    });

    function readFilters() {
        state.startDate  = $('prStartDate')?.value  || '';
        state.endDate    = $('prEndDate')?.value    || '';
        state.supplierId = $('prSupplier')?.value   || '';
        state.locationId = $('prLocation')?.value   || '';
    }

    function resetFilters() {
        state.startDate = state.endDate = state.supplierId = state.locationId = '';
        state.page = 1;
        if ($('prStartDate')) $('prStartDate').value = '';
        if ($('prEndDate'))   $('prEndDate').value   = '';
        if (window.jQuery) jQuery('#prSupplier, #prLocation').val(null).trigger('change.select2');
        load();
    }

    async function load() {
        if (inFlight) return;
        inFlight = true;
        showSkeleton(true);

        const params = new URLSearchParams({ page: state.page, per_page: state.perPage });
        if (state.startDate)  params.set('start_date',  state.startDate);
        if (state.endDate)    params.set('end_date',    state.endDate);
        if (state.supplierId) params.set('supplier_id', state.supplierId);
        if (state.locationId) params.set('location_id', state.locationId);

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
            console.error('[purchasing-receipts] load failed:', err);
            window.toastr?.error('Failed to load purchase receipts.');
            showSkeleton(false);
        } finally {
            inFlight = false;
        }
    }

    function showSkeleton(v) {
        $('prSkeleton')?.classList.toggle('d-none', !v);
        $('prContent')?.classList.toggle('d-none', v);
    }

    function render(p) {
        renderSummary(p.summary);
        renderTable(p.items, p.pagination);
    }

    function renderSummary(s) {
        $('sumTotalReceipts').textContent = fmtNum(s.total_receipts, 0);
        $('sumTotalQuantity').textContent = fmtNum(s.total_quantity, 0);
        $('sumUniqueItems').textContent   = fmtNum(s.unique_items, 0);
        $('sumTotalValue').textContent    = money(s.total_value);
    }

    function renderTable(rows, pagination) {
        const body = $('prTableBody');
        const empty = $('prEmpty');
        const wrap = $('prPaginationWrap');
        const badge = $('prBadge');
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
            return `
                <tr class="pr-row" data-id="${r.id}">
                    <td class="ps-4 fw-bold">${idx}</td>
                    <td class="fw-semibold">${esc(r.receipt_number)}</td>
                    <td>
                        <div class="fw-bold">${esc(r.po_number || '—')}</div>
                        ${r.po_date ? `<small class="text-muted">${esc(r.po_date)}</small>` : ''}
                    </td>
                    <td>
                        <div class="fw-bold">${esc(r.supplier_name || '—')}</div>
                        ${r.supplier_contact ? `<small class="text-muted">${esc(r.supplier_contact)}</small>` : ''}
                    </td>
                    <td>${esc(r.location_name || '—')}</td>
                    <td>${esc(r.received_at || '—')}</td>
                    <td>${esc(r.received_by || '—')}</td>
                    <td class="text-center"><span class="badge badge-light-info">${fmtNum(r.items_count, 0)}</span></td>
                    <td class="text-end fw-bold text-primary">${fmtNum(r.total_quantity, 0)}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-light-primary">
                            <i class="bi bi-eye fs-5"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');

        renderPagination(pagination);
    }

    function renderPagination(p) {
        const info = $('prPaginationInfo');
        const ul   = $('prPagination');
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
        const el = document.getElementById('prDrawer');
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

        $('prDrawerSkeleton')?.classList.remove('d-none');
        const content = $('prDrawerContent');
        if (content) { content.classList.add('d-none'); content.innerHTML = ''; }
        $('prDrawerSubtitle').textContent = '';
        inst.show();

        try {
            const res = await fetch(DETAIL_ROUTE.replace('__ID__', id), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await res.json();
            if (!res.ok || !json.success) throw new Error(json.message || 'Failed');
            renderDrawer(json.data);
        } catch (err) {
            console.error('[pr-drawer] load failed:', err);
            $('prDrawerSkeleton')?.classList.add('d-none');
            if (content) {
                content.classList.remove('d-none');
                content.innerHTML = `<div class="alert alert-danger">${esc(err.message || 'Failed to load')}</div>`;
            }
        }
    }

    function renderDrawer(d) {
        $('prDrawerSkeleton')?.classList.add('d-none');
        $('prDrawerSubtitle').textContent = `${d.receipt_number} · ${d.received_at || ''}`;

        const content = $('prDrawerContent');
        if (!content) return;
        content.classList.remove('d-none');

        const itemsHtml = (d.items || []).map((item, i) => {
            const recvPct = item.ordered_quantity > 0
                ? Math.round((item.received_quantity / item.ordered_quantity) * 100)
                : 0;
            const recvCls = item.pending_quantity === 0
                ? 'success'
                : (item.received_quantity > 0 ? 'warning' : 'secondary');

            return `
                <tr>
                    <td>${i + 1}</td>
                    <td>
                        <div class="fw-bold">${esc(item.product_name || '—')}</div>
                        ${item.variant_name ? `<small class="text-muted">${esc(item.variant_product || '')} · ${esc(item.variant_name)}</small>` : ''}
                    </td>
                    <td><small>${esc(item.sku || '—')}</small></td>
                    <td class="text-end">${fmtNum(item.ordered_quantity, 0)}</td>
                    <td class="text-end">
                        <span class="badge badge-light-${recvCls}">${fmtNum(item.received_quantity, 0)}</span>
                    </td>
                    <td class="text-end">${fmtNum(item.pending_quantity, 0)}</td>
                    <td class="text-end">${money(item.unit_cost)}</td>
                    <td class="text-end fw-bold">${money(item.total_cost)}</td>
                    <td><small>${esc(item.batch_number || '—')}</small></td>
                    <td><small>${esc(item.expiry_date || '—')}</small></td>
                </tr>
            `;
        }).join('');

        content.innerHTML = `
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h4 class="fw-bold mb-1">${esc(d.receipt_number)}</h4>
                    <div class="text-muted fs-7">${esc(d.supplier.name)} · ${esc(d.location)}</div>
                </div>
                <span class="badge badge-light-primary fs-6 py-2 px-3">${esc(d.received_at || '—')}</span>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <h6 class="fw-bold mb-2"><i class="bi bi-shop me-2 text-primary"></i>Supplier</h6>
                    <div class="border rounded p-3">
                        <div class="fw-bold">${esc(d.supplier.name)}</div>
                        ${d.supplier.contact_person ? `<div class="text-muted fs-7">${esc(d.supplier.contact_person)}</div>` : ''}
                        ${d.supplier.email ? `<div class="text-muted fs-7"><i class="bi bi-envelope me-1"></i>${esc(d.supplier.email)}</div>` : ''}
                        ${d.supplier.phone ? `<div class="text-muted fs-7"><i class="bi bi-telephone me-1"></i>${esc(d.supplier.phone)}</div>` : ''}
                    </div>
                </div>
                <div class="col-md-6">
                    <h6 class="fw-bold mb-2"><i class="bi bi-info-circle me-2 text-primary"></i>Receipt Info</h6>
                    <table class="table table-sm table-bordered mb-0">
                        <tbody>
                            <tr><th class="w-50">PO Number</th><td>${esc(d.po_number || '—')}</td></tr>
                            <tr><th>PO Date</th><td>${esc(d.po_date || '—')}</td></tr>
                            <tr><th>Location</th><td>${esc(d.location)}</td></tr>
                            <tr><th>Received by</th><td>${esc(d.received_by)}</td></tr>
                            <tr><th>Received at</th><td>${esc(d.received_at || '—')}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <h6 class="fw-bold mb-3"><i class="bi bi-list-check me-2 text-primary"></i>Received Items</h6>
            <div class="table-responsive mb-4">
                <table class="table table-row-bordered table-sm align-middle">
                    <thead><tr class="fw-bold fs-8 text-gray-800 bg-light">
                        <th>#</th><th>Product</th><th>SKU</th>
                        <th class="text-end">Ordered</th><th class="text-end">Received</th>
                        <th class="text-end">Pending</th><th class="text-end">Unit</th>
                        <th class="text-end">Total</th><th>Batch</th><th>Expiry</th>
                    </tr></thead>
                    <tbody>${itemsHtml || '<tr><td colspan="10" class="text-center text-muted py-4">No items</td></tr>'}</tbody>
                </table>
            </div>

            <h6 class="fw-bold mb-3"><i class="bi bi-calculator me-2 text-primary"></i>Totals</h6>
            <table class="table table-sm table-bordered mb-4">
                <tbody>
                    <tr><th class="w-50">Total items</th><td class="text-end">${fmtNum(d.total_items, 0)}</td></tr>
                    <tr><th>Total quantity</th><td class="text-end">${fmtNum(d.total_quantity, 0)}</td></tr>
                    <tr class="bg-light"><th>Total value</th><td class="text-end fw-bold text-primary">${money(d.total_value)}</td></tr>
                </tbody>
            </table>

            ${d.notes ? `
                <h6 class="fw-bold mb-3"><i class="bi bi-chat-left-text me-2 text-primary"></i>Notes</h6>
                <div class="border rounded p-3 text-gray-800">${esc(d.notes)}</div>
            ` : ''}
        `;
    }

    function exportCsv() {
        const params = new URLSearchParams();
        if (state.startDate)  params.set('start_date',  state.startDate);
        if (state.endDate)    params.set('end_date',    state.endDate);
        if (state.supplierId) params.set('supplier_id', state.supplierId);
        if (state.locationId) params.set('location_id', state.locationId);
        window.location.href = `/reports/purchasing/purchase-receipts?${params.toString()}&export=1`;
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