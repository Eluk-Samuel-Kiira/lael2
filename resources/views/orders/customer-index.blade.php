<x-app-layout>
    @section('title', __('passwords.customer_index'))
    @section('content')

    <div class="app-toolbar py-3 py-lg-6">
        <div class="app-container container-fluid d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-4 w-100">
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading text-gray-900 fw-bold fs-2hx my-0">
                    {{ __('passwords.customer_table') }}
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">
                            {{ __('pagination.dashboard') }}
                        </a>
                    </li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted">{{ __('passwords.customer_table') }}</li>
                </ul>
            </div>

            <div class="d-flex align-items-stretch align-items-sm-center gap-3 w-100 w-md-auto">
                <div class="flex-grow-1 flex-sm-grow-0" style="min-width: 240px; max-width: 320px;">
                    <div class="d-flex align-items-center position-relative w-100">
                        <input type="text" id="customerSearchInput"
                               class="form-control form-control-solid w-100 pe-10"
                               placeholder="{{ __('auth._search') }} {{ __('passwords.customer') }}">
                        <i class="bi bi-search fs-4 position-absolute end-0 me-3 top-50 translate-middle-y text-muted"></i>
                    </div>
                </div>

                @can('create customer')
                    <button type="button" class="btn btn-primary flex-shrink-0"
                            data-bs-toggle="modal" data-bs-target="#kt_modal_add_customer">
                        <i class="ki-duotone ki-plus fs-2 me-2"></i>
                        <span class="d-none d-sm-inline">{{ __('passwords.customer_new') }}</span>
                    </button>
                @endcan
            </div>
        </div>
    </div>

    <div class="app-content flex-column-fluid">
        <div class="app-container container-xxl">

            {{-- Stat cards --}}
            <div class="row g-4 mb-6">
                <div class="col-6 col-md-4">
                    <div class="card bg-light-primary">
                        <div class="card-body py-4">
                            <span class="text-muted fs-8 fw-semibold text-uppercase d-block">Total Customers</span>
                            <span class="fs-2x fw-bold text-primary" id="statTotal">—</span>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="card bg-light-success">
                        <div class="card-body py-4">
                            <span class="text-muted fs-8 fw-semibold text-uppercase d-block">Active</span>
                            <span class="fs-2x fw-bold text-success" id="statActive">—</span>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="card bg-light-warning">
                        <div class="card-body py-4">
                            <span class="text-muted fs-8 fw-semibold text-uppercase d-block">Inactive</span>
                            <span class="fs-2x fw-bold text-warning" id="statInactive">—</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filters --}}
            <div class="card mb-6">
                <div class="card-body py-4">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold fs-7">{{ __('auth._search') }}</label>
                            <div class="position-relative">
                                <input type="text" id="customerSearchTop" class="form-control form-control-solid pe-10"
                                       placeholder="{{ __('auth._search') }}…">
                                <i class="bi bi-search fs-4 position-absolute end-0 me-3 top-50 translate-middle-y text-muted"></i>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold fs-7">{{ __('passwords.customer_group') }}</label>
                            <select id="customerGroupFilter" class="form-select form-select-solid">
                                <option value="">All groups</option>
                                @foreach($customerGroups ?? [] as $group)
                                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold fs-7">{{ __('auth._status') }}</label>
                            <select id="customerStatusFilter" class="form-select form-select-solid">
                                <option value="">All statuses</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold fs-7">Per page</label>
                            <select id="customerPerPage" class="form-select form-select-solid">
                                <option value="15">15 / page</option>
                                <option value="25">25 / page</option>
                                <option value="50">50 / page</option>
                                <option value="100">100 / page</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="card card-flush">
                <div class="card-body pt-0">
                    <div id="customerLoading" class="text-center py-10 d-none">
                        <div class="spinner-border text-primary"></div>
                    </div>

                    <div id="customerWrapper" class="d-none">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase">
                                        <th class="min-w-200px">Customer</th>
                                        <th class="min-w-200px">Contact</th>
                                        <th class="min-w-150px">Group</th>
                                        <th class="min-w-100px">Creator</th>
                                        <th class="min-w-100px">Status</th>
                                        <th class="min-w-40px text-end"></th>
                                    </tr>
                                </thead>
                                <tbody id="customerTableBody"></tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div class="text-muted fs-7" id="customerPageInfo"></div>
                            <ul class="pagination m-0" id="customerPagination"></ul>
                        </div>
                    </div>

                    <div id="customerEmpty" class="text-center py-15 d-none">
                        <i class="bi bi-file-earmark fs-5tx text-muted mb-4 d-block"></i>
                        <h4 class="fw-bold text-gray-800 mb-2">No customers found</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('orders.customer.create-customer')
    @include('orders.customer.edit-shared')
    @include('orders.customer.drawer')
    @endsection

    @push('scripts')
    <script>
        function setButtonLoading(button, isLoading) {
            if (!button) return;
            const label    = button.querySelector('.indicator-label');
            const progress = button.querySelector('.indicator-progress');

            if (isLoading) {
                button.disabled = true;
                if (label)    label.style.display    = 'none';
                if (progress) progress.style.display = 'inline';
            } else {
                button.disabled = false;
                if (label)    label.style.display    = 'inline';
                if (progress) progress.style.display = 'none';
            }
        }

        function clearFormErrors(form) {
            if (!form) return;
            form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            form.querySelectorAll('.invalid-feedback.d-block').forEach(el => {
                el.textContent = '';
                el.classList.remove('d-block');
            });
        }

        window.CUSTOMER_ROUTES = {
            data:        '{{ route('customer.data') }}',
            drawer_data: '{{ route('customer.drawer-data', ['id' => '__ID__']) }}',
            status:      '{{ route('customer.status', ['id' => '__ID__']) }}',
            update:      '{{ route('customer.update', ['customer' => '__ID__']) }}',
            destroy:     '{{ route('customer.destroy', ['customer' => '__ID__']) }}',
        };
        window.CUSTOMER_BOOTSTRAP = {
            search:  @json($initialFilters['search'] ?? ''),
            groupId: @json($initialFilters['group_id'] ?? ''),
            status:  @json($initialFilters['status'] ?? ''),
            perPage: @json($initialFilters['per_page'] ?? 15),
        };
    </script>
    @include('orders.customer.list-scripts')
    @include('orders.customer.drawer-scripts')
    @endpush
</x-app-layout>