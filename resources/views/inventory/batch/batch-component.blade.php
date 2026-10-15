<x-app-layout>
    @section('title', __('passwords.batch_inventory'))
    @section('content')

    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-4 gap-md-0">
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-md-1 flex-column my-0">
                    {{ __('passwords.batch_inventory') }}
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ url()->previous() }}" class="text-muted text-hover-primary">
                            {{ __('auth._back') }}
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">{{ __('passwords.batch_inventory') }}</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">

                {{-- ─── FILTER CARD ─────────────────────────────────── --}}
                <div class="card card-flush mb-5">
                    <div class="card-header align-items-center py-5 gap-2 gap-md-5">
                        <div class="d-flex align-items-center position-relative my-1">
                            <input type="text"
                                id="batchSearchInput"
                                class="form-control form-control-solid w-250px pe-12"
                                placeholder="{{ __('passwords.search_batches') }}"
                                value="{{ $initialFilters['search'] }}" />
                            <i class="ki-duotone ki-magnifier fs-3 position-absolute end-0 me-4 top-50 translate-middle-y">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                        </div>
                        <div class="card-toolbar flex-row-fluid justify-content-end gap-3">
                            @if($locations->count() > 0)
                                <select id="batchLocationFilter"
                                        class="form-select form-select-solid w-200px">
                                    <option value="">{{ __('passwords.all_locations') }}</option>
                                    @foreach($locations as $loc)
                                        <option value="{{ $loc->id }}"
                                                @selected((int) $initialFilters['location_id'] === (int) $loc->id)>
                                            {{ $loc->name }}
                                        </option>
                                    @endforeach
                                </select>
                            @endif

                            <select id="batchDepartmentFilter"
                                    class="form-select form-select-solid w-200px"
                                    disabled>
                                <option value="">{{ __('passwords.all_departments') }}</option>
                            </select>

                            @can('edit inventory')
                                <button type="button"
                                        class="btn btn-primary flex-shrink-0"
                                        onclick="assignSelectedBatches()">
                                    <i class="ki-duotone ki-tag fs-2 me-2 me-sm-3"></i>
                                    <span class="d-none d-sm-inline">{{ __('passwords.assign_batches') }}</span>
                                    <span class="d-inline d-sm-none">{{ __('passwords.assign') }}</span>
                                </button>
                            @endcan
                        </div>
                    </div>
                </div>

                {{-- ─── SUMMARY CARDS ───────────────────────────────── --}}
                <div class="row g-5 g-xl-8 mb-5">
                    <div class="col-xl-6">
                        <div class="card card-flush bg-light-primary">
                            <div class="card-body py-5">
                                <div class="d-flex justify-content-between">
                                    <div class="d-flex flex-column">
                                        <span class="text-muted fw-semibold">{{ __('passwords.total_batches') }}</span>
                                        <span class="fs-1 fw-bold text-primary"
                                              id="summaryTotalBatches">—</span>
                                    </div>
                                    <i class="ki-duotone ki-parcel fs-3x text-primary opacity-50">
                                        <span class="path1"></span><span class="path2"></span>
                                        <span class="path3"></span><span class="path4"></span>
                                    </i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="card card-flush bg-light-success">
                            <div class="card-body py-5">
                                <div class="d-flex justify-content-between">
                                    <div class="d-flex flex-column">
                                        <span class="text-muted fw-semibold">{{ __('passwords.total_quantity_remaining') }}</span>
                                        <span class="fs-1 fw-bold text-success"
                                              id="summaryTotalQuantity">—</span>
                                    </div>
                                    <i class="ki-duotone ki-chart-simple fs-3x text-success opacity-50">
                                        <span class="path1"></span><span class="path2"></span>
                                        <span class="path3"></span><span class="path4"></span>
                                    </i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ─── TABLE CARD ─────────────────────────────────── --}}
                <div class="card card-flush">
                    <div class="card-header align-items-center py-5">
                        <h3 class="card-title fw-bold">
                            <span class="text-gray-800">{{ __('passwords.batches') }}</span>
                        </h3>
                        <div class="card-toolbar">
                            <select id="batchPerPage" class="form-select form-select-solid form-select-sm w-75px">
                                @foreach([15, 25, 50, 100] as $pp)
                                    <option value="{{ $pp }}" @selected((int) $initialFilters['per_page'] === $pp)>
                                        {{ $pp }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="card-body pt-0">
                        {{-- Skeleton --}}
                        <div id="batchLoadingSpinner" class="d-none">
                            @for ($i = 0; $i < 6; $i++)
                                <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                                    <div class="skeleton skeleton-checkbox" style="width:18px;height:18px;"></div>
                                    <div class="flex-grow-1">
                                        <div class="skeleton skeleton-text" style="width:180px;"></div>
                                        <div class="skeleton skeleton-text" style="width:120px;"></div>
                                    </div>
                                    <div class="skeleton skeleton-text" style="width:80px;"></div>
                                    <div class="skeleton skeleton-text" style="width:100px;"></div>
                                    <div class="skeleton skeleton-text" style="width:60px;"></div>
                                </div>
                            @endfor
                        </div>

                        {{-- Table --}}
                        <div id="batchTableContainer" class="d-none">
                            <div class="table-responsive">
                                <table class="table align-middle table-row-dashed table-row-gray-300 fs-6 gy-4 mb-0">
                                    <thead>
                                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                            <th class="w-10px pe-2">
                                                <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                                    <input class="form-check-input" type="checkbox" id="batchSelectAll" value="1" />
                                                </div>
                                            </th>
                                            <th class="min-w-150px">{{ __('passwords.batch_number') }}</th>
                                            <th class="min-w-200px">{{ __('passwords.product') }}</th>
                                            <th class="min-w-100px">{{ __('passwords.quantity') }}</th>
                                            <th class="min-w-100px">{{ __('passwords.unit_cost') }}</th>
                                            <th class="min-w-120px">{{ __('passwords.expiry_date') }}</th>
                                            <th class="min-w-160px">{{ __('passwords.location') }}</th>
                                            <th class="min-w-150px">{{ __('auth._department') }}</th>
                                            <th class="min-w-100px text-end">{{ __('auth._actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody id="batchTableBody"></tbody>
                                </table>
                            </div>

                            <div id="batchPaginationContainer"
                                 class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-5 d-none">
                                <div id="batchPaginationInfo" class="text-muted fs-7"></div>
                                <nav>
                                    <ul class="pagination pagination-outline m-0" id="batchPagination"></ul>
                                </nav>
                            </div>
                        </div>

                        {{-- Empty state --}}
                        <div id="batchNoDataMessage" class="text-center py-15 d-none">
                            <i class="ki-duotone ki-parcel fs-5tx text-muted mb-4 d-block">
                                <span class="path1"></span><span class="path2"></span>
                                <span class="path3"></span><span class="path4"></span>
                            </i>
                            <h4 class="fw-bold text-gray-800 mb-2">{{ __('passwords.no_batches_found') }}</h4>
                            <p class="text-muted mb-5">{{ __('passwords.try_adjusting_filters') }}</p>
                            <button type="button" class="btn btn-sm btn-primary"
                                    onclick="window.BatchPage.resetFilters()">
                                {{ __('passwords.reset_filters') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
     Batch Details Modal
     ============================================================ --}}
    <div class="modal fade" id="batchDetailsModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content">

                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-0">
                            <i class="ki-duotone ki-parcel fs-2 me-2">
                                <span class="path1"></span><span class="path2"></span>
                                <span class="path3"></span><span class="path4"></span>
                            </i>
                            <span id="batchDetailsTitle">{{ __('passwords.batch_details') }}</span>
                        </h5>
                        <small class="text-muted" id="batchDetailsSubtitle"></small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body" id="batchDetailsBody">
                    {{-- Rendered by JS --}}
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        {{ __('auth._close') }}
                    </button>
                </div>

            </div>
        </div>
    </div>
    
    @include('inventory.batch.assign-modal')
    @include('inventory.batch.split-modal')

    @endsection

    @push('styles')
    <style>
        .skeleton {
            display: inline-block;
            background: linear-gradient(90deg, #f1f1f4 0%, #e9e9ed 50%, #f1f1f4 100%);
            background-size: 200% 100%;
            animation: skeleton-shimmer 1.4s ease-in-out infinite;
            border-radius: 6px;
        }
        .skeleton-checkbox { border-radius: 4px; }
        .skeleton-text     { height: 12px; margin: 4px 0; display: block; }

        @keyframes skeleton-shimmer {
            0%   { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
    </style>
    @endpush

    @push('scripts')
    <script>
    (function () {
        'use strict';

        // ─────────────────────────────────────────────────────────
        // Config
        // ─────────────────────────────────────────────────────────
        const URLS = {
            data:          '{{ route("batches.data") }}',
            details:       '{{ route("batches.details") }}',
            departments:   '{{ route("get.departments.by.location", ["locationId" => "__LOC__"]) }}',
        };

        const CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        // Initial state from the server-rendered inputs
        const state = {
            page:           1,
            perPage:        {{ (int) $initialFilters['per_page'] }},
            search:         @json($initialFilters['search']),
            locationId:     @json($initialFilters['location_id']),
            departmentId:   @json($initialFilters['department_id']),
        };

        let searchDebounce;
        const selectedBatchIds = new Set();

        // ─────────────────────────────────────────────────────────
        // Bootstrap
        // ─────────────────────────────────────────────────────────
        document.addEventListener('DOMContentLoaded', () => {
            bindEvents();
            initCascadingDepartments(state.locationId, state.departmentId);
            loadBatches();
        });

        function bindEvents() {
            const searchInput = document.getElementById('batchSearchInput');
            searchInput?.addEventListener('input', function () {
                clearTimeout(searchDebounce);
                searchDebounce = setTimeout(() => {
                    state.search = this.value.trim();
                    state.page = 1;
                    loadBatches();
                }, 400);
            });

            document.getElementById('batchLocationFilter')?.addEventListener('change', function () {
                state.locationId = this.value;
                state.departmentId = '';
                state.page = 1;
                initCascadingDepartments(state.locationId, '');
                loadBatches();
            });

            document.getElementById('batchDepartmentFilter')?.addEventListener('change', function () {
                state.departmentId = this.value;
                state.page = 1;
                loadBatches();
            });

            document.getElementById('batchPerPage')?.addEventListener('change', function () {
                state.perPage = parseInt(this.value, 10) || 15;
                state.page = 1;
                loadBatches();
            });

            // Select-all in the header
            document.getElementById('batchSelectAll')?.addEventListener('change', function () {
                const checked = this.checked;
                document.querySelectorAll('.batch-row-checkbox').forEach(cb => {
                    cb.checked = checked;
                    toggleRowSelection(cb.value, checked);
                });
                syncSelectionUI();
            });
        }

        // ─────────────────────────────────────────────────────────
        // Location → Department cascade
        // ─────────────────────────────────────────────────────────
        async function initCascadingDepartments(locationId, preselectDepartmentId) {
            const deptSelect = document.getElementById('batchDepartmentFilter');
            if (!deptSelect) return;

            deptSelect.innerHTML = `<option value="">{{ __("passwords.all_departments") }}</option>`;
            deptSelect.disabled = true;

            if (!locationId) return;

            try {
                const url = URLS.departments.replace('__LOC__', encodeURIComponent(locationId));
                const res = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);

                const data = await res.json();
                const list = Array.isArray(data) ? data : Object.values(data || {});

                list.forEach(d => {
                    if (!d || !d.id) return;
                    const opt = document.createElement('option');
                    opt.value = d.id;
                    opt.textContent = d.name;
                    if (String(d.id) === String(preselectDepartmentId)) opt.selected = true;
                    deptSelect.appendChild(opt);
                });

                deptSelect.disabled = list.length === 0;
            } catch (err) {
                console.warn('[batch] failed to load departments:', err);
            }
        }

        // ─────────────────────────────────────────────────────────
        // Data loading
        // ─────────────────────────────────────────────────────────
        async function loadBatches() {
            showState('loading');

            const params = new URLSearchParams({
                page:     state.page,
                per_page: state.perPage,
            });
            if (state.search)       params.set('search', state.search);
            if (state.locationId)   params.set('location_id', state.locationId);
            if (state.departmentId) params.set('department_id', state.departmentId);

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

                // Update summary cards
                document.getElementById('summaryTotalBatches').textContent  = json.summary.total_batches ?? 0;
                document.getElementById('summaryTotalQuantity').textContent = formatNumber(json.summary.total_quantity ?? 0, 2);

                // Empty rows → empty state
                if (!json.data || json.data.length === 0) {
                    showState('empty');
                    return;
                }

                renderTable(json.data);
                renderPagination(json.pagination);
                showState('table');

            } catch (err) {
                console.error('[batch] load failed:', err);
                showState('empty');
                window.toastr?.error?.('{{ __("passwords.failed_to_load_batches") }}');
            }
        }

        // ─────────────────────────────────────────────────────────
        // Table render
        // ─────────────────────────────────────────────────────────
        function renderTable(rows) {
            const tbody = document.getElementById('batchTableBody');
            tbody.innerHTML = rows.map(row => {
                const isChecked = selectedBatchIds.has(String(row.id));

                return `
                    <tr data-batch-id="${row.id}">
                        <td>
                            <div class="form-check form-check-sm form-check-custom form-check-solid">
                                <input class="form-check-input batch-row-checkbox"
                                       type="checkbox"
                                       value="${row.id}"
                                       ${isChecked ? 'checked' : ''} />
                            </div>
                        </td>
                        <td>
                            <span class="fw-bold text-gray-800">${escapeHtml(row.batch_number)}</span>
                        </td>
                        <td>
                            <div class="d-flex flex-column">
                                <span class="fw-semibold text-gray-800">${escapeHtml(row.variant_name)}</span>
                                <span class="text-muted fs-7">SKU: ${escapeHtml(row.sku)}</span>
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-light-primary fw-bold">
                                ${formatNumber(row.quantity_remaining, 2)}
                            </span>
                        </td>
                        <td>
                            <span class="fw-semibold">${escapeHtml(row.unit_cost_formatted)}</span>
                        </td>
                        <td>
                            <span class="badge badge-light-${row.expiry_badge}">
                                ${escapeHtml(row.expiry_text)}
                            </span>
                        </td>
                        <td>
                            ${row.location_id
                                ? `<span class="badge badge-light-info">${escapeHtml(row.location_name)}</span>`
                                : `<span class="text-muted">${__('Unassigned')}</span>`}
                        </td>
                        <td>
                            ${row.department_id
                                ? `<span class="badge badge-light">${escapeHtml(row.department_name)}</span>`
                                : `<span class="text-muted">—</span>`}
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-1">
                                <button class="btn btn-sm btn-icon btn-light btn-active-light-primary"
                                        type="button"
                                        onclick="viewBatchDetails(${row.id})"
                                        title="{{ __('passwords.view_details') }}">
                                    <i class="ki-duotone ki-eye fs-4">
                                        <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                                    </i>
                                </button>
                                <button class="btn btn-sm btn-icon btn-light btn-active-light-primary"
                                        type="button"
                                        onclick="openSplitBatchModal(${row.id}, '${escapeHtml(row.batch_number)}', ${row.quantity_remaining})"
                                        title="{{ __('passwords.split_batch') }}">
                                    <i class="bi bi-scissors fs-5"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');

            // Wire row checkboxes
            tbody.querySelectorAll('.batch-row-checkbox').forEach(cb => {
                cb.addEventListener('change', function () {
                    toggleRowSelection(this.value, this.checked);
                    syncSelectionUI();
                });
            });

            syncSelectionUI();
        }

        function toggleRowSelection(id, selected) {
            if (selected) selectedBatchIds.add(String(id));
            else          selectedBatchIds.delete(String(id));
        }

        function syncSelectionUI() {
            const all = document.querySelectorAll('.batch-row-checkbox');
            const checked = document.querySelectorAll('.batch-row-checkbox:checked');
            const selectAll = document.getElementById('batchSelectAll');

            if (selectAll) {
                selectAll.checked = all.length > 0 && checked.length === all.length;
                selectAll.indeterminate = checked.length > 0 && checked.length < all.length;
            }
        }

        // ─────────────────────────────────────────────────────────
        // Pagination
        // ─────────────────────────────────────────────────────────
        function renderPagination(p) {
            const ul = document.getElementById('batchPagination');
            const info = document.getElementById('batchPaginationInfo');
            const wrap = document.getElementById('batchPaginationContainer');
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

            for (let i = start; i <= end; i++) {
                addItem(i, i, i === p.current_page);
            }

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
            loadBatches();
            document.getElementById('batchTableContainer')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        // ─────────────────────────────────────────────────────────
        // State views
        // ─────────────────────────────────────────────────────────
        function showState(which) {
            const loading = document.getElementById('batchLoadingSpinner');
            const table   = document.getElementById('batchTableContainer');
            const empty   = document.getElementById('batchNoDataMessage');
            const pager   = document.getElementById('batchPaginationContainer');

            loading.classList.toggle('d-none', which !== 'loading');
            table.classList.toggle('d-none',   which !== 'table');
            empty.classList.toggle('d-none',   which !== 'empty');
            pager.classList.toggle('d-none',   which !== 'table');
        }

        // ─────────────────────────────────────────────────────────
        // Reset filters
        // ─────────────────────────────────────────────────────────
        function resetFilters() {
            state.search = state.locationId = state.departmentId = '';
            state.page = 1;

            document.getElementById('batchSearchInput').value = '';
            document.getElementById('batchLocationFilter').value = '';
            document.getElementById('batchDepartmentFilter').innerHTML =
                `<option value="">{{ __("passwords.all_departments") }}</option>`;
            document.getElementById('batchDepartmentFilter').disabled = true;

            loadBatches();
        }
        

        // ─────────────────────────────────────────────────────────
        // Batch Details Modal — full provenance + history
        // ─────────────────────────────────────────────────────────
        window.viewBatchDetails = async function (batchId) {
            const modalEl = document.getElementById('batchDetailsModal');
            const body    = document.getElementById('batchDetailsBody');
            const title   = document.getElementById('batchDetailsTitle');
            const subtitle= document.getElementById('batchDetailsSubtitle');

            // Reset + show loading
            title.textContent    = '{{ __("passwords.batch_details") }}';
            subtitle.textContent = '';
            body.innerHTML       = `
                <div class="text-center py-10">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="text-muted mt-3">{{ __("passwords.loading_details") }}</div>
                </div>
            `;

            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();

            try {
                const res = await fetch('{{ route("batches.details") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': CSRF,
                    },
                    body: JSON.stringify({ batch_ids: [batchId] }),
                });

                if (!res.ok) throw new Error('HTTP ' + res.status);

                const json = await res.json();
                if (!json.success || !json.data?.length) {
                    body.innerHTML = `<div class="alert alert-danger">${__('Batch not found.')}</div>`;
                    return;
                }

                renderBatchDetails(json.data[0]);
            } catch (err) {
                console.error('[batch] details failed:', err);
                body.innerHTML = `<div class="alert alert-danger">${__('Failed to load batch details.')}</div>`;
            }
        };

        function renderBatchDetails(b) {
            const title    = document.getElementById('batchDetailsTitle');
            const subtitle = document.getElementById('batchDetailsSubtitle');
            const body     = document.getElementById('batchDetailsBody');

            title.textContent    = b.batch_number;
            subtitle.textContent = `${b.variant.name}${b.variant.sku ? ' · SKU ' + b.variant.sku : ''}`;

            const r = b.rollup;
            const p = b.provenance;
            const c = b.current;

            // Status badge
            const statusMap = {
                assigned:  { label: '{{ __("passwords.assigned") }}',  cls: 'success' },
                available: { label: '{{ __("passwords.available") }}', cls: 'info' },
                depleted:  { label: '{{ __("passwords.depleted") }}',  cls: 'secondary' },
            };
            const statusInfo = statusMap[c.status] ?? { label: c.status, cls: 'secondary' };

            // ── Section 1: Provenance (PO + receipt) ─────────────
            const provenanceRows = `
                <tr>
                    <td class="text-muted">${__('Purchase Order')}</td>
                    <td class="fw-bold">
                        ${p.purchase_order_number
                            ? escapeHtml(p.purchase_order_number)
                            : '<span class="text-muted">—</span>'}
                    </td>
                </tr>
                <tr>
                    <td class="text-muted">${__('PO Date')}</td>
                    <td>${escapeHtml(p.purchase_order_date ?? '—')}</td>
                </tr>
                <tr>
                    <td class="text-muted">${__('Supplier')}</td>
                    <td class="fw-semibold">${escapeHtml(p.supplier_name ?? '—')}</td>
                </tr>
                <tr>
                    <td class="text-muted">${__('Receipt')}</td>
                    <td>#${p.purchase_receipt_id ?? '—'}</td>
                </tr>
                <tr>
                    <td class="text-muted">${__('Ordered Quantity')}</td>
                    <td>${formatNumber(p.ordered_quantity, 2)}</td>
                </tr>
                <tr class="table-active">
                    <td class="fw-semibold">${__('Original Quantity Received')}</td>
                    <td class="fw-bold text-primary">${formatNumber(p.original_received, 2)}</td>
                </tr>
                <tr>
                    <td class="text-muted">${__('Unit Cost at Receipt')}</td>
                    <td>${formatMoney(p.unit_cost_at_receipt)}</td>
                </tr>
            `;

            // ── Section 2: Current state ─────────────────────────
            const currentRows = `
                <tr>
                    <td class="text-muted">${__('Location')}</td>
                    <td>
                        ${c.location_id
                            ? `<span class="badge badge-light-info">${escapeHtml(c.location_name)}</span>`
                            : `<span class="text-muted">${__('Unassigned')}</span>`}
                    </td>
                </tr>
                <tr>
                    <td class="text-muted">${__('Department')}</td>
                    <td>
                        ${c.department_id
                            ? `<span class="badge badge-light">${escapeHtml(c.department_name)}</span>`
                            : `<span class="text-muted">—</span>`}
                    </td>
                </tr>
                <tr>
                    <td class="text-muted">${__('Expiry')}</td>
                    <td>${escapeHtml(c.expiry_date ?? '—')}</td>
                </tr>
                <tr>
                    <td class="text-muted">${__('Unit Cost')}</td>
                    <td>${formatMoney(c.unit_cost)}</td>
                </tr>
                <tr class="table-active">
                    <td class="fw-semibold">${__('Remaining on Shelf')}</td>
                    <td>
                        <span class="badge badge-light-${c.quantity_remaining > 0 ? 'success' : 'secondary'} fs-6 fw-bold">
                            ${formatNumber(c.quantity_remaining, 2)}
                        </span>
                    </td>
                </tr>
                <tr>
                    <td class="text-muted">${__('Status')}</td>
                    <td><span class="badge badge-light-${statusInfo.cls}">${statusInfo.label}</span></td>
                </tr>
            `;

            // ── Section 3: Rollup cards ──────────────────────────
            const rollupCards = `
                <div class="row g-3 mb-5">
                    <div class="col-md-3">
                        <div class="border rounded p-3 text-center">
                            <div class="text-muted fs-7">${__('Original')}</div>
                            <div class="fw-bold fs-4">${formatNumber(r.original_received, 2)}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3 text-center bg-light-danger">
                            <div class="text-muted fs-7">${__('Consumed by Factory')}</div>
                            <div class="fw-bold fs-4 text-danger">${formatNumber(r.consumed_by_production, 2)}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3 text-center">
                            <div class="text-muted fs-7">${__('Other Movements')}</div>
                            <div class="fw-bold fs-4">${formatNumber(r.total_added + (r.total_consumed - r.consumed_by_production), 2)}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3 text-center bg-light-success">
                            <div class="text-muted fs-7">${__('Remaining')}</div>
                            <div class="fw-bold fs-4 text-success">${formatNumber(r.quantity_remaining, 2)}</div>
                        </div>
                    </div>
                </div>
            `;

            // ── Section 4: Movement history timeline ─────────────
            const historyRows = b.history.length
                ? b.history.map(h => {
                    const change    = h.quantity_change;
                    const changeCls = change > 0 ? 'success' : (change < 0 ? 'danger' : 'secondary');
                    const changeStr = change > 0
                        ? '+' + formatNumber(change, 2)
                        : formatNumber(change, 2);

                    return `
                        <tr>
                            <td class="text-muted fs-7">${escapeHtml(h.event_date_human ?? '—')}</td>
                            <td>
                                <span class="badge badge-light-secondary">${escapeHtml(h.type_label)}</span>
                            </td>
                            <td>
                                <span class="fw-semibold">${escapeHtml(h.source)}</span>
                                ${h.production_number
                                    ? `<br><small class="text-muted">${escapeHtml(h.note ?? '')}</small>`
                                    : (h.note ? `<br><small class="text-muted">${escapeHtml(h.note)}</small>` : '')}
                            </td>
                            <td class="text-end">
                                <span class="fw-bold text-${changeCls}">${changeStr}</span>
                            </td>
                            <td class="text-end text-muted fs-7">${formatNumber(h.quantity_after, 2)}</td>
                            <td class="text-muted fs-7">${escapeHtml(h.performed_by ?? '—')}</td>
                        </tr>
                    `;
                }).join('')
                : `<tr><td colspan="6" class="text-center text-muted py-5">${__('No movements recorded')}</td></tr>`;

            // ── Compose the modal ───────────────────────────────
            body.innerHTML = `
                ${rollupCards}

                <ul class="nav nav-tabs nav-line-tabs mb-5" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#tab-provenance">
                            ${__('Provenance')}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#tab-current">
                            ${__('Current State')}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#tab-history">
                            ${__('Movement History')}
                            <span class="badge badge-light-primary ms-2">${b.history.length}</span>
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="tab-provenance">
                        <table class="table table-row-dashed fs-6 gy-3">
                            <tbody>${provenanceRows}</tbody>
                        </table>
                    </div>
                    <div class="tab-pane fade" id="tab-current">
                        <table class="table table-row-dashed fs-6 gy-3">
                            <tbody>${currentRows}</tbody>
                        </table>
                    </div>
                    <div class="tab-pane fade" id="tab-history">
                        <div class="table-responsive">
                            <table class="table table-row-dashed table-row-gray-300 fs-7 gy-3">
                                <thead>
                                    <tr class="text-muted fw-bold text-uppercase fs-8">
                                        <th>${__('When')}</th>
                                        <th>${__('Type')}</th>
                                        <th>${__('Source')}</th>
                                        <th class="text-end">${__('Change')}</th>
                                        <th class="text-end">${__('After')}</th>
                                        <th>${__('By')}</th>
                                    </tr>
                                </thead>
                                <tbody>${historyRows}</tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
        }

        // ─────────────────────────────────────────────────────────
        // Helpers used by the details modal
        // ─────────────────────────────────────────────────────────
        function formatMoney(n) {
            const num = Number(n ?? 0);
            return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        // ─────────────────────────────────────────────────────────
        // Expose helpers to other scripts (assign modal, etc.)
        // ─────────────────────────────────────────────────────────
        window.BatchPage = {
            getSelectedIds: () => Array.from(selectedBatchIds),
            clearSelection: () => {
                selectedBatchIds.clear();
                document.querySelectorAll('.batch-row-checkbox').forEach(cb => cb.checked = false);
                syncSelectionUI();
            },
            resetFilters,
            reload: loadBatches,
        };

        // ─────────────────────────────────────────────────────────
        // Open the Assign modal for the current selection
        // ─────────────────────────────────────────────────────────
        window.assignSelectedBatches = function () {
            const ids = window.BatchPage.getSelectedIds();

            if (!ids.length) {
                window.toastr?.warning?.('{{ __("passwords.no_batches_selected") }}');
                return;
            }

            window.openAssignBatchModal(ids);
        };

        // ─────────────────────────────────────────────────────────
        // Utilities
        // ─────────────────────────────────────────────────────────
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

        function __(s) { return s; }   // tiny i18n shim
    })();
    </script>
    @endpush
</x-app-layout>
            
                