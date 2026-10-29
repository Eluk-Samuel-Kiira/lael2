<script>
window.LeaveList = (function () {
    'use strict';

    if (!window.LEAVE_ROUTES) {
        console.error('[leave-list] LEAVE_ROUTES undefined.');
        return { load: () => {}, reload: () => {} };
    }

    const URL_DATA = window.LEAVE_ROUTES.data;
    const BOOT     = window.LEAVE_BOOTSTRAP || {};

    const state = {
        page: 1, perPage: Number(BOOT.perPage) || 15,
        search: BOOT.search || '', status: '', type: '', dateFrom: '', dateTo: '',
    };

    let debounce, inFlight = false, activeController = null;

    document.addEventListener('DOMContentLoaded', () => { bind(); load(); });

    function bind() {
        document.getElementById('leaveStatusFilter')?.addEventListener('change', function () { state.status = this.value; state.page = 1; load(); });
        document.getElementById('leaveTypeFilter')?.addEventListener('change', function () { state.type = this.value; state.page = 1; load(); });
        document.getElementById('leaveDateFrom')?.addEventListener('change', function () { state.dateFrom = this.value; state.page = 1; load(); });
        document.getElementById('leaveDateTo')?.addEventListener('change', function () { state.dateTo = this.value; state.page = 1; load(); });
        document.getElementById('leavePerPage')?.addEventListener('change', function () { state.perPage = parseInt(this.value, 10) || 15; state.page = 1; load(); });
        document.getElementById('btnResetLeaveFilters')?.addEventListener('click', () => {
            Object.assign(state, { page: 1, status: '', type: '', dateFrom: '', dateTo: '' });
            ['leaveStatusFilter','leaveTypeFilter','leaveDateFrom','leaveDateTo'].forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
            load();
        });

        document.getElementById('leaveTableBody')?.addEventListener('click', (e) => {
            const row = e.target.closest('tr[data-leave-id]');
            if (!row) return;
            if (e.target.closest('button, a, select, input')) return;
            const id = parseInt(row.dataset.leaveId, 10);
            if (id && window.LeaveDrawer) window.LeaveDrawer.open(id);
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
        if (state.search)   params.set('search', state.search);
        if (state.status)   params.set('status', state.status);
        if (state.type)     params.set('leave_type', state.type);
        if (state.dateFrom) params.set('date_from', state.dateFrom);
        if (state.dateTo)   params.set('date_to', state.dateTo);

        try {
            const res = await fetch(`${URL_DATA}?${params}`, {
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
            console.error('[leaves] load failed:', err);
            showState('empty');
        } finally {
            if (activeController === controller) { inFlight = false; activeController = null; }
        }
    }

    function renderStats(s) {
        const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
        set('statPending',   s.pending_count ?? 0);
        set('statOngoing',   s.ongoing_count ?? 0);
        set('statUpcoming',  s.approved_upcoming ?? 0);
        set('statDays',      fmtNum(s.total_days_this_month));
    }

    function renderTable(rows) {
        const tbody = document.getElementById('leaveTableBody');
        if (!tbody) return;
        const statusColors = {
            pending: 'warning', approved: 'info', ongoing: 'primary',
            completed: 'success', rejected: 'danger', cancelled: 'secondary',
        };

        tbody.innerHTML = rows.map(l => `
            <tr data-leave-id="${l.id}" style="cursor:pointer;">
                <td>
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-circle symbol-40px me-3">
                            <div class="symbol-label bg-light-primary text-primary fw-bold">${esc(l.employee_initials)}</div>
                        </div>
                        <div>
                            <span class="fw-bold d-block">${esc(l.employee_name)}</span>
                            <small class="text-muted">${esc(l.department_name)}</small>
                        </div>
                    </div>
                </td>
                <td><span class="badge badge-light-primary">${esc(l.leave_type_label)}</span></td>
                <td>${esc(l.start_date)} — ${esc(l.end_date)}</td>
                <td><span class="badge badge-light-info">${esc(l.total_days)}</span></td>
                <td class="text-center">
                    <span class="badge badge-light-${statusColors[l.status] || 'secondary'}">${esc(l.status)}</span>
                </td>
                <td class="text-muted fs-7">${esc(l.applied_at)}</td>
                <td class="text-end"><i class="bi bi-chevron-right text-muted"></i></td>
            </tr>
        `).join('');
    }

    function renderPagination(p) {
        const info = document.getElementById('leavePageInfo');
        const ul   = document.getElementById('leavePagination');
        if (!info || !ul) return;
        info.textContent = `Showing ${p.from || 0}–${p.to || 0} of ${p.total || 0}`;
        ul.innerHTML = '';
        const addPage = (page, label, active = false, disabled = false) => {
            const li = document.createElement('li');
            li.className = `page-item ${active ? 'active' : ''} ${disabled ? 'disabled' : ''}`;
            const a = document.createElement('a');
            a.className = 'page-link'; a.href = '#'; a.textContent = label;
            if (!disabled) a.addEventListener('click', (ev) => { ev.preventDefault(); state.page = page; load(); });
            li.appendChild(a); ul.appendChild(li);
        };
        addPage(p.current_page - 1, 'Prev', false, p.current_page <= 1);
        for (let i = Math.max(1, p.current_page - 2); i <= Math.min(p.last_page, p.current_page + 2); i++) addPage(i, String(i), i === p.current_page);
        addPage(p.current_page + 1, 'Next', false, !p.has_more);
    }

    function showState(which) {
        document.getElementById('leaveLoading')?.classList.toggle('d-none', which !== 'loading');
        document.getElementById('leaveWrapper')?.classList.toggle('d-none', which !== 'table');
        document.getElementById('leaveEmpty')?.classList.toggle('d-none',   which !== 'empty');
    }

    function fmtNum(n) { const v = Number(n) || 0; return v.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 3 }); }
    function esc(t) { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }

    window.LeaveList = { load, reload: load };
    return window.LeaveList;
})();
</script>