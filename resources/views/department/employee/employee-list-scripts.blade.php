<script>
window.EmployeeList = (function () {
    'use strict';

    if (!window.EMPLOYEE_ROUTES) {
        console.error('[tenant-employee-list] EMPLOYEE_ROUTES undefined — routes <script> must render first.');
        return { load: () => {}, reload: () => {}, syncUsers: () => {} };
    }

    const URL_DATA = window.EMPLOYEE_ROUTES.data;
    const URL_SYNC = window.EMPLOYEE_ROUTES.sync;
    const CSRF     = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const BOOT     = window.EMPLOYEE_BOOTSTRAP || {};

    const state = {
        page:     1,
        perPage:  Number(BOOT.perPage) || 15,
        search:   BOOT.search || '',
        status:   '',
    };

    let debounce, inFlight = false, activeController = null;

    document.addEventListener('DOMContentLoaded', () => { bind(); load(); });

    function bind() {
        const doSearch = function () {
            clearTimeout(debounce);
            debounce = setTimeout(() => {
                state.search = this.value.trim();
                state.page = 1;
                load();
            }, 400);
        };
        document.getElementById('employeeSearchInput')?.addEventListener('input', doSearch);
        document.getElementById('employeeSearchTop')?.addEventListener('input', function () {
            const top = document.getElementById('employeeSearchInput');
            if (top) top.value = this.value;
            doSearch.call(this);
        });

        document.getElementById('employeeStatusFilter')?.addEventListener('change', function () {
            state.status = this.value; state.page = 1; load();
        });
        document.getElementById('employeePerPage')?.addEventListener('change', function () {
            state.perPage = parseInt(this.value, 10) || 15; state.page = 1; load();
        });

        document.getElementById('empTableBody')?.addEventListener('click', (e) => {
            const row = e.target.closest('tr[data-emp-id]');
            if (!row) return;
            if (e.target.closest('button, a, select, input, .form-check')) return;
            const id = parseInt(row.dataset.empId, 10);
            if (id && window.EmployeeDrawer) window.EmployeeDrawer.open(id);
        });

        document.getElementById('empTableBody')?.addEventListener('keydown', (e) => {
            if (e.key !== 'Enter' && e.key !== ' ') return;
            const row = e.target.closest('tr[data-emp-id]');
            if (!row) return;
            e.preventDefault();
            const id = parseInt(row.dataset.empId, 10);
            if (id && window.EmployeeDrawer) window.EmployeeDrawer.open(id);
        });
    }

    async function load() {
        if (inFlight) return;
        inFlight = true;
        if (activeController) activeController.abort();
        const controller = new AbortController();
        activeController = controller;
        showState('loading');

        const params = new URLSearchParams({ page: state.page, per_page: state.perPage });
        if (state.search)        params.set('search', state.search);
        if (state.status !== '') params.set('status', state.status);

        try {
            const res  = await fetch(`${URL_DATA}?${params}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal,
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Failed');

            renderStats(json.summary);
            renderTable(json.data);
            renderPagination(json.pagination);
            showState(json.data.length ? 'table' : 'empty');
        } catch (err) {
            if (err.name === 'AbortError') return;
            console.error('[tenant-employees] load failed:', err);
            window.toastr?.error?.('Failed to load employees.');
            showState('empty');
        } finally {
            if (activeController === controller) { inFlight = false; activeController = null; }
        }
    }

    function renderStats(s) {
        const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
        set('statTotal',    s.total    ?? 0);
        set('statActive',   s.active   ?? 0);
        set('statInactive', s.inactive ?? 0);
    }

    function renderTable(rows) {
        const tbody = document.getElementById('empTableBody');
        if (!tbody) return;

        tbody.innerHTML = rows.map(e => `
            <tr data-emp-id="${e.id}" style="cursor:pointer;" tabindex="0" role="button" aria-label="${esc(e.name)}">
                <td>
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-circle symbol-45px me-3">
                            <img src="${esc(e.profile_image)}" alt="${esc(e.name)}" class="w-100" />
                        </div>
                        <div class="d-flex flex-column">
                            <span class="text-gray-800 fw-bold">${esc(e.name)}</span>
                            <small class="text-muted">${esc(e.email)}</small>
                        </div>
                    </div>
                </td>
                <td><span class="badge badge-light fw-bold">${esc(e.department_name)}</span></td>
                <td>
                    <div class="d-flex flex-column">
                        <span class="badge badge-light fw-bold">${esc(formatNumber(e.salary))} — ${esc(e.currency_symbol)}</span>
                        ${e.salary_type ? `<small class="text-muted mt-1">(${esc(e.salary_type)})</small>` : ''}
                    </div>
                </td>
                <td>${esc(e.hire_date || 'N/A')}</td>
                <td>${e.termination_date ? esc(e.termination_date) : '<span class="text-muted">— — —</span>'}</td>
                <td>
                    <span class="badge badge-light-${e.is_active ? 'success' : 'warning'}">
                        ${e.is_active ? 'Active' : 'Inactive'}
                    </span>
                </td>
                <td class="text-end"><i class="bi bi-chevron-right text-muted"></i></td>
            </tr>
        `).join('');
    }

    function renderPagination(p) {
        const info = document.getElementById('empPageInfo');
        const ul   = document.getElementById('empPagination');
        if (!info || !ul) return;
        info.textContent = `Showing ${p.from || 0}–${p.to || 0} of ${p.total || 0}`;
        ul.innerHTML = '';

        const addPage = (page, label, active = false, disabled = false) => {
            const li = document.createElement('li');
            li.className = `page-item ${active ? 'active' : ''} ${disabled ? 'disabled' : ''}`;
            const a = document.createElement('a');
            a.className = 'page-link'; a.href = '#'; a.textContent = label;
            if (!disabled) a.addEventListener('click', (ev) => {
                ev.preventDefault(); state.page = page; load();
            });
            li.appendChild(a); ul.appendChild(li);
        };
        addPage(p.current_page - 1, 'Prev', false, p.current_page <= 1);
        for (let i = Math.max(1, p.current_page - 2); i <= Math.min(p.last_page, p.current_page + 2); i++) {
            addPage(i, String(i), i === p.current_page);
        }
        addPage(p.current_page + 1, 'Next', false, !p.has_more);
    }

    function showState(which) {
        document.getElementById('empLoading')?.classList.toggle('d-none', which !== 'loading');
        document.getElementById('empWrapper')?.classList.toggle('d-none', which !== 'table');
        document.getElementById('empEmpty')?.classList.toggle('d-none',   which !== 'empty');
    }

    async function syncUsers() {
        if (!confirm('Sync all users to employees? This may create/update records.')) return;
        try {
            const res = await fetch(URL_SYNC, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const json = await res.json().catch(() => ({}));
            if (!res.ok || json.success === false) throw new Error(json.message || 'Sync failed');
            window.toastr?.success?.(
                `Sync completed: ${json.stats.created} created, ${json.stats.updated} updated.`
            );
            load();
        } catch (err) {
            window.toastr?.error?.(err.message || 'Sync failed');
        }
    }

    function formatNumber(n) {
        const num = Number(n) || 0;
        return num.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 });
    }

    function esc(t) { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }

    window.EmployeeList = { load, reload: load, syncUsers };
    return window.EmployeeList;
})();
</script>