<script>
window.StockList = (function () {
    'use strict';

    const URL_DATA = '{{ route("stocks.data") }}';

    const state = {
        page:         {{ (int) request('page', 1) }},
        perPage:      {{ (int) $initialFilters['per_page'] }},
        search:       @json($initialFilters['search']),
        locationId:   @json($initialFilters['location_id']),
        departmentId: @json($initialFilters['department_id']),
    };

    let searchDebounce;
    let inFlight = false;

    document.addEventListener('DOMContentLoaded', () => {
        bindFilters();
        bindRowClick(); 
        bindDepartmentDependency();
        load();
    });

    function bindFilters() {
        document.getElementById('stockSearchInput')?.addEventListener('input', function () {
            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(() => {
                state.search = this.value.trim();
                state.page   = 1;
                load();
            }, 400);
        });

        document.getElementById('stockLocationFilter')?.addEventListener('change', function () {
            state.locationId   = this.value;
            state.departmentId = '';
            const deptSel = document.getElementById('stockDepartmentFilter');
            if (deptSel) deptSel.value = '';
            state.page = 1;
            load();
        });

        document.getElementById('stockDepartmentFilter')?.addEventListener('change', function () {
            state.departmentId = this.value;
            state.page = 1;
            load();
        });

        document.getElementById('stockPerPage')?.addEventListener('change', function () {
            state.perPage = parseInt(this.value, 10) || 15;
            state.page = 1;
            load();
        });
    }

    function bindDepartmentDependency() {
        const locSel  = document.getElementById('stockLocationFilter');
        const deptSel = document.getElementById('stockDepartmentFilter');
        if (!locSel || !deptSel) return;

        const applyFilter = () => {
            const loc = String(locSel.value || '');
            Array.from(deptSel.options).forEach(opt => {
                if (!opt.value) return;
                const optLoc = String(opt.getAttribute('data-location') || '');
                const show = !loc || optLoc === loc;
                opt.hidden   = !show;
                opt.disabled = !show;
            });
        };

        locSel.addEventListener('change', applyFilter);
        applyFilter();
    }

    async function load() {
        if (inFlight) return;
        inFlight = true;
        showState('loading');

        const params = new URLSearchParams({ page: state.page, per_page: state.perPage });
        if (state.search)       params.set('search', state.search);
        if (state.locationId)   params.set('location_id', state.locationId);
        if (state.departmentId) params.set('department_id', state.departmentId);

        try {
            const res = await fetch(`${URL_DATA}?${params.toString()}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const json = await res.json();
            if (!json.success) { showState('empty'); return; }

            renderSummary(json.summary);
            if (!json.data || json.data.length === 0) { showState('empty'); return; }

            renderTable(json.data);
            renderPagination(json.pagination);
            showState('table');
        } catch (err) {
            console.error('[stock-list] load failed:', err);
            showState('empty');
            window.toastr?.error?.('{{ __("passwords.failed_to_load_records") ?? "Failed to load records" }}');
        } finally {
            inFlight = false;
        }
    }

    function bindRowClick() {
        document.getElementById('stockTableBody')?.addEventListener('click', function (e) {
            const row = e.target.closest('.stock-row[data-item-id]');
            if (!row) return;
            const id = parseInt(row.getAttribute('data-item-id'), 10);
            if (!id) return;

            if (window.StockDrawer?.open) {
                window.StockDrawer.open(id);
            } else {
                console.log('[stock-list] row clicked:', id, '— drawer not wired');
            }
        });
    }

    function renderSummary(s) {
        const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
        set('summaryTotal',     s.total ?? 0);
        set('summaryAllocated', fmt(s.total_allocated ?? 0, 0));
    }

    function renderTable(rows) {
        const tbody = document.getElementById('stockTableBody');
        tbody.innerHTML = rows.map(row => {
            const imageUrl = productImageUrl(row.variant_image);
            return `
                <tr class="stock-row" data-item-id="${row.id}">
                    <td><span class="badge badge-light fw-bold">${esc(row.variant_sku)}</span></td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-40px me-3">
                                <img src="${imageUrl}" alt="" class="symbol-label"
                                    draggable="false"
                                    onerror="this.src='{{ asset('assets/media/stock/ecommerce/2.png') }}'">
                            </div>
                            <div><span class="text-gray-800 fw-bold">${esc(row.variant_name)}</span></div>
                        </div>
                    </td>
                    <td><span class="badge badge-light fw-bold">${esc(row.department_name)}</span></td>
                    <td><span class="badge badge-light fw-bold">${esc(row.location_name)}</span></td>
                    <td><span class="badge badge-light-success">${fmt(row.overall_quantity, 0)}</span></td>
                    <td><span class="badge badge-light-warning">${fmt(row.quantity_allocated, 0)}</span></td>
                    <td class="text-end">
                        <button type="button"
                                class="btn btn-sm btn-icon btn-light btn-active-light-info"
                                onclick="event.stopPropagation(); window.StockDrawer?.open(${row.id})"
                                title="{{ __('auth._view') }}">
                            <i class="ki-duotone ki-information fs-4">
                                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                            </i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }
    
    function renderPagination(p) {
        const ul    = document.getElementById('stockPagination');
        const info  = document.getElementById('stockPaginationInfo');
        const wrap  = document.getElementById('stockPaginationContainer');
        if (!ul || !info || !wrap) return;

        ul.innerHTML = '';
        info.textContent = `Showing ${p.from || 0} to ${p.to || 0} of ${p.total} entries`;
        wrap.classList.remove('d-none');

        const add = (page, text, active = false, disabled = false) => {
            const li = document.createElement('li');
            li.className = `page-item ${active ? 'active' : ''} ${disabled ? 'disabled' : ''}`;
            const a = document.createElement('a');
            a.className = 'page-link';
            a.href = '#';
            a.textContent = text;
            a.addEventListener('click', e => {
                e.preventDefault();
                if (!disabled && !active) gotoPage(page);
            });
            li.appendChild(a);
            ul.appendChild(li);
        };

        add(p.current_page - 1, 'Prev', false, p.current_page <= 1);

        const start = Math.max(1, p.current_page - 2);
        const end   = Math.min(p.last_page, p.current_page + 2);

        if (start > 1) add(1, '1');
        if (start > 2) {
            const li = document.createElement('li');
            li.className = 'page-item disabled';
            li.innerHTML = '<span class="page-link">…</span>';
            ul.appendChild(li);
        }
        for (let i = start; i <= end; i++) add(i, i, i === p.current_page);
        if (end < p.last_page - 1) {
            const li = document.createElement('li');
            li.className = 'page-item disabled';
            li.innerHTML = '<span class="page-link">…</span>';
            ul.appendChild(li);
        }
        if (end < p.last_page) add(p.last_page, p.last_page);

        add(p.current_page + 1, 'Next', false, p.current_page >= p.last_page);
    }

    function gotoPage(page) {
        if (page === state.page || page < 1) return;
        state.page = page;
        load();
        document.getElementById('stockTableContainer')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function showState(which) {
        document.getElementById('stockLoadingSpinner')?.classList.toggle('d-none', which !== 'loading');
        document.getElementById('stockTableContainer')?.classList.toggle('d-none', which !== 'table');
        document.getElementById('stockNoDataMessage')?.classList.toggle('d-none', which !== 'empty');
        document.getElementById('stockPaginationContainer')?.classList.toggle('d-none', which !== 'table');
    }

    function resetFilters() {
        state.search = state.locationId = state.departmentId = '';
        state.page = 1;
        const s   = document.getElementById('stockSearchInput');       if (s)   s.value = '';
        const loc = document.getElementById('stockLocationFilter');    if (loc) loc.value = '';
        const dep = document.getElementById('stockDepartmentFilter');  if (dep) dep.value = '';
        loc?.dispatchEvent(new Event('change'));
        load();
    }

    function productImageUrl(path) {
        if (!path) return '{{ asset("assets/media/stock/ecommerce/2.png") }}';
        const clean = String(path).replace(/^\/+/, '');
        return '{{ asset("storage") }}/' + clean;
    }

    function esc(t) {
        if (t === null || t === undefined) return '';
        const d = document.createElement('div');
        d.textContent = String(t);
        return d.innerHTML;
    }

    function fmt(n, d = 0) {
        return Number(n ?? 0).toLocaleString('en-US', {
            minimumFractionDigits: d,
            maximumFractionDigits: d,
        });
    }

    window.StockList = { load, reload: load, resetFilters };
    return window.StockList;
})();
</script>