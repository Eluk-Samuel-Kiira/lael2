<x-app-layout>
    @section('title', __('passwords.expense_index'))
    @section('content')

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- TOOLBAR                                                        --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div class="app-container container-fluid d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-4">
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx my-0">
                    {{ __('passwords.expense_table') }}
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">
                            {{ __('accounting.dashboard') }}
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">{{ __('passwords.expense_table') }}</li>
                </ul>
            </div>

            @can('create expense')
            <div class="d-flex gap-2">
                <a href="{{ route('expense-templates.index') }}" class="btn btn-light-primary">
                    <i class="ki-duotone ki-flash fs-2 me-2"><span class="path1"></span><span class="path2"></span></i>
                    {{ __('auth.quick_log') }}
                </a>
                <button type="button" class="btn btn-primary"
                        data-bs-toggle="modal" data-bs-target="#kt_modal_add_expense">
                    <i class="ki-duotone ki-plus fs-2 me-2"><span class="path1"></span><span class="path2"></span></i>
                    {{ __('passwords.expense_new') }}
                </button>
            </div>
            @endcan
        </div>
    </div>

    @if (tenant_can('expenses'))
    <div class="app-content flex-column-fluid">
        <div class="app-container container-xxl">

            {{-- ═══════════════════════════════════════════════════════ --}}
            {{-- SUMMARY CARDS (JS-populated)                              --}}
            {{-- ═══════════════════════════════════════════════════════ --}}
            <div class="row g-4 g-lg-6 mb-6">
                <div class="col-6 col-md-3">
                    <div class="card bg-light-primary border border-primary border-dashed h-100">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center">
                                <div class="symbol symbol-40px me-3">
                                    <span class="symbol-label bg-primary">
                                        <i class="bi bi-cash-stack fs-2 text-white"></i>
                                    </span>
                                </div>
                                <div>
                                    <div class="text-muted fs-8">{{ __('auth.total_spent') }}</div>
                                    <div class="fw-bold fs-4 text-primary" id="summaryTotalNet">—</div>
                                    <small class="text-muted" id="summaryTotalCount">—</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-3">
                    <div class="card bg-light-warning border border-warning border-dashed h-100">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center">
                                <div class="symbol symbol-40px me-3">
                                    <span class="symbol-label bg-warning">
                                        <i class="bi bi-hourglass-split fs-2 text-white"></i>
                                    </span>
                                </div>
                                <div>
                                    <div class="text-muted fs-8">{{ __('pagination.pending') }}</div>
                                    <div class="fw-bold fs-4 text-warning" id="summaryPendingNet">—</div>
                                    <small class="text-muted" id="summaryPendingCount">—</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-3">
                    <div class="card bg-light-success border border-success border-dashed h-100">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center">
                                <div class="symbol symbol-40px me-3">
                                    <span class="symbol-label bg-success">
                                        <i class="bi bi-check-circle-fill fs-2 text-white"></i>
                                    </span>
                                </div>
                                <div>
                                    <div class="text-muted fs-8">{{ __('pagination.paid') }}</div>
                                    <div class="fw-bold fs-4 text-success" id="summaryPaidNet">—</div>
                                    <small class="text-muted" id="summaryPaidCount">—</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-3">
                    <div class="card bg-light-info border border-info border-dashed h-100">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center">
                                <div class="symbol symbol-40px me-3">
                                    <span class="symbol-label bg-info">
                                        <i class="bi bi-arrow-repeat fs-2 text-white"></i>
                                    </span>
                                </div>
                                <div>
                                    <div class="text-muted fs-8">{{ __('pagination.reimbursed') }}</div>
                                    <div class="fw-bold fs-4 text-info" id="summaryReimbursedNet">—</div>
                                    <small class="text-muted" id="summaryReimbursedCount">—</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══════════════════════════════════════════════════════ --}}
            {{-- FILTERS                                                   --}}
            {{-- ═══════════════════════════════════════════════════════ --}}
            <div class="card mb-6">
                <div class="card-body py-4">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-2">
                            <label class="fs-8 text-muted mb-1">{{ __('accounting.start_date') }}</label>
                            <input type="date" id="expenseStartDate" class="form-control form-control-sm"
                                   value="{{ now()->startOfMonth()->toDateString() }}">
                        </div>
                        <div class="col-md-2">
                            <label class="fs-8 text-muted mb-1">{{ __('accounting.end_date') }}</label>
                            <input type="date" id="expenseEndDate" class="form-control form-control-sm"
                                   value="{{ now()->toDateString() }}">
                        </div>
                        <div class="col-md-2">
                            <label class="fs-8 text-muted mb-1">{{ __('pagination.payment_status') }}</label>
                            <select id="expenseStatusFilter" class="form-select form-select-sm">
                                <option value="">{{ __('auth.all_statuses') }}</option>
                                <option value="pending">{{ __('pagination.pending') }}</option>
                                <option value="paid">{{ __('pagination.paid') }}</option>
                                <option value="reimbursed">{{ __('pagination.reimbursed') }}</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="fs-8 text-muted mb-1">{{ __('auth.location') }}</label>
                            <select id="expenseLocationFilter" class="form-select form-select-sm" data-control="select2">
                                <option value="">{{ __('auth.all_locations') }}</option>
                                @foreach($locations ?? [] as $loc)
                                    <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="fs-8 text-muted mb-1">{{ __('auth.department') }}</label>
                            <select id="expenseDepartmentFilter" class="form-select form-select-sm" data-control="select2">
                                <option value="">{{ __('auth.all_departments') }}</option>
                                @foreach($departments ?? [] as $dep)
                                    <option value="{{ $dep->id }}" data-location-id="{{ $dep->location_id }}">
                                        {{ $dep->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="fs-8 text-muted mb-1">{{ __('payments.payment_method') }}</label>
                            <select id="expensePaymentMethodFilter" class="form-select form-select-sm" data-control="select2">
                                <option value="">{{ __('auth.all') }}</option>
                                @foreach($PaymentMethods ?? [] as $pm)
                                    <option value="{{ $pm->id }}">{{ $pm->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mt-2">
                        <div class="col-md-4">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" id="expenseSearchInput" class="form-control"
                                       placeholder="{{ __('auth._search') }}...">
                            </div>
                        </div>
                        <div class="col-md-8 d-flex justify-content-end flex-wrap gap-2">
                            <button type="button" class="btn btn-sm btn-light" onclick="applyQuickRange('today')">{{ __('auth.today') }}</button>
                            <button type="button" class="btn btn-sm btn-light" onclick="applyQuickRange('week')">{{ __('auth.this_week') }}</button>
                            <button type="button" class="btn btn-sm btn-light" onclick="applyQuickRange('month')">{{ __('auth.this_month') }}</button>
                            <button type="button" class="btn btn-sm btn-light" onclick="applyQuickRange('last_month')">{{ __('auth.last_month') }}</button>
                            <button type="button" class="btn btn-sm btn-primary" onclick="applyExpenseFilters()">
                                <i class="bi bi-funnel"></i> {{ __('accounting.apply_filters') }}
                            </button>
                            <button type="button" class="btn btn-sm btn-light" onclick="resetExpenseFilters()">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══════════════════════════════════════════════════════ --}}
            {{-- TABLE — JS fills tbody                                     --}}
            {{-- ═══════════════════════════════════════════════════════ --}}
            <div class="card">
                <div class="card-body">

                    <div id="expensesLoading" class="text-center py-10">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-3 text-muted">{{ __('auth.loading') }}...</p>
                    </div>

                    <div id="expensesEmpty" class="text-center py-10 d-none">
                        <i class="bi bi-inbox fs-1 text-muted"></i>
                        <p class="text-muted mt-2">{{ __('pagination.no_expenses_found') }}</p>
                    </div>

                    <div id="expensesWrapper" class="d-none">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase">
                                        <th>{{ __('pagination.description') }}</th>
                                        <th>{{ __('payments.date') }}</th>
                                        <th>{{ __('auth.supplier') }}</th>
                                        <th class="text-end">{{ __('pagination.amount') }}</th>
                                        <th>{{ __('payments.payment_method') }}</th>
                                        <th>{{ __('passwords.location') }}</th>
                                        <th>{{ __('pagination.approve') }}</th>
                                        <th>{{ __('pagination.payment_status') }}</th>
                                        <th class="text-end">{{ __('auth._actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="expensesTableBody"></tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div class="text-muted fs-7" id="expensesPageInfo"></div>
                            <ul class="pagination m-0" id="expensesPagination"></ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Modals --}}
    @include('procurement.expense.create')
    @include('procurement.expense-template.bulk-create')

        {{-- Single generic edit modal, populated by JS --}}
    @include('procurement.expense.edit')

    {{-- Single generic upload receipt modal, populated by JS --}}
    @include('procurement.expense.upload-receipt')

    @endsection

    

    @push('scripts')
<script>
    window.ExpenseConfig = {
        urls: {
            data:          '{{ route("expense.data") }}',
            approve:       (id) => `/expenses/${id}/approve`,
            updateStatus:  (id) => `/expense-status/${id}`,
            destroy:       (id) => `/expense/${id}`,
            uploadReceipt: (id) => `/expenses/${id}/receipt`,
        },
        csrf:           '{{ csrf_token() }}',
        currencySymbol: '{{ currency_symbol() }}',
        t: {
            all:           '{{ __("auth.all") }}',
            allStatuses:   '{{ __("auth.all_statuses") }}',
            approved:      '{{ __("pagination.approved") }}',
            pending:       '{{ __("pagination.pending") }}',
            paid:          '{{ __("pagination.paid") }}',
            reimbursed:    '{{ __("pagination.reimbursed") }}',
            approve:       '{{ __("pagination.approve") }}',
            expenses:      '{{ __("auth.expenses") }}',
            noPM:          '{{ __("accounting.no_payment_method") }}',
            confirm:       '{{ __("auth.are_you_sure") }}',
            errorOccurred: '{{ __("auth.error_occurred") }}',
            deleted:       '{{ __("auth._deleted") }}',
            updated:       '{{ __("auth._updated") }}',
            uploading:     '{{ __("auth.uploading") }}',
        },
        defaults: {
            start_date: '{{ now()->startOfMonth()->toDateString() }}',
            end_date:   '{{ now()->toDateString() }}',
        },
    };
</script>

<script>
(function () {
    'use strict';

    console.log('[expenses] script running');

    const CFG = window.ExpenseConfig;
    const $id = (id) => document.getElementById(id);

    // Never leave the spinner running if something is fundamentally broken
    function failLoud(message, err) {
        console.error('[expenses] ' + message, err || '');
        $id('expensesLoading')?.classList.add('d-none');
        $id('expensesEmpty')?.classList.remove('d-none');
    }

    if (!CFG) {
        failLoud('window.ExpenseConfig is missing; config script was not rendered before this one');
        return;
    }

    // ─────────────────────────────────────────────────────────
    // State
    // ─────────────────────────────────────────────────────────
    const FILTER_KEYS = [
        'search', 'start_date', 'end_date', 'payment_status',
        'location_id', 'department_id', 'payment_method_id',
    ];

    const State = {
        page: 1,
        perPage: 15,
        filters: {
            search:            '',
            start_date:        CFG.defaults.start_date,
            end_date:          CFG.defaults.end_date,
            payment_status:    '',
            location_id:       '',
            department_id:     '',
            payment_method_id: '',
        },

        toQueryString() {
            const params = new URLSearchParams();
            params.set('page', this.page);
            params.set('per_page', this.perPage);
            FILTER_KEYS.forEach(k => {
                const v = this.filters[k];
                if (v !== '' && v !== null && v !== undefined) params.set(k, v);
            });
            return params.toString();
        },

        syncFromUrl() {
            const url = new URL(window.location.href);
            FILTER_KEYS.forEach(k => {
                if (url.searchParams.has(k)) this.filters[k] = url.searchParams.get(k);
            });
            if (url.searchParams.has('page')) {
                this.page = parseInt(url.searchParams.get('page'), 10) || 1;
            }
        },

        pushToUrl() {
            const url = new URL(window.location.href);
            FILTER_KEYS.forEach(k => {
                const v = this.filters[k];
                if (v === '' || v === null || v === undefined) url.searchParams.delete(k);
                else url.searchParams.set(k, v);
            });
            if (this.page > 1) url.searchParams.set('page', this.page);
            else url.searchParams.delete('page');
            window.history.replaceState({}, '', url.toString());
        },

        // Push current State.filters back into the form controls (used after syncFromUrl)
        writeToInputs() {
            const map = {
                search:            'expenseSearchInput',
                start_date:        'expenseStartDate',
                end_date:          'expenseEndDate',
                payment_status:    'expenseStatusFilter',
                location_id:       'expenseLocationFilter',
                department_id:     'expenseDepartmentFilter',
                payment_method_id: 'expensePaymentMethodFilter',
            };
            Object.entries(map).forEach(([key, elId]) => {
                const el = $id(elId);
                if (!el) return;
                el.value = this.filters[key] ?? '';
                if (window.jQuery && window.jQuery(el).data('select2')) {
                    window.jQuery(el).val(el.value).trigger('change.select2');
                }
            });
        },
    };

    // ─────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────
    function esc(s) {
        if (s === null || s === undefined) return '';
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function money(n) {
        return CFG.currencySymbol + Number(n || 0).toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    }

    function toast(kind, message) {
        if (window.toastr) {
            (toastr[kind] || toastr.info)(message);
        } else {
            console[kind === 'error' ? 'error' : 'log'](message);
        }
    }

    // Parses JSON only when the server actually returned JSON
    // (login redirects and 500 pages come back as HTML)
    async function parseJson(res) {
        const ct = res.headers.get('content-type') || '';
        if (!ct.includes('application/json')) {
            throw new Error(res.redirected
                ? 'Session expired. Please log in again.'
                : `Unexpected response (HTTP ${res.status})`);
        }
        return res.json();
    }

    const jsonHeaders = () => ({
        'X-CSRF-TOKEN':     CFG.csrf,
        'Accept':           'application/json',
        'Content-Type':     'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    });

    // ─────────────────────────────────────────────────────────
    // Summary
    // ─────────────────────────────────────────────────────────
    function renderSummary(s) {
        if (!s) return;
        const set = (id, v) => { const el = $id(id); if (el) el.textContent = v; };

        set('summaryTotalNet',        money(s.total_net));
        set('summaryTotalCount',      `${s.total_count} ${CFG.t.expenses}`);
        set('summaryPendingNet',      money(s.pending_amount));
        set('summaryPendingCount',    `${s.pending_count} ${CFG.t.expenses}`);
        set('summaryPaidNet',         money(s.paid_amount));
        set('summaryPaidCount',       `${s.paid_count} ${CFG.t.expenses}`);
        set('summaryReimbursedNet',   money(s.reimbursed_amount));
        set('summaryReimbursedCount', `${s.reimbursed_count} ${CFG.t.expenses}`);
    }

    // ─────────────────────────────────────────────────────────
    // Row cells
    // ─────────────────────────────────────────────────────────
    function cellPaymentMethod(pm) {
        if (!pm || !pm.name) {
            return `<span class="badge badge-light-secondary">
                        <i class="bi bi-exclamation-triangle me-1"></i> ${esc(CFG.t.noPM)}
                    </span>`;
        }
        const colors = {
            cash: 'warning', bank_account: 'info', digital_wallet: 'primary',
            card: 'success', check: 'secondary', mobile_money: 'danger',
        };
        const color = colors[pm.type] || 'dark';
        const star  = pm.is_default ? '<i class="bi bi-star-fill text-success me-1"></i>' : '';
        return `<span class="badge badge-light-${color}">${star}${esc(pm.name)}</span>`;
    }

    function cellApproval(e) {
        if (e.approved_at) {
            return `<span class="badge badge-success">
                        <i class="bi bi-check-circle-fill me-1"></i> ${esc(CFG.t.approved)}
                    </span>`;
        }
        return `<button type="button" class="btn btn-sm btn-light-success"
                        onclick="ExpenseActions.approve(${Number(e.id)}, true)">
                    <i class="bi bi-check-circle"></i> ${esc(CFG.t.approve)}
                </button>`;
    }

    function cellStatus(e) {
        const status = e.payment_status;
        const paidDisabled = (!e.approved_at && status !== 'paid') ? 'disabled' : '';
        return `
            <select class="form-select form-select-sm form-select-solid"
                    onchange="ExpenseActions.updateStatus(${Number(e.id)}, this.value)">
                <option value="pending"    ${status === 'pending'    ? 'selected' : ''}>${esc(CFG.t.pending)}</option>
                <option value="paid"       ${status === 'paid'       ? 'selected' : ''} ${paidDisabled}>${esc(CFG.t.paid)}</option>
                <option value="reimbursed" ${status === 'reimbursed' ? 'selected' : ''}>${esc(CFG.t.reimbursed)}</option>
            </select>`;
    }

    function cellActions(e) {
        const editBtn = `
            <button type="button" class="btn btn-sm btn-light me-1"
                    onclick="ExpenseActions.edit(${Number(e.id)})" title="Edit">
                <i class="bi bi-pencil-square"></i>
            </button>`;

        const uploadBtn = `
            <button type="button" class="btn btn-sm btn-light me-1"
                    onclick="ExpenseActions.uploadReceipt(${Number(e.id)})" title="Upload receipt">
                <i class="bi bi-upload"></i>
            </button>`;

        const deleteBtn = (e.payment_status !== 'paid')
            ? `<button type="button" class="btn btn-sm btn-light-danger"
                    onclick="ExpenseActions.destroy(${Number(e.id)})" title="Delete">
                <i class="bi bi-trash"></i>
            </button>`
            : '';

        return `<div class="d-flex justify-content-end gap-1">${editBtn}${uploadBtn}${deleteBtn}</div>`;
    }

    // ─────────────────────────────────────────────────────────
    // Table
    // ─────────────────────────────────────────────────────────
    function renderRows(rows) {
        const tbody   = $id('expensesTableBody');
        const wrapper = $id('expensesWrapper');
        const empty   = $id('expensesEmpty');
        if (!tbody) return;

        if (!Array.isArray(rows) || rows.length === 0) {
            tbody.innerHTML = '';
            wrapper.classList.add('d-none');
            empty.classList.remove('d-none');
            return;
        }

        empty.classList.add('d-none');

        tbody.innerHTML = rows.map(e => `
            <tr>
                <td>
                    <div class="fw-bold text-gray-800">${esc(e.description)}</div>
                    <small class="text-muted">${esc(e.expense_number)}</small>
                </td>
                <td>
                    <span class="badge badge-light-success">
                        <i class="bi bi-calendar3 me-1"></i> ${esc(e.date_formatted || '—')}
                    </span>
                </td>
                <td>${esc(e.vendor_name || '—')}</td>
                <td class="text-end"><span class="fw-bold">${esc(e.amount_formatted || '')}</span></td>
                <td>${cellPaymentMethod(e.payment_method)}</td>
                <td>
                    <span class="badge badge-light-primary">${esc(e.location?.name || 'N/A')}</span>
                </td>
                <td>${cellApproval(e)}</td>
                <td>${cellStatus(e)}</td>
                <td class="text-end">${cellActions(e)}</td>
            </tr>
        `).join('');

        wrapper.classList.remove('d-none');
    }

    // ─────────────────────────────────────────────────────────
    // Pagination
    // ─────────────────────────────────────────────────────────
    function renderPagination(p) {
        const info = $id('expensesPageInfo');
        const ul   = $id('expensesPagination');
        if (!info || !ul || !p) return;

        info.textContent = `Showing ${p.from || 0}–${p.to || 0} of ${p.total || 0}`;
        ul.innerHTML = '';

        const addEllipsis = () => {
            const li = document.createElement('li');
            li.className = 'page-item disabled';
            li.innerHTML = '<span class="page-link">…</span>';
            ul.appendChild(li);
        };

        const addPage = (page, label, active = false, disabled = false) => {
            const li = document.createElement('li');
            li.className = `page-item ${active ? 'active' : ''} ${disabled ? 'disabled' : ''}`;

            const a = document.createElement('a');
            a.className = 'page-link';
            a.href = '#';
            a.textContent = label;

            if (!disabled) {
                a.addEventListener('click', (ev) => {
                    ev.preventDefault();
                    State.page = page;
                    reload();
                });
            }

            li.appendChild(a);
            ul.appendChild(li);
        };

        addPage(p.current_page - 1, 'Previous', false, p.current_page <= 1);

        const start = Math.max(1, p.current_page - 2);
        const end   = Math.min(p.last_page, p.current_page + 2);

        if (start > 1) addPage(1, '1');
        if (start > 2) addEllipsis();

        for (let i = start; i <= end; i++) addPage(i, String(i), i === p.current_page);

        if (end < p.last_page - 1) addEllipsis();
        if (end < p.last_page) addPage(p.last_page, String(p.last_page));

        addPage(p.current_page + 1, 'Next', false, !p.has_more);
    }

    // ─────────────────────────────────────────────────────────
    // Reload — the single fetch that drives everything
    // ─────────────────────────────────────────────────────────
    let activeController = null;

    async function reload() {
        const loading = $id('expensesLoading');
        const empty   = $id('expensesEmpty');
        const wrapper = $id('expensesWrapper');

        // Cancel any in-flight request so stale responses can't overwrite newer ones
        if (activeController) activeController.abort();
        const controller = new AbortController();
        activeController = controller;

        loading.classList.remove('d-none');
        empty.classList.add('d-none');
        wrapper.classList.add('d-none');

        try {
            const res = await fetch(`${CFG.urls.data}?${State.toQueryString()}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                signal: controller.signal,
            });

            const data = await parseJson(res);

            if (!res.ok || !data.success) {
                throw new Error(data.message || `HTTP ${res.status}`);
            }

            renderSummary(data.summary);
            renderRows(data.data);
            renderPagination(data.pagination);
            State.pushToUrl();

        } catch (e) {
            if (e.name === 'AbortError') return; // superseded by a newer request

            console.error('[expenses] reload failed', e);
            toast('error', e.message || CFG.t.errorOccurred);
            empty.classList.remove('d-none');

        } finally {
            // Only the latest request is allowed to hide the loader
            if (activeController === controller) {
                loading.classList.add('d-none');
                activeController = null;
            }
        }
    }

    // ─────────────────────────────────────────────────────────
    // Filters
    // ─────────────────────────────────────────────────────────
    function readFiltersFromInputs() {
        const val = (id, fallback = '') => $id(id)?.value ?? fallback;
        return {
            search:            val('expenseSearchInput'),
            start_date:        val('expenseStartDate', CFG.defaults.start_date) || CFG.defaults.start_date,
            end_date:          val('expenseEndDate',   CFG.defaults.end_date)   || CFG.defaults.end_date,
            payment_status:    val('expenseStatusFilter'),
            location_id:       val('expenseLocationFilter'),
            department_id:     val('expenseDepartmentFilter'),
            payment_method_id: val('expensePaymentMethodFilter'),
        };
    }

    function applyFilters() {
        State.filters = readFiltersFromInputs();
        State.page = 1;
        reload();
    }

    // Select2 and native handlers can both fire for one user action; collapse them
    let applyTimer;
    function applyFiltersDebounced(delay = 50) {
        clearTimeout(applyTimer);
        applyTimer = setTimeout(applyFilters, delay);
    }

    function resetFilters() {
        State.filters = {
            search: '',
            start_date: CFG.defaults.start_date,
            end_date:   CFG.defaults.end_date,
            payment_status: '',
            location_id: '',
            department_id: '',
            payment_method_id: '',
        };
        State.page = 1;
        State.writeToInputs();
        reload();
    }

    function applyQuickRange(range) {
        const today = new Date();
        const pad = n => String(n).padStart(2, '0');
        const fmt = d => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;

        let from, to;
        switch (range) {
            case 'today':
                from = to = today;
                break;
            case 'week': {
                const day = today.getDay();
                const diffToMonday = (day === 0 ? -6 : 1 - day);
                from = new Date(today); from.setDate(today.getDate() + diffToMonday);
                to   = new Date(from);  to.setDate(from.getDate() + 6);
                break;
            }
            case 'month':
                from = new Date(today.getFullYear(), today.getMonth(), 1);
                to   = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                break;
            case 'last_month':
                from = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                to   = new Date(today.getFullYear(), today.getMonth(), 0);
                break;
            default:
                return;
        }

        $id('expenseStartDate').value = fmt(from);
        $id('expenseEndDate').value   = fmt(to);
        applyFilters();
    }

    // ─────────────────────────────────────────────────────────
    // Actions
    // ─────────────────────────────────────────────────────────
    async function postJson(url, method, body) {
        const res = await fetch(url, {
            method,
            headers: jsonHeaders(),
            credentials: 'same-origin',
            body: body === undefined ? undefined : JSON.stringify(body),
        });
        return parseJson(res);
    }

    const Actions = {
        async approve(id, checked = true) {
            try {
                const data = await postJson(CFG.urls.approve(id), 'POST', { approved: checked ? 1 : 0 });
                toast(data.success ? 'success' : 'error', data.message || (data.success ? CFG.t.updated : CFG.t.errorOccurred));
            } catch (e) {
                console.error(e);
                toast('error', e.message || CFG.t.errorOccurred);
            }
            reload();
        },

        async updateStatus(id, status) {
            try {
                const data = await postJson(CFG.urls.updateStatus(id), 'POST', { status });
                toast(data.success ? 'success' : 'error', data.message || (data.success ? CFG.t.updated : CFG.t.errorOccurred));
            } catch (e) {
                console.error(e);
                toast('error', e.message || CFG.t.errorOccurred);
            }
            reload();
        },

        async destroy(id) {
            if (!confirm(CFG.t.confirm)) return;
            try {
                const data = await postJson(CFG.urls.destroy(id), 'DELETE');
                if (data.success) {
                    toast('success', data.message || CFG.t.deleted);
                    reload();
                } else {
                    toast('error', data.message || CFG.t.errorOccurred);
                }
            } catch (e) {
                console.error(e);
                toast('error', e.message || CFG.t.errorOccurred);
            }
        },

        // ─────────────────────────────────────────────────────
        // Edit — fetch the expense, populate the modal, show it
        // ─────────────────────────────────────────────────────
        async edit(id) {
            try {
                const res = await fetch(`/expense/${id}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                const data = await parseJson(res);

                if (!data.success || !data.expense) {
                    throw new Error(data.message || 'Expense not found');
                }

                const e = data.expense;

                $id('editExpenseId').value           = e.id;
                $id('editExpenseNumber').textContent = e.expense_number || '';

                $id('editDate').value          = e.date || '';
                $id('editDescription').value   = e.description || '';
                $id('editCategoryId').value    = e.category_id || '';
                $id('editSupplierId').value    = e.supplier_id || '';
                $id('editEmployeeId').value    = e.employee_id || '';
                $id('editGrossAmount').value   = e.gross_amount ?? '';
                $id('editTaxAmount').value     = e.tax_amount   ?? 0;
                $id('editTotalAmount').value   = (Number(e.total_amount) || 0).toFixed(2);
                $id('editPaymentMethodId').value = e.payment_method_id || '';
                $id('editPaymentStatus').value   = e.payment_status || 'pending';
                $id('editPaidDate').value      = e.paid_date || '';
                $id('editLocationId').value    = e.location_id || '';
                $id('editDepartmentId').value  = e.department_id || '';

                // Cascade: hide departments not at this location
                const depEl = $id('editDepartmentId');
                if (depEl && depEl._allOptions) {
                    const loc = $id('editLocationId').value;
                    depEl.innerHTML = '';
                    depEl._allOptions
                        .filter(o => o.value === '' || !loc || String(o.locationId) === String(loc))
                        .forEach(o => {
                            const opt = document.createElement('option');
                            opt.value = o.value;
                            opt.textContent = o.text;
                            if (o.locationId) opt.setAttribute('data-location-id', o.locationId);
                            depEl.appendChild(opt);
                        });
                    depEl.value = e.department_id || '';
                }

                new bootstrap.Modal($id('expenseEditModal')).show();
            } catch (err) {
                console.error('[expenses] edit failed', err);
                toast('error', err.message || CFG.t.errorOccurred);
            }
        },

        // ─────────────────────────────────────────────────────
        // Upload receipt — opens the modal, NOT a raw file dialog
        // ─────────────────────────────────────────────────────
        async uploadReceipt(id) {
            try {
                const res = await fetch(`/expense/${id}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                const data = await parseJson(res);

                if (!data.success || !data.expense) {
                    throw new Error(data.message || 'Expense not found');
                }

                const e = data.expense;

                $id('uploadExpenseId').value            = e.id;
                $id('uploadExpenseNumber').textContent  = e.expense_number || '';

                // Show the existing receipt link if there is one
                const currentBox  = $id('uploadReceiptCurrent');
                const currentLink = $id('uploadReceiptCurrentLink');
                if (e.receipt_url) {
                    // Adjust the URL if you store on a different disk
                    currentLink.href = `/storage/${e.receipt_url}`;
                    currentBox.classList.remove('d-none');
                } else {
                    currentBox.classList.add('d-none');
                }

                // Reset the form fields (but keep the hidden id)
                const form = $id('expenseUploadReceiptForm');
                form.querySelector('input[type="file"]').value = '';
                form.querySelector('textarea[name="description"]').value = '';

                new bootstrap.Modal($id('expenseUploadReceiptModal')).show();
            } catch (err) {
                console.error('[expenses] upload prep failed', err);
                toast('error', err.message || CFG.t.errorOccurred);
            }
        },
    };

    // ─────────────────────────────────────────────────────────
    // Globals for inline handlers
    // ─────────────────────────────────────────────────────────
    window.ExpenseActions      = Actions;
    window.applyExpenseFilters = applyFilters;
    window.resetExpenseFilters = resetFilters;
    window.applyQuickRange     = applyQuickRange;
    window.reloadExpenseList   = reload;

    // ─────────────────────────────────────────────────────────
    // Boot — works whether DOMContentLoaded has fired or not
    // ─────────────────────────────────────────────────────────
    function boot() {
        console.log('[expenses] boot');

        if (!$id('expensesLoading') || !$id('expensesTableBody')) {
            // Page markup isn't there (e.g. tenant_can('expenses') was false)
            console.warn('[expenses] list markup not found; skipping boot');
            return;
        }

        State.syncFromUrl();
        State.writeToInputs();

        // Filter auto-apply.
        // jQuery delegated binding catches both native changes and Select2's
        // jQuery-triggered changes, regardless of when Select2 initialises.
        const filterSelector = [
            '#expenseStatusFilter', '#expenseLocationFilter', '#expenseDepartmentFilter',
            '#expensePaymentMethodFilter', '#expenseStartDate', '#expenseEndDate',
        ].join(',');

        if (window.jQuery) {
            window.jQuery(document).on('change', filterSelector, () => applyFiltersDebounced());
        } else {
            document.querySelectorAll(filterSelector).forEach(el =>
                el.addEventListener('change', () => applyFiltersDebounced())
            );
        }

        // Debounced search
        $id('expenseSearchInput')?.addEventListener('input', () => applyFiltersDebounced(400));

        // ─────────────────────────────────────────────────────
        // Edit form submit
        // ─────────────────────────────────────────────────────
        document.getElementById('expenseEditForm')?.addEventListener('submit', async function (ev) {
            ev.preventDefault();

            const btn = document.getElementById('editExpenseSubmit');
            const label = btn.querySelector('.indicator-label');
            const prog  = btn.querySelector('.indicator-progress');
            label.classList.add('d-none');
            prog.classList.remove('d-none');

            const id = document.getElementById('editExpenseId').value;

            try {
                const res = await fetch(`/expense/${id}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': CFG.csrf,
                        'Accept':       'application/json',
                    },
                    credentials: 'same-origin',
                    body: new FormData(this),
                });

                const data = await parseJson(res);
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('expenseEditModal'))?.hide();
                    toast('success', data.message || CFG.t.updated);
                    reload();
                } else {
                    toast('error', data.message || CFG.t.errorOccurred);
                }
            } catch (err) {
                console.error(err);
                toast('error', err.message || CFG.t.errorOccurred);
            } finally {
                label.classList.remove('d-none');
                prog.classList.add('d-none');
            }
        });

        // ─────────────────────────────────────────────────────
        // Upload receipt form submit
        // ─────────────────────────────────────────────────────
        document.getElementById('expenseUploadReceiptForm')?.addEventListener('submit', async function (ev) {
            ev.preventDefault();

            const id = document.getElementById('uploadExpenseId').value;
            const fd = new FormData(this);
            fd.append('_method', 'PUT');

            try {
                const res = await fetch(`/expenses/${id}/receipt`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': CFG.csrf,
                        'Accept':       'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: fd,
                });

                const ct = res.headers.get('content-type') || '';
                if (ct.includes('application/json')) {
                    const data = await res.json();
                    if (data.success) {
                        bootstrap.Modal.getInstance(document.getElementById('expenseUploadReceiptModal'))?.hide();
                        toast('success', data.message || CFG.t.updated);
                        reload();
                    } else {
                        toast('error', data.message || CFG.t.errorOccurred);
                    }
                } else {
                    // Controller returned HTML (redirect)
                    bootstrap.Modal.getInstance(document.getElementById('expenseUploadReceiptModal'))?.hide();
                    toast('success', CFG.t.updated);
                    reload();
                }
            } catch (err) {
                console.error(err);
                toast('error', err.message || CFG.t.errorOccurred);
            }
        });

        // Auto-calc total in the edit modal
        ['editGrossAmount', 'editTaxAmount'].forEach(id => {
            document.getElementById(id)?.addEventListener('input', () => {
                const gross = parseFloat(document.getElementById('editGrossAmount').value) || 0;
                const tax   = parseFloat(document.getElementById('editTaxAmount').value)   || 0;
                document.getElementById('editTotalAmount').value = (gross + tax).toFixed(2);
            });
        });

        // Department cascades from location in the edit modal
        (function () {
            const locEl = document.getElementById('editLocationId');
            const depEl = document.getElementById('editDepartmentId');
            if (!locEl || !depEl) return;

            const allOptions = Array.from(depEl.options).map(o => ({
                value: o.value,
                text: o.textContent,
                locationId: o.getAttribute('data-location-id'),
            }));

            function refilter() {
                const loc = locEl.value;
                const current = depEl.value;

                depEl.innerHTML = '';
                allOptions
                    .filter(o => o.value === '' || !loc || String(o.locationId) === String(loc))
                    .forEach(o => {
                        const opt = document.createElement('option');
                        opt.value = o.value;
                        opt.textContent = o.text;
                        if (o.locationId) opt.setAttribute('data-location-id', o.locationId);
                        depEl.appendChild(opt);
                    });

                depEl.value = Array.from(depEl.options).some(o => o.value === current) ? current : '';
            }

            locEl.addEventListener('change', refilter);
        })();



        reload();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
</script>
@endpush
</x-app-layout>