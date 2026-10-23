<script>
window.ExcessStock = (function () {
    'use strict';

    const ROUTE    = window.ES_ROUTES.data;
    const CURRENCY = window.REPORT_CURRENCY || '';
    const BOOT     = window.ES_BOOTSTRAP || {};

    const state = {
        departmentId:    BOOT.departmentId    || '',
        locationId:      BOOT.locationId      || '',
        variantId:       BOOT.variantId       || '',
        excessThreshold: Number(BOOT.excessThreshold || 1.5),
        perPage:         Number(BOOT.perPage || 15),
        page:            1,
    };

    let inFlight = false;
    const $ = (id) => document.getElementById(id);

    document.addEventListener('DOMContentLoaded', () => {
        $('esApplyBtn')?.addEventListener('click', () => { readFilters(); state.page = 1; load(); });
        $('esClearBtn')?.addEventListener('click', resetFilters);
        $('esExportBtn')?.addEventListener('click', exportCsv);
        load();
    });

    function readFilters() {
        state.excessThreshold = Number($('esThreshold')?.value || 1.5);
        state.variantId       = $('esVariant')?.value    || '';
        state.locationId      = $('esLocation')?.value   || '';
        state.departmentId    = $('esDepartment')?.value || '';
    }

    function resetFilters() {
        state.variantId = state.locationId = state.departmentId = '';
        state.excessThreshold = 1.5;
        state.page = 1;
        if ($('esThreshold')) $('esThreshold').value = '1.5';
        if (window.jQuery) jQuery('#esVariant, #esLocation, #esDepartment').val(null).trigger('change.select2');
        load();
    }

    async function load() {
        if (inFlight) return;
        inFlight = true;
        showSkeleton(true);

        const params = new URLSearchParams({
            page: state.page,
            per_page: state.perPage,
            excess_threshold: state.excessThreshold,
        });
        if (state.variantId)    params.set('variant_id',    state.variantId);
        if (state.locationId)   params.set('location_id',   state.locationId);
        if (state.departmentId) params.set('department_id', state.departmentId);

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
            console.error('[excess-stock] load failed:', err);
            window.toastr?.error('Failed to load excess stock.');
            showSkeleton(false);
        } finally {
            inFlight = false;
        }
    }

    function showSkeleton(v) {
        $('esSkeleton')?.classList.toggle('d-none', !v);
        $('esContent')?.classList.toggle('d-none', v);
    }

    function render(p) {
        renderSummary(p.summary);
        renderCategories(p.categories);
        renderTable(p.items, p.pagination);
    }

    function renderSummary(s) {
        $('sumItems').textContent = fmtNum(s.total_items, 0);
        $('sumQty').textContent   = fmtNum(s.total_excess_quantity, 0);
        $('sumValue').textContent = money(s.total_excess_value);
        $('sumAvg').textContent   = fmtNum(s.avg_excess_percentage, 1) + '%';
    }

    function renderCategories(c) {
        $('catModerate').textContent = fmtNum(c['50_100']   || 0, 0);
        $('catHigh').textContent     = fmtNum(c['100_200']  || 0, 0);
        $('catCritical').textContent = fmtNum(c['over_200'] || 0, 0);
    }

    function renderTable(rows, pagination) {
        const body = $('esTableBody');
        const empty = $('esEmpty');
        const wrap = $('esPaginationWrap');
        const badge = $('esBadge');
        const footer = $('esTableFooter');
        if (!body) return;

        if (!rows || rows.length === 0) {
            body.innerHTML = ''; empty?.classList.remove('d-none'); wrap?.classList.add('d-none');
            footer?.classList.add('d-none');
            if (badge) badge.textContent = '';
            return;
        }
        empty?.classList.add('d-none');
        wrap?.classList.remove('d-none');
        footer?.classList.remove('d-none');
        if (badge) badge.textContent = `Showing ${pagination.from} of ${pagination.total}`;

        body.innerHTML = rows.map(r => {
            const pct = Number(r.excess_percentage);
            const color = r.severity_color || 'info';
            const progress = Math.min(100, Math.max(0, pct));
            const imgHtml = r.image_url
                ? `<div class="symbol symbol-40px me-2"><img src="${esc(r.image_url)}" class="rounded" alt="${esc(r.variant_name)}"></div>`
                : '';

            return `
                <tr>
                    <td class="ps-4">
                        <div class="fw-semibold">${esc(r.sku)}</div>
                        ${r.barcode ? `<small class="text-muted">${esc(r.barcode)}</small>` : ''}
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            ${imgHtml}
                            <div>
                                <div class="fw-bold">${esc(r.variant_name)}</div>
                                ${r.product_name ? `<div class="text-muted fs-7">${esc(r.product_name)}</div>` : ''}
                            </div>
                        </div>
                    </td>
                    <td><span class="badge badge-light-primary">${esc(r.department_name)}</span></td>
                    <td><span class="badge badge-light-info">${esc(r.location_name)}</span></td>
                    <td class="text-center"><span class="fw-bold">${fmtNum(r.quantity_on_hand, 0)}</span></td>
                    <td class="text-center"><span class="fw-bold text-success">${fmtNum(r.preferred_stock, 0)}</span></td>
                    <td class="text-center"><span class="fw-bold text-${color}">+${fmtNum(r.excess_quantity, 0)}</span></td>
                    <td class="text-center">
                        <span class="fw-bold text-${color}">${fmtNum(pct, 1)}%</span>
                        <div class="progress mt-1 mx-auto" style="height:4px; width:60px;">
                            <div class="progress-bar bg-${color}" style="width:${progress}%"></div>
                        </div>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-light-${color} py-2 px-3">${esc(r.severity_text)}</span>
                    </td>
                    <td class="text-end"><span class="fw-bold text-${color}">${money(r.excess_value)}</span></td>
                </tr>
            `;
        }).join('');

        const pageValue = rows.reduce((s, r) => s + Number(r.excess_value || 0), 0);
        $('ftPageValue').textContent = money(pageValue);

        renderPagination(pagination);
    }

    function renderPagination(p) {
        const info = $('esPaginationInfo');
        const ul   = $('esPagination');
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
        params.set('excess_threshold', state.excessThreshold);
        if (state.variantId)    params.set('variant_id',    state.variantId);
        if (state.locationId)   params.set('location_id',   state.locationId);
        if (state.departmentId) params.set('department_id', state.departmentId);
        window.location.href = `/reports/inventory/excess-stock?${params.toString()}&export=1`;
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