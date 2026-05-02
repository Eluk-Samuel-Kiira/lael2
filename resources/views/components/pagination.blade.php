@props([
    'paginator' => null,
    'showInfo' => true,
    'showPerPage' => false,
    'compact' => false,
    'id' => null,
    'containerClass' => 'mt-4',
    'perPageOptions' => [15, 25, 50, 100],
    'onPageChange' => null, // JavaScript callback for AJAX pagination
    'route' => null, // For AJAX mode, specify the route name
    'searchInputId' => null, // For AJAX mode with search
])

@if($paginator && method_exists($paginator, 'links') && $paginator->lastPage() > 1)
    <div {{ $attributes->merge(['class' => $containerClass]) }} id="{{ $id }}">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 pt-2">
            <!-- Left: Results info -->
            @if($showInfo)
                <div class="d-flex align-items-center gap-2">
                    <span class="badge badge-light-primary fs-7 fw-semibold py-2 px-3">
                        <i class="ki-duotone ki-chart-simple fs-6 me-1"></i>
                        {{ $paginator->total() }} {{ __('total') }}
                    </span>
                    <span class="text-muted fs-7">
                        {{ __('Showing') }} 
                        <span class="fw-bold text-gray-800">{{ $paginator->firstItem() ?? 0 }}</span> 
                        - 
                        <span class="fw-bold text-gray-800">{{ $paginator->lastItem() ?? 0 }}</span>
                    </span>
                </div>
            @endif

            <!-- Right: Pagination controls -->
            <div class="d-flex align-items-center gap-2">
                {{-- Previous --}}
                @if($onPageChange)
                    <button type="button" 
                        class="btn btn-sm btn-icon btn-light prev-page {{ $paginator->onFirstPage() ? 'disabled opacity-50' : '' }}"
                        data-page="{{ $paginator->currentPage() - 1 }}"
                        data-per-page="{{ $paginator->perPage() }}"
                        {{ $paginator->onFirstPage() ? 'disabled' : '' }}>
                        <i class="ki-duotone ki-left fs-3"></i>
                    </button>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" 
                        class="btn btn-sm btn-icon btn-light {{ $paginator->onFirstPage() ? 'disabled opacity-50' : '' }}"
                        style="pointer-events: {{ $paginator->onFirstPage() ? 'none' : 'auto' }};">
                        <i class="ki-duotone ki-left fs-3"></i>
                    </a>
                @endif

                {{-- Page Numbers --}}
                <div class="d-flex gap-1">
                    @php
                        $current = $paginator->currentPage();
                        $last = $paginator->lastPage();
                        $showing = $compact ? 1 : 2;
                        
                        $from = max(1, $current - $showing);
                        $to = min($last, $current + $showing);
                    @endphp

                    @if ($from > 1)
                        @if($onPageChange)
                            <button type="button" class="btn btn-sm btn-light page-btn" data-page="1" data-per-page="{{ $paginator->perPage() }}">1</button>
                        @else
                            <a href="{{ $paginator->url(1) }}" class="btn btn-sm btn-light">1</a>
                        @endif
                        @if ($from > 2)
                            <span class="btn btn-sm btn-light disabled opacity-50">...</span>
                        @endif
                    @endif

                    @for ($i = $from; $i <= $to; $i++)
                        @if ($i == $current)
                            <span class="btn btn-sm btn-primary">{{ $i }}</span>
                        @else
                            @if($onPageChange)
                                <button type="button" class="btn btn-sm btn-light page-btn" data-page="{{ $i }}" data-per-page="{{ $paginator->perPage() }}">{{ $i }}</button>
                            @else
                                <a href="{{ $paginator->url($i) }}" class="btn btn-sm btn-light">{{ $i }}</a>
                            @endif
                        @endif
                    @endfor

                    @if ($to < $last)
                        @if ($to < $last - 1)
                            <span class="btn btn-sm btn-light disabled opacity-50">...</span>
                        @endif
                        @if($onPageChange)
                            <button type="button" class="btn btn-sm btn-light page-btn" data-page="{{ $last }}" data-per-page="{{ $paginator->perPage() }}">{{ $last }}</button>
                        @else
                            <a href="{{ $paginator->url($last) }}" class="btn btn-sm btn-light">{{ $last }}</a>
                        @endif
                    @endif
                </div>

                {{-- Next --}}
                @if($onPageChange)
                    <button type="button" 
                        class="btn btn-sm btn-icon btn-light next-page {{ !$paginator->hasMorePages() ? 'disabled opacity-50' : '' }}"
                        data-page="{{ $paginator->currentPage() + 1 }}"
                        data-per-page="{{ $paginator->perPage() }}"
                        {{ !$paginator->hasMorePages() ? 'disabled' : '' }}>
                        <i class="ki-duotone ki-right fs-3"></i>
                    </button>
                @else
                    <a href="{{ $paginator->nextPageUrl() }}" 
                        class="btn btn-sm btn-icon btn-light {{ !$paginator->hasMorePages() ? 'disabled opacity-50' : '' }}"
                        style="pointer-events: {{ !$paginator->hasMorePages() ? 'none' : 'auto' }};">
                        <i class="ki-duotone ki-right fs-3"></i>
                    </a>
                @endif

                {{-- Per Page Selector --}}
                @if($showPerPage)
                    <div class="d-flex align-items-center gap-2 ms-2">
                        <span class="text-muted fs-7">{{ __('Per page') }}:</span>
                        <select class="form-select form-select-sm w-auto per-page-select" 
                                data-current-url="{{ url()->current() }}"
                                data-search-input-id="{{ $searchInputId }}"
                                data-route="{{ $route }}"
                                data-mode="{{ $onPageChange ? 'ajax' : 'standard' }}"
                                style="width: auto;">
                            @foreach($perPageOptions as $option)
                                <option value="{{ $option }}" {{ $paginator->perPage() == $option ? 'selected' : '' }}>
                                    {{ $option }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle per-page change for standard (non-AJAX) mode
        const perPageSelectors = document.querySelectorAll('.per-page-select');
        
        perPageSelectors.forEach(selector => {
            selector.addEventListener('change', function(e) {
                const perPage = this.value;
                const mode = this.dataset.mode;
                const currentUrl = this.dataset.currentUrl;
                const route = this.dataset.route;
                const searchInputId = this.dataset.searchInputId;
                
                if (mode === 'ajax') {
                    // AJAX mode - use the callback
                    if (typeof window.handlePerPageChange === 'function') {
                        window.handlePerPageChange(perPage);
                    } else {
                        // Generic AJAX reload
                        const searchTerm = searchInputId ? 
                            (document.getElementById(searchInputId)?.value || '') : '';
                        
                        const url = new URL(route || currentUrl, window.location.origin);
                        if (searchTerm) url.searchParams.set('search', searchTerm);
                        url.searchParams.set('per_page', perPage);
                        url.searchParams.set('page', 1);
                        
                        // Get the component container
                        const componentId = '{{ $attributes->get('id') ?? 'reloadEmployeeComponent' }}';
                        const container = document.getElementById(componentId.replace('Pagination', 'Component'));
                        
                        if (container) {
                            fetch(url.toString(), {
                                headers: { 'X-Requested-With': 'XMLHttpRequest' }
                            })
                            .then(res => res.text())
                            .then(html => {
                                container.innerHTML = html;
                                // Reinitialize any needed functionality
                                if (typeof window.initializeComponentScripts === 'function') {
                                    window.initializeComponentScripts();
                                }
                            });
                        }
                    }
                } else {
                    // Standard mode - reload page with new per_page parameter
                    const url = new URL(currentUrl, window.location.origin);
                    url.searchParams.set('per_page', perPage);
                    url.searchParams.set('page', 1); // Reset to first page
                    window.location.href = url.toString();
                }
            });
        });
        
        // Handle AJAX pagination clicks if in AJAX mode
        @if($onPageChange)
            const container = document.getElementById('{{ $id }}');
            if (container) {
                container.addEventListener('click', function(e) {
                    const btn = e.target.closest('[data-page]');
                    if (btn && !btn.disabled) {
                        e.preventDefault();
                        const page = btn.getAttribute('data-page');
                        const perPage = btn.getAttribute('data-per-page') || 
                                       document.querySelector('.per-page-select')?.value || 
                                       {{ $paginator->perPage() }};
                        
                        if (page && typeof window.loadPageWithPerPage === 'function') {
                            window.loadPageWithPerPage(page, perPage);
                        } else if (typeof {{ $onPageChange }} === 'function') {
                            {{ $onPageChange }}(page, perPage);
                        }
                    }
                });
            }
        @endif
    });
    </script>
    @endpush
    
    @push('styles')
    <style>
        .pagination-container .btn-sm,
        .pagination-wrapper .btn-sm {
            min-width: 32px;
            transition: all 0.2s ease;
        }
        .pagination-container .btn-sm:hover:not(.disabled):not(.btn-primary),
        .pagination-wrapper .btn-sm:hover:not(.disabled):not(.btn-primary) {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .pagination-container .btn-primary,
        .pagination-wrapper .btn-primary {
            box-shadow: 0 2px 6px rgba(0, 123, 255, 0.25);
        }
        .pagination-container .badge-light-primary,
        .pagination-wrapper .badge-light-primary {
            background-color: rgba(0, 123, 255, 0.08);
        }
        .per-page-select {
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .per-page-select:hover {
            border-color: var(--bs-primary);
        }
    </style>
    @endpush
@endif