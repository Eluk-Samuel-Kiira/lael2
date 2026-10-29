<x-app-layout>
    @section('title', __('payments._payment_methods_index'))
    @section('content')

    <div class="app-toolbar py-3 py-lg-6">
        <div class="app-container container-fluid d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-4 w-100">
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading text-gray-900 fw-bold fs-2hx my-0">
                    {{ __('payments._payment_methods_table') }}
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">
                            {{ __('pagination.dashboard') }}
                        </a>
                    </li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted">{{ __('payments._payment_methods') }}</li>
                </ul>
            </div>

            <div class="d-flex align-items-stretch align-items-sm-center gap-3 w-100 w-md-auto">
                @can('create payment method')
                    <button type="button" class="btn btn-primary flex-shrink-0"
                            data-bs-toggle="modal" data-bs-target="#kt_modal_add_payment_method">
                        <i class="ki-duotone ki-plus fs-2 me-2"></i>
                        <span class="d-none d-sm-inline">{{ __('payments.new_payment_method') }}</span>
                    </button>
                @endcan
            </div>
        </div>
    </div>

    <div class="app-content flex-column-fluid">
        <div class="app-container container-xxl">

            {{-- Stat cards --}}
            <div class="row g-4 mb-6">
                <div class="col-6 col-md-3">
                    <div class="card bg-light-primary">
                        <div class="card-body py-4">
                            <span class="text-muted fs-8 fw-semibold text-uppercase d-block">Total Methods</span>
                            <span class="fs-2x fw-bold text-primary" id="statTotal">—</span>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card bg-light-success">
                        <div class="card-body py-4">
                            <span class="text-muted fs-8 fw-semibold text-uppercase d-block">Active</span>
                            <span class="fs-2x fw-bold text-success" id="statActive">—</span>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card bg-light-warning">
                        <div class="card-body py-4">
                            <span class="text-muted fs-8 fw-semibold text-uppercase d-block">Inactive</span>
                            <span class="fs-2x fw-bold text-warning" id="statInactive">—</span>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card bg-light-info">
                        <div class="card-body py-4">
                            <span class="text-muted fs-8 fw-semibold text-uppercase d-block">Total Balance</span>
                            <span class="fs-3 fw-bold text-info" id="statBalance">—</span>
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
                                <input type="text" id="pmSearchTop" class="form-control form-control-solid pe-10"
                                       placeholder="{{ __('auth._search') }}…">
                                <i class="bi bi-search fs-4 position-absolute end-0 me-3 top-50 translate-middle-y text-muted"></i>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold fs-7">{{ __('auth._status') }}</label>
                            <select id="pmStatusFilter" class="form-select form-select-solid">
                                <option value="">All statuses</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold fs-7">{{ __('auth.location') }}</label>
                            <select id="pmLocationFilter" class="form-select form-select-solid">
                                <option value="">{{ __('payments.all_locations') }}</option>
                                @foreach($locations ?? [] as $loc)
                                    <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold fs-7">Per page</label>
                            <select id="pmPerPage" class="form-select form-select-solid">
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
                    <div id="pmLoading" class="text-center py-10 d-none">
                        <div class="spinner-border text-primary"></div>
                    </div>

                    <div id="pmWrapper" class="d-none">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase">
                                        <th class="min-w-150px">Name</th>
                                        <th class="min-w-100px">Type</th>
                                        <th class="min-w-120px">Provider</th>
                                        <th class="min-w-120px">Account</th>
                                        <th class="min-w-120px text-end">Balance</th>
                                        <th class="min-w-100px">Creator</th>
                                        <th class="min-w-100px">Status</th>
                                        <th class="min-w-40px text-end"></th>
                                    </tr>
                                </thead>
                                <tbody id="pmTableBody"></tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div class="text-muted fs-7" id="pmPageInfo"></div>
                            <ul class="pagination m-0" id="pmPagination"></ul>
                        </div>
                    </div>

                    <div id="pmEmpty" class="text-center py-15 d-none">
                        <i class="bi bi-file-earmark fs-5tx text-muted mb-4 d-block"></i>
                        <h4 class="fw-bold text-gray-800 mb-2">{{ __('payments.no_payment_methods_found') }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('settings.payment-method.create')
    @include('settings.payment-method.edit-shared')
    @include('settings.payment-method.transfer-shared')
    @include('settings.payment-method.drawer')
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

        window.PM_ROUTES = {
            data:        '{{ route('paymentmethod.data') }}',
            drawer_data: '{{ route('paymentmethod.drawer-data', ['id' => '__ID__']) }}',
            status:      '{{ route('paymentmethod.status', ['id' => '__ID__']) }}',
            update:      '{{ route('paymentmethod.update', ['paymentmethod' => '__ID__']) }}',
            destroy:     '{{ route('paymentmethod.destroy', ['paymentmethod' => '__ID__']) }}',
            transfer:    '{{ route('paymentmethod.transfer') }}',
            preview:     '{{ route('paymentmethod.transfer.preview') }}',
        };
        window.PM_BOOTSTRAP = {
            search:     @json($initialFilters['search'] ?? ''),
            locationId: @json($initialFilters['location_id'] ?? ''),
            perPage:    @json($initialFilters['per_page'] ?? 15),
        };
    </script>
    @include('settings.payment-method.list-scripts')
    @include('settings.payment-method.drawer-scripts')
    @endpush
</x-app-layout>