<x-app-layout>
    @section('title', __('payments.invoice_index'))
    @section('content')

    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container"
             class="app-container container-fluid d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-4 gap-md-0">
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-md-1 flex-column my-0">
                    {{ __('payments.invoice_table') }}
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
                    <li class="breadcrumb-item text-muted">{{ __('payments.invoice_table') }}</li>
                </ul>
            </div>

            <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-3 w-100 w-md-auto flex-wrap">
                {{-- Search --}}
                <div class="flex-grow-1 flex-sm-grow-0" style="min-width: 240px; max-width: 300px;">
                    <div class="d-flex align-items-center position-relative w-100">
                        <input type="text"
                               id="invoiceSearchInput"
                               class="form-control form-control-solid w-100 pe-12"
                               placeholder="{{ __('auth._search') }} {{ __('payments.invoice') }}"
                               value="{{ $initialFilters['search'] }}" />
                        <i class="ki-duotone ki-magnifier fs-3 position-absolute end-0 me-4 top-50 translate-middle-y">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                </div>

                {{-- Status --}}
                <div class="flex-grow-1 flex-sm-grow-0" style="min-width: 180px; max-width: 220px;">
                    <select id="invoiceStatusFilter" class="form-select form-select-solid w-100">
                        <option value="">{{ __('payments.all_statuses') }}</option>
                        @foreach(\App\Models\Invoice::STATUS_LABELS as $value => $label)
                            <option value="{{ $value }}" @selected($initialFilters['status'] === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Location --}}
                @if(isset($locations) && $locations->count() > 0)
                    <div class="flex-grow-1 flex-sm-grow-0" style="min-width: 180px; max-width: 220px;">
                        <select id="invoiceLocationFilter" class="form-select form-select-solid w-100">
                            <option value="">{{ __('passwords.all_locations') }}</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc->id }}"
                                        @selected((int) $initialFilters['location_id'] === (int) $loc->id)>
                                    {{ $loc->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                {{-- Issue date range --}}
                <div class="d-flex align-items-center gap-2 flex-grow-1 flex-sm-grow-0">
                    <input type="date"
                           id="invoiceIssueFrom"
                           class="form-control form-control-solid"
                           value="{{ $initialFilters['issue_date_from'] }}"
                           title="{{ __('payments.issue_date_from') ?? 'Issue from' }}" />
                    <span class="text-muted fs-8">—</span>
                    <input type="date"
                           id="invoiceIssueTo"
                           class="form-control form-control-solid"
                           value="{{ $initialFilters['issue_date_to'] }}"
                           title="{{ __('payments.issue_date_to') ?? 'Issue to' }}" />
                </div>
            </div>
        </div>
    </div>

    @if (tenant_can('pos'))
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">

                {{-- Summary cards --}}
                <div class="row g-5 g-xl-8 mb-5">
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-flush bg-light-primary">
                            <div class="card-body py-5">
                                <span class="text-muted fw-semibold d-block">{{ __('payments.total') ?? 'Total' }}</span>
                                <span class="fs-1 fw-bold text-primary" id="summaryTotal">—</span>
                                <small class="text-muted d-block mt-1" id="summaryBreakdown"></small>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-flush bg-light-success">
                            <div class="card-body py-5">
                                <span class="text-muted fw-semibold d-block">{{ __('payments.amount_paid') }}</span>
                                <span class="fs-1 fw-bold text-success" id="summaryPaid">—</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-flush bg-light-danger">
                            <div class="card-body py-5">
                                <span class="text-muted fw-semibold d-block">{{ __('payments.balance_due') }}</span>
                                <span class="fs-1 fw-bold text-danger" id="summaryOutstanding">—</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-flush bg-light-warning">
                            <div class="card-body py-5">
                                <span class="text-muted fw-semibold d-block">{{ __('payments.total_amount') ?? 'Invoice value' }}</span>
                                <span class="fs-1 fw-bold text-warning" id="summaryAmount">—</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Table card --}}
                <div class="card card-flush">
                    <div class="card-header align-items-center py-5">
                        <h3 class="card-title fw-bold">
                            <span class="text-gray-800">{{ __('payments.invoice_table') }}</span>
                        </h3>
                        <div class="card-toolbar">
                            <select id="invoicePerPage" class="form-select form-select-solid form-select-sm w-75px">
                                @foreach([15, 25, 50, 100] as $pp)
                                    <option value="{{ $pp }}" @selected($initialFilters['per_page'] === $pp)>
                                        {{ $pp }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="card-body pt-0">
                        {{-- Skeleton --}}
                        <div id="invoiceLoadingSpinner" class="d-none">
                            @for($i = 0; $i < 6; $i++)
                                <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                                    <div class="skeleton skeleton-text" style="width:18px;"></div>
                                    <div class="skeleton skeleton-text" style="width:140px;"></div>
                                    <div class="flex-grow-1">
                                        <div class="skeleton skeleton-text" style="width:180px;"></div>
                                        <div class="skeleton skeleton-text" style="width:120px;"></div>
                                    </div>
                                    <div class="skeleton skeleton-text" style="width:100px;"></div>
                                    <div class="skeleton skeleton-text" style="width:100px;"></div>
                                    <div class="skeleton skeleton-text" style="width:80px;"></div>
                                </div>
                            @endfor
                        </div>

                        {{-- Table --}}
                        <div id="invoiceTableContainer" class="d-none">
                            <div class="table-responsive">
                                <table class="table align-middle table-row-dashed table-row-gray-300 fs-6 gy-5 mb-0">
                                    <thead>
                                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                            <th class="min-w-125px">{{ __('payments.invoice_number') }}</th>
                                            <th class="min-w-150px">{{ __('payments.customer') }}</th>
                                            <th class="min-w-140px">{{ __('payments.location') }}</th>
                                            <th class="min-w-120px">{{ __('payments.issue_date') }}</th>
                                            <th class="min-w-120px">{{ __('payments.due_date') }}</th>
                                            <th class="min-w-120px">{{ __('payments.amount') }}</th>
                                            <th class="min-w-120px">{{ __('pagination.created_by') }}</th>
                                            <th class="min-w-120px">{{ __('payments.status') }}</th>
                                            <th class="min-w-100px text-end">{{ __('payments.actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody id="invoiceTableBody"></tbody>
                                </table>
                            </div>

                            <div id="invoicePaginationContainer"
                                 class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-5 d-none">
                                <div id="invoicePaginationInfo" class="text-muted fs-7"></div>
                                <nav>
                                    <ul class="pagination pagination-outline m-0" id="invoicePagination"></ul>
                                </nav>
                            </div>
                        </div>

                        {{-- Empty --}}
                        <div id="invoiceNoDataMessage" class="text-center py-15 d-none">
                            <i class="ki-duotone ki-file-deleted fs-5tx text-muted mb-4 d-block">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                            <h4 class="fw-bold text-gray-800 mb-2">{{ __('payments.no_invoices_found') }}</h4>
                            <p class="text-muted mb-5">{{ __('payments.no_invoices_message') }}</p>
                            <button type="button" class="btn btn-sm btn-primary"
                                    onclick="window.InvoiceList?.resetFilters()">
                                {{ __('payments.reset_filters') ?? 'Reset filters' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    @include('orders.invoice.drawer')

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
        .skeleton-text { height: 12px; margin: 4px 0; display: block; }
        @keyframes skeleton-shimmer {
            0%   { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        .invoice-number-link { cursor: pointer; }
        .invoice-number-link:hover { text-decoration: underline; }
    </style>
    @endpush

    @push('scripts')
        @include('orders.invoice.list-scripts')
        @include('orders.invoice.drawer-scripts')
    @endpush
</x-app-layout>