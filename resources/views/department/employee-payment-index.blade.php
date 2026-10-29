<x-app-layout>
    @section('title', __('payments.employee_payment_index'))
    @section('content')

    <div class="app-toolbar py-3 py-lg-6">
        <div class="app-container container-fluid d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-4">
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading text-gray-900 fw-bold fs-2hx my-0">{{ __('payments.employee_payment_table') }}</h1>
            </div>
            <div class="d-flex align-items-stretch gap-3 w-100 w-md-auto">
                <div class="flex-grow-1 flex-sm-grow-0" style="min-width: 240px; max-width: 320px;">
                    <div class="d-flex align-items-center position-relative w-100">
                        <input type="text" id="paymentSearchInput"
                               class="form-control form-control-solid w-100 pe-10"
                               placeholder="{{ __('auth._search') }} {{ __('payments.employee') }}">
                        <i class="bi bi-search fs-4 position-absolute end-0 me-3 top-50 translate-middle-y text-muted"></i>
                    </div>
                </div>
                @can('export employee payment')
                    <button type="button" class="btn btn-light-primary flex-shrink-0" id="btnExportPayments">
                        <i class="ki-duotone ki-file-down fs-2 me-2"><span class="path1"></span><span class="path2"></span></i>
                        <span class="d-none d-sm-inline">{{ __('payments.export') }}</span>
                    </button>
                @endcan
                @can('create employee payment')
                    <button type="button" class="btn btn-primary flex-shrink-0"
                            data-bs-toggle="modal" data-bs-target="#kt_modal_add_payment">
                        <i class="ki-duotone ki-plus fs-2 me-2"></i>
                        <span class="d-none d-sm-inline">{{ __('payments.payment_new') }}</span>
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
                    <div class="card bg-light-primary"><div class="card-body py-4">
                        <span class="text-muted fs-8 fw-semibold text-uppercase d-block">{{ __('payments.total_payments') }}</span>
                        <span class="fs-2x fw-bold text-primary" id="statTotalPayments">—</span>
                    </div></div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card bg-light-success"><div class="card-body py-4">
                        <span class="text-muted fs-8 fw-semibold text-uppercase d-block">{{ __('payments.total_amount') }}</span>
                        <span class="fs-2x fw-bold text-success" id="statTotalAmount">—</span>
                    </div></div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card bg-light-warning"><div class="card-body py-4">
                        <span class="text-muted fs-8 fw-semibold text-uppercase d-block">{{ __('payments.this_month') }}</span>
                        <span class="fs-2x fw-bold text-warning" id="statThisMonth">—</span>
                    </div></div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card bg-light-danger"><div class="card-body py-4">
                        <span class="text-muted fs-8 fw-semibold text-uppercase d-block">{{ __('payments.pending_payments') }}</span>
                        <span class="fs-2x fw-bold text-danger" id="statPending">—</span>
                    </div></div>
                </div>
            </div>

            {{-- Filters --}}
            <div class="card mb-6">
                <div class="card-body py-4">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label fw-semibold fs-7">{{ __('payments.status') }}</label>
                            <select id="paymentStatusFilter" class="form-select form-select-solid">
                                <option value="">{{ __('payments.all_status') }}</option>
                                <option value="pending">{{ __('payments.pending') }}</option>
                                <option value="completed">{{ __('payments.completed') }}</option>
                                <option value="failed">{{ __('payments.failed') }}</option>
                                <option value="cancelled">{{ __('payments.cancelled') }}</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold fs-7">{{ __('payments.payment_type') }}</label>
                            <select id="paymentTypeFilter" class="form-select form-select-solid">
                                <option value="">{{ __('payments.all_types') }}</option>
                                <option value="salary">{{ __('payments.salary') }}</option>
                                <option value="allowance">{{ __('payments.allowance') }}</option>
                                <option value="bonus">{{ __('payments.bonus') }}</option>
                                <option value="overtime">{{ __('payments.overtime') }}</option>
                                <option value="other">{{ __('payments.other') }}</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold fs-7">{{ __('payments.date_from') }}</label>
                            <input type="date" id="paymentDateFrom" class="form-control form-control-solid">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold fs-7">{{ __('payments.date_to') }}</label>
                            <input type="date" id="paymentDateTo" class="form-control form-control-solid">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold fs-7">Per page</label>
                            <select id="paymentPerPage" class="form-select form-select-solid">
                                <option value="15">15 / page</option>
                                <option value="25">25 / page</option>
                                <option value="50">50 / page</option>
                                <option value="100">100 / page</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="button" id="btnResetPaymentFilters" class="btn btn-light w-100">{{ __('payments.reset') }}</button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="card card-flush">
                <div class="card-body pt-0">
                    <div id="payLoading" class="text-center py-10 d-none"><div class="spinner-border text-primary"></div></div>

                    <div id="payWrapper" class="d-none">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase">
                                        <th class="min-w-180px">{{ __('payments.employee') }}</th>
                                        <th class="min-w-100px">{{ __('payments.payment_type') }}</th>
                                        <th class="min-w-150px">{{ __('payments.payment_method') }}</th>
                                        <th class="min-w-100px text-end">{{ __('payments.gross_amount') }}</th>
                                        <th class="min-w-100px text-end">{{ __('payments.net_amount') }}</th>
                                        <th class="min-w-100px">{{ __('payments.payment_date') }}</th>
                                        <th class="min-w-100px text-center">{{ __('payments.payment_status') }}</th>
                                        <th class="min-w-40px text-end"></th>
                                    </tr>
                                </thead>
                                <tbody id="payTableBody"></tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div class="text-muted fs-7" id="payPageInfo"></div>
                            <ul class="pagination m-0" id="payPagination"></ul>
                        </div>
                    </div>

                    <div id="payEmpty" class="text-center py-15 d-none">
                        <i class="bi bi-cash-coin fs-5tx text-muted mb-4 d-block"></i>
                        <h4 class="fw-bold text-gray-800 mb-2">{{ __('payments.no_payments_found') }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('department.employee-payment.create')
    @include('department.employee-payment.payment-drawer')
    @include('department.employee-payment.payment-edit-modal')
    @endsection

    @push('scripts')
    <script>
        window.PAYMENT_ROUTES = {
            data:         '{{ route('payment.data') }}',
            drawer_data:  '{{ route('payment.drawer-data', ['id' => '__ID__']) }}',
            meta:         '{{ route('payment.meta') }}',
            store:        '{{ route('payment.store') }}',
            update:       '{{ route('payment.update', ['payment' => '__ID__']) }}',
            destroy:      '{{ route('payment.destroy', ['payment' => '__ID__']) }}',
            status:       '{{ route('payment.status', ['id' => '__ID__']) }}',
            tax_preview:  '{{ route('payment.calculate-tax-preview') }}',
            {{-- export:       '{{ route('payment.export') }}', --}}
        };
        window.PAYMENT_BOOTSTRAP = {
            search:  @json($initialFilters['search'] ?? ''),
            perPage: @json($initialFilters['per_page'] ?? 15),
        };
    </script>
    @include('department.employee-payment.payment-list-scripts')
    @include('department.employee-payment.payment-drawer-scripts')
    @include('department.employee-payment.payment-page-scripts')
    @endpush
</x-app-layout>