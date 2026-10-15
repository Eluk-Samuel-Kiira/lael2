
<script>
(function () {
    'use strict';

    // ── Config ─────────────────────────────────────────────────────
    const URLS = {
        data:       '{{ route("production-orders.data") }}',
        store:      '{{ route("production-orders.store") }}',
        batches:    '{{ route("production-orders.available-batches") }}', // adjust name if different
    };
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // ── Initial state from the Blade-rendered controls ─────────────
    const state = {
        page:       1,
        perPage:    {{ (int) $initialFilters['per_page'] }},
        search:     @json($initialFilters['search']),
        locationId: @json($initialFilters['location_id']),
        status:     @json($initialFilters['status']),
    };

    let searchDebounce;
    const selectedIds = new Set();

    // ─────────────────────────────────────────────────────────────
    // Bootstrap
    // ─────────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', () => {
        bindListEvents();
        loadOrders();
    });

    function bindListEvents() {
        document.getElementById('productionSearchInput')?.addEventListener('input', function () {
            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(() => {
                state.search = this.value.trim();
                state.page = 1;
                loadOrders();
            }, 400);
        });

        document.getElementById('productionLocationFilter')?.addEventListener('change', function () {
            state.locationId = this.value;
            state.page = 1;
            loadOrders();
        });

        document.getElementById('productionStatusFilter')?.addEventListener('change', function () {
            state.status = this.value;
            state.page = 1;
            loadOrders();
        });

        document.getElementById('productionPerPage')?.addEventListener('change', function () {
            state.perPage = parseInt(this.value, 10) || 15;
            state.page = 1;
            loadOrders();
        });

        document.getElementById('productionSelectAll')?.addEventListener('change', function () {
            const checked = this.checked;
            document.querySelectorAll('.production-row-checkbox').forEach(cb => {
                cb.checked = checked;
                toggleRowSelection(cb.value, checked);
            });
            syncSelectionUI();
        });
    }

    // ─────────────────────────────────────────────────────────────
    // List loading
    // ─────────────────────────────────────────────────────────────
    async function loadOrders() {
        showState('loading');

        const params = new URLSearchParams({
            page:     state.page,
            per_page: state.perPage,
        });
        if (state.search)     params.set('search', state.search);
        if (state.locationId) params.set('location_id', state.locationId);
        if (state.status && state.status !== 'all') params.set('status', state.status);

        try {
            const res = await fetch(`${URLS.data}?${params.toString()}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);

            const json = await res.json();
            if (!json.success) {
                showState('empty');
                return;
            }

            // Summary cards
            document.getElementById('summaryTotal').textContent  = json.summary.total ?? 0;
            document.getElementById('summaryCost').textContent   = formatMoney(json.summary.total_cost ?? 0);
            document.getElementById('summaryInput').textContent  = formatNumber(json.summary.total_input ?? 0, 2);
            document.getElementById('summaryOutput').textContent = formatNumber(json.summary.total_output ?? 0, 2);
            document.getElementById('summaryBreakdown').textContent =
                `${json.summary.draft ?? 0} draft · ${json.summary.in_progress ?? 0} active · ${json.summary.completed ?? 0} done · ${json.summary.cancelled ?? 0} cancelled`;

            if (!json.data || json.data.length === 0) {
                showState('empty');
                return;
            }

            renderTable(json.data);
            renderPagination(json.pagination);
            showState('table');
        } catch (err) {
            console.error('[production] load failed:', err);
            showState('empty');
            window.toastr?.error?.('{{ __("passwords.failed_to_load_records") }}');
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Table
    // ─────────────────────────────────────────────────────────────
    function renderTable(rows) {
        const tbody = document.getElementById('productionTableBody');
        tbody.innerHTML = rows.map(row => {
            const isChecked = selectedIds.has(String(row.id));

            // Action buttons driven by status — same rule set as the
            // old Blade loop, but rendered client-side.
            const actions = buildActions(row);

            return `
                <tr data-order-id="${row.id}">
                    <td>
                        <div class="form-check form-check-sm form-check-custom form-check-solid">
                            <input class="form-check-input production-row-checkbox"
                                   type="checkbox"
                                   value="${row.id}"
                                   ${isChecked ? 'checked' : ''} />
                        </div>
                    </td>
                    <td>
                        <span class="fw-bold text-primary">${escapeHtml(row.production_number)}</span>
                    </td>
                    <td>
                        <span class="badge badge-light fw-bold">${escapeHtml(row.location_name)}</span>
                    </td>
                    <td>
                        <span class="badge badge-${row.status_badge} fw-bold">${escapeHtml(row.status_label)}</span>
                    </td>
                    <td>
                        <span class="badge badge-light fw-bold">${row.inputs_count} ${__('items')}</span>
                        <small class="d-block text-muted">${formatNumber(row.total_input_quantity, 2)} ${__('units')}</small>
                    </td>
                    <td>
                        <span class="badge badge-light fw-bold">${row.outputs_count} ${__('items')}</span>
                        <small class="d-block text-muted">${formatNumber(row.total_output_quantity, 2)} ${__('units')}</small>
                    </td>
                    <td>
                        <span class="badge badge-light fw-bold">${escapeHtml(row.total_cost_formatted)}</span>
                    </td>
                    <td>
                        <span class="badge badge-light fw-bold">${escapeHtml(row.scheduled_date_human)}</span>
                    </td>
                    <td>
                        <span class="badge badge-light fw-bold">${escapeHtml(row.created_by_name)}</span>
                    </td>
                    <td>
                        <div class="d-flex gap-2 justify-content-end">${actions}</div>
                    </td>
                </tr>
            `;
        }).join('');

        tbody.querySelectorAll('.production-row-checkbox').forEach(cb => {
            cb.addEventListener('change', function () {
                toggleRowSelection(this.value, this.checked);
                syncSelectionUI();
            });
        });

        syncSelectionUI();
    }

    function buildActions(row) {
        const status = row.status;
        let html = '';

        // Draft → Start
        if (status === 'draft') {
            html += `
                <button class="btn btn-sm btn-warning"
                        type="button"
                        data-action="start"
                        data-order-id="${row.id}"
                        title="{{ __('passwords.start_production') }}">
                    <i class="bi bi-play-fill"></i>
                </button>`;
        }

        // In-progress → Complete
        if (status === 'in_progress') {
            html += `
                <button class="btn btn-sm btn-success"
                        type="button"
                        data-action="complete"
                        data-order-id="${row.id}"
                        title="{{ __('passwords.complete_production') }}">
                    <i class="bi bi-check-circle"></i>
                </button>`;
        }

        // Draft or in-progress → Cancel + Edit
        if (status === 'draft' || status === 'in_progress') {
            html += `
                <button class="btn btn-sm btn-danger"
                        type="button"
                        data-action="cancel"
                        data-order-id="${row.id}"
                        title="{{ __('passwords.cancel') }}">
                    <i class="bi bi-x-circle"></i>
                </button>
                <button class="btn btn-sm btn-light btn-active-color-primary"
                        type="button"
                        data-action="edit"
                        data-order-id="${row.id}"
                        title="{{ __('auth._edit') }}">
                    <i class="bi bi-pencil fs-5"></i>
                </button>`;
        }

        // Always → View
        html += `
            <button class="btn btn-sm btn-light btn-active-color-success"
                    type="button"
                    data-action="view"
                    data-order-id="${row.id}"
                    title="{{ __('auth._view') }}">
                <i class="bi bi-eye fs-5"></i>
            </button>`;

        return html;
    }

    // Action dispatcher — attach ONE listener on the tbody
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-action][data-order-id]');
        if (!btn) return;
        // Only handle our own buttons in the production table
        if (!btn.closest('#productionTableBody')) return;

        e.preventDefault();

        const action = btn.dataset.action;
        const id     = parseInt(btn.dataset.orderId, 10);

        // Placeholder handlers — will be wired in later phases.
        // For now, they delegate to whatever global hook exists,
        // or open a toast so nothing is silently ignored.
        switch (action) {
            case 'view':
                window.ProductionPage?.dispatch?.('view', id);
                break;
            case 'edit':
                window.ProductionPage?.dispatch?.('edit', id);
                break;
            case 'start':
                window.ProductionPage?.dispatch?.('start', id);
                break;
            case 'complete':
                window.ProductionPage?.dispatch?.('complete', id);
                break;
            case 'cancel':
                window.ProductionPage?.dispatch?.('cancel', id);
                break;
            default:
                console.warn('[production] unknown action:', action);
        }
    });

    // ─────────────────────────────────────────────────────────────
    // Selection
    // ─────────────────────────────────────────────────────────────
    function toggleRowSelection(id, selected) {
        if (selected) selectedIds.add(String(id));
        else          selectedIds.delete(String(id));
    }

    function syncSelectionUI() {
        const all = document.querySelectorAll('.production-row-checkbox');
        const checked = document.querySelectorAll('.production-row-checkbox:checked');
        const selectAll = document.getElementById('productionSelectAll');

        if (selectAll) {
            selectAll.checked = all.length > 0 && checked.length === all.length;
            selectAll.indeterminate = checked.length > 0 && checked.length < all.length;
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Pagination
    // ─────────────────────────────────────────────────────────────
    function renderPagination(p) {
        const ul = document.getElementById('productionPagination');
        const info = document.getElementById('productionPaginationInfo');
        const wrap = document.getElementById('productionPaginationContainer');
        if (!ul || !info || !wrap) return;

        ul.innerHTML = '';
        info.textContent = `Showing ${p.from || 0} to ${p.to || 0} of ${p.total} entries`;
        wrap.classList.remove('d-none');

        const addItem = (page, text, active = false, disabled = false) => {
            const li = document.createElement('li');
            li.className = `page-item ${active ? 'active' : ''} ${disabled ? 'disabled' : ''}`;
            const a = document.createElement('a');
            a.className = 'page-link';
            a.href = '#';
            a.textContent = text;
            if (!disabled && !active) {
                a.addEventListener('click', e => { e.preventDefault(); gotoPage(page); });
            } else {
                a.addEventListener('click', e => e.preventDefault());
            }
            li.appendChild(a);
            ul.appendChild(li);
        };

        addItem(p.current_page - 1, 'Prev', false, p.current_page <= 1);

        const start = Math.max(1, p.current_page - 2);
        const end   = Math.min(p.last_page, p.current_page + 2);

        if (start > 1) addItem(1, '1');
        if (start > 2) {
            const li = document.createElement('li');
            li.className = 'page-item disabled';
            li.innerHTML = '<span class="page-link">…</span>';
            ul.appendChild(li);
        }

        for (let i = start; i <= end; i++) addItem(i, i, i === p.current_page);

        if (end < p.last_page - 1) {
            const li = document.createElement('li');
            li.className = 'page-item disabled';
            li.innerHTML = '<span class="page-link">…</span>';
            ul.appendChild(li);
        }
        if (end < p.last_page) addItem(p.last_page, p.last_page);

        addItem(p.current_page + 1, 'Next', false, p.current_page >= p.last_page);
    }

    function gotoPage(page) {
        if (page === state.page || page < 1) return;
        state.page = page;
        loadOrders();
        document.getElementById('productionTableContainer')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // ─────────────────────────────────────────────────────────────
    // State toggles
    // ─────────────────────────────────────────────────────────────
    function showState(which) {
        const loading = document.getElementById('productionLoadingSpinner');
        const table   = document.getElementById('productionTableContainer');
        const empty   = document.getElementById('productionNoDataMessage');
        const pager   = document.getElementById('productionPaginationContainer');

        loading?.classList.toggle('d-none', which !== 'loading');
        table?.classList.toggle('d-none',   which !== 'table');
        empty?.classList.toggle('d-none',   which !== 'empty');
        pager?.classList.toggle('d-none',   which !== 'table');
    }

    function resetFilters() {
        state.search = state.locationId = '';
        state.status = 'all';
        state.page = 1;

        const s = document.getElementById('productionSearchInput');
        if (s) s.value = '';

        const loc = document.getElementById('productionLocationFilter');
        if (loc) loc.value = '';

        const st = document.getElementById('productionStatusFilter');
        if (st) st.value = 'all';

        loadOrders();
    }

    // ─────────────────────────────────────────────────────────────
    // Utilities
    // ─────────────────────────────────────────────────────────────
    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        const div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
    }

    function formatNumber(n, decimals = 0) {
        return Number(n ?? 0).toLocaleString('en-US', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
        });
    }

    function formatMoney(n) {
        return '{{ currency_symbol() }}' + formatNumber(n, 2);
    }

    function __(s) { return s; }

    // ─────────────────────────────────────────────────────────────
    // Public API
    // ─────────────────────────────────────────────────────────────
    window.ProductionPage = {
        getSelectedIds: () => Array.from(selectedIds),
        clearSelection: () => {
            selectedIds.clear();
            document.querySelectorAll('.production-row-checkbox').forEach(cb => cb.checked = false);
            syncSelectionUI();
        },
        resetFilters,
        reload: loadOrders,
        // Placeholder dispatcher — replaced in later phases
        dispatch: function (action, id) {
            const handler = window.ProductionActions?.[action];
            if (typeof handler === 'function') {
                handler(id);
            } else {
                console.info(`[production] action "${action}" for #${id} — handler not yet wired`);
            }
        },
    };
})();
</script>