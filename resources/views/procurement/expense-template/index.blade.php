
<x-app-layout>
    @section('title', __('auth.expense_templates'))
    @section('content')

    <div class="app-toolbar py-3 py-lg-6">
        <div class="app-container container-fluid d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-4">
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx my-0">
                    {{ __('auth.expense_templates') }}
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">
                            {{ __('accounting.dashboard') }}
                        </a>
                    </li>
                    <li class="breadcrumb-item text-muted">{{ __('passwords.expense_table') }}</li>
                    <li class="breadcrumb-item text-muted">{{ __('auth.expense_templates') }}</li>
                </ul>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('expense.index') }}" class="btn btn-light">
                    <i class="ki-duotone ki-arrow-left fs-2"></i>
                    {{ __('auth.back_to_expenses') }}
                </a>
                @can('create expense')
                    <button type="button" class="btn btn-light-primary"
                            data-bs-toggle="modal"
                            data-bs-target="#kt_modal_bulk_templates">
                        <i class="ki-duotone ki-list-check fs-2 me-1"></i>
                        {{ __('auth.bulk_add_templates') }}
                    </button>
                @endcan
            </div>
        </div>
    </div>

    <div class="app-content flex-column-fluid">
        <div class="app-container container-fluid">

            {{-- ═══════════════════════════════════════════════════════ --}}
            {{-- Quick Log — most-used templates                          --}}
            {{-- ═══════════════════════════════════════════════════════ --}}
            @if($quickLog->isNotEmpty())
            <div class="card mb-6">
                <div class="card-header border-0">
                    <div class="card-title">
                        <i class="ki-duotone ki-flash fs-2 me-2 text-warning"></i>
                        <h3 class="fw-bold m-0">{{ __('auth.quick_log') }}</h3>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="row g-4">
                        @foreach($quickLog as $t)
                        <div class="col-md-6 col-xl-4">
                            <div class="card card-flush border border-dashed h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <div class="fw-bold fs-5">{{ $t->name }}</div>
                                            <small class="text-muted">
                                                {{ $t->category->name ?? '' }}
                                                @if($t->location)
                                                    · {{ $t->location->name }}
                                                @endif
                                            </small>
                                        </div>
                                        <span class="badge badge-light-primary">
                                            {{ $t->usage_count }}×
                                        </span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="text-muted fs-8">{{ __('auth.last_used') }}</div>
                                            <div class="fw-bold">
                                                @if($t->last_amount)
                                                    {{ currency_symbol() }}{{ number_format($t->last_amount, 2) }}
                                                @else
                                                    {{ __('auth.no_amount_yet') }}
                                                @endif
                                            </div>
                                            <div class="text-muted fs-8">
                                                {{ $t->last_used_at?->diffForHumans() }}
                                            </div>
                                        </div>
                                        <button class="btn btn-sm btn-primary"
                                                onclick="logTemplate({{ $t->id }})">
                                            <i class="ki-duotone ki-plus fs-3"></i>
                                            {{ __('auth.log_now') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            {{-- ═══════════════════════════════════════════════════════ --}}
            {{-- Likely Due                                              --}}
            {{-- ═══════════════════════════════════════════════════════ --}}
            @if($likelyDue->isNotEmpty())
            <div class="alert alert-warning d-flex align-items-center mb-6">
                <i class="ki-duotone ki-information-5 fs-2 me-3"></i>
                <div class="flex-grow-1">
                    <strong>{{ __('auth.likely_due') }}:</strong>
                    {{ trans_choice('auth.templates_likely_due_message', $likelyDue->count(), ['count' => $likelyDue->count()]) }}
                </div>
                <button class="btn btn-sm btn-warning" type="button"
                        data-bs-toggle="collapse" data-bs-target="#likelyDueList">
                    {{ __('auth.show') }}
                </button>
            </div>
            <div class="collapse mb-6" id="likelyDueList">
                <div class="card">
                    <div class="card-body">
                        <div class="row g-3">
                            @foreach($likelyDue as $t)
                            <div class="col-md-6 col-xl-4">
                                <div class="d-flex justify-content-between align-items-center p-3 bg-light-warning rounded">
                                    <div>
                                        <div class="fw-bold">{{ $t->name }}</div>
                                        <small class="text-muted">
                                            {{ $t->frequency }} ·
                                            {{ $t->last_used_at?->diffForHumans() }}
                                        </small>
                                    </div>
                                    <button class="btn btn-sm btn-warning"
                                            onclick="logTemplate({{ $t->id }})">
                                        {{ __('auth.log_now') }}
                                    </button>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- ═══════════════════════════════════════════════════════ --}}
            {{-- All Templates                                           --}}
            {{-- ═══════════════════════════════════════════════════════ --}}
            <div class="card">
                <div class="card-header border-0">
                    <div class="card-title d-flex align-items-center">
                        <i class="ki-duotone ki-tablet-text-up fs-2 me-2 text-primary"></i>
                        <h3 class="fw-bold m-0">{{ __('auth.all_templates') }}</h3>
                        <span class="badge badge-light-primary ms-3">{{ $templates->total() }}</span>
                    </div>
                    <div class="card-toolbar">
                        <form method="GET" class="d-flex gap-2">
                            <input type="text" name="search"
                                   class="form-control form-control-solid form-control-sm"
                                   placeholder="{{ __('auth._search') }}"
                                   value="{{ request('search') }}">
                            <select name="category_id" class="form-select form-select-sm"
                                    data-control="select2" style="width: 180px;">
                                <option value="">{{ __('auth.all_categories') }}</option>
                                @foreach($expenseCategories as $cat)
                                    <option value="{{ $cat->id }}"
                                        {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                            <button class="btn btn-sm btn-primary" type="submit">
                                {{ __('auth.filter') }}
                            </button>
                        </form>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-row-bordered table-row-dashed gy-4 align-middle">
                            <thead>
                                <tr class="fw-bold fs-7 text-gray-800 bg-light">
                                    <th class="ps-4">{{ __('auth.template_name') }}</th>
                                    <th>{{ __('pagination.category') }}</th>
                                    <th>{{ __('auth.default_location') }}</th>
                                    <th>{{ __('auth.default_department') }}</th>
                                    <th class="text-end">{{ __('auth.last_amount') }}</th>
                                    <th class="text-center">{{ __('auth.usage_count') }}</th>
                                    <th>{{ __('auth.last_used_at') }}</th>
                                    <th class="text-end">{{ __('auth._actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($templates as $t)
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold">{{ $t->name }}</div>
                                        @if($t->description)
                                            <small class="text-muted">{{ Str::limit($t->description, 40) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-light-primary">
                                            {{ $t->category->name ?? '—' }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($t->location)
                                            <span class="badge badge-light-info">
                                                {{ $t->location->name }}
                                            </span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($t->department)
                                            <span class="badge badge-light-secondary">
                                                {{ $t->department->name }}
                                            </span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if($t->last_amount)
                                            <span class="fw-bold">
                                                {{ currency_symbol() }}{{ number_format($t->last_amount, 2) }}
                                            </span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-light-primary">
                                            {{ $t->usage_count }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($t->last_used_at)
                                            <span title="{{ $t->last_used_at->format('Y-m-d H:i') }}">
                                                {{ $t->last_used_at->diffForHumans() }}
                                            </span>
                                        @else
                                            <span class="text-muted">{{ __('auth.never_used') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-primary me-1"
                                                onclick="logTemplate({{ $t->id }})">
                                            <i class="ki-duotone ki-plus fs-3"></i>
                                            {{ __('auth.log_now') }}
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-10">
                                        {{ __('auth.no_templates_found') }}
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($templates->hasPages() || $templates->count() > 0)
                <div class="card-footer">
                    @include('partials.pagination', [
                        'paginator'   => $templates,
                        'pageName'    => 'page',
                        'perPageName' => 'per_page',
                        'showPerPage' => true,
                    ])
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- The Log modal container --}}
    @include('procurement.expense-template.log-modal')

    {{-- Bulk-create modal --}}
    @include('procurement.expense-template.bulk-create')

    @endsection
</x-app-layout>