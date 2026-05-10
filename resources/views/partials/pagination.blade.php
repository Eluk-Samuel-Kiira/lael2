{{--
    Reusable Pagination Component
    ==============================
    Usage:
        @include('partials.pagination', [
            'paginator'    => $variants,       // LengthAwarePaginator or Collection
            'pageName'     => 'page',          // query param name for page  (default: 'page')
            'perPageName'  => 'per_page',      // query param name for perPage (default: 'per_page')
            'showPerPage'  => true,            // show per-page selector      (default: true)
            'perPageOptions' => [10,15,25,50,100], // options list            (default shown)
        ])
--}}

@php
    $pageName      = $pageName      ?? 'page';
    $perPageName   = $perPageName   ?? 'per_page';
    $showPerPage   = $showPerPage   ?? true;
    $perPageOptions = $perPageOptions ?? [10, 15, 25, 50, 100];
    
    // ✅ Check if it's a paginator instance
    $isPaginator = $paginator instanceof \Illuminate\Pagination\LengthAwarePaginator;
    
    // ✅ Safely get current per page value
    if ($isPaginator) {
        $currentPerPage = $paginator->perPage() ?? 15;
    } else {
        $currentPerPage = (int)request()->get($perPageName, 15);
    }
@endphp

@if((!$isPaginator) || ($isPaginator && $paginator->hasPages()) || $showPerPage)
<div class="d-flex flex-column flex-sm-row align-items-center justify-content-between gap-3 px-4 py-3 flex-wrap">

    {{-- Left: Showing X–Y of Z --}}
    <div class="text-muted fs-7">
        @if($isPaginator && method_exists($paginator, 'firstItem') && $paginator->firstItem())
            {{ __('accounting.showing') }}
            <strong>{{ $paginator->firstItem() }}</strong>–<strong>{{ $paginator->lastItem() }}</strong>
            {{ __('accounting.of') }}
            <strong>{{ $paginator->total() }}</strong>
            {{ __('accounting.results') }}
        @else
            <strong>{{ $paginator->count() }}</strong> {{ __('accounting.results') }}
        @endif
    </div>

    {{-- Center: Page links --}}
    @if($isPaginator && $paginator->hasPages())
    <nav aria-label="Page navigation">
        <ul class="pagination pagination-sm m-0">

            {{-- Previous --}}
            <li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
                <a class="page-link"
                   href="{{ $paginator->onFirstPage() ? '#' : $paginator->previousPageUrl() }}"
                   aria-label="{{ __('accounting.previous') }}">
                    <i class="ki-duotone ki-left fs-6"></i>
                </a>
            </li>

            {{-- Page numbers with smart truncation --}}
            @php
                $current   = $paginator->currentPage();
                $last      = $paginator->lastPage();
                $window    = 2; // pages either side of current
                $pages     = [];

                for ($p = 1; $p <= $last; $p++) {
                    if (
                        $p === 1 ||
                        $p === $last ||
                        ($p >= $current - $window && $p <= $current + $window)
                    ) {
                        $pages[] = $p;
                    }
                }

                // Insert ellipsis markers
                $rendered = [];
                $prev = null;
                foreach ($pages as $p) {
                    if ($prev !== null && $p - $prev > 1) {
                        $rendered[] = '...';
                    }
                    $rendered[] = $p;
                    $prev = $p;
                }
            @endphp

            @foreach($rendered as $item)
                @if($item === '...')
                    <li class="page-item disabled">
                        <span class="page-link">…</span>
                    </li>
                @else
                    <li class="page-item {{ $current === $item ? 'active' : '' }}">
                        <a class="page-link"
                           href="{{ $paginator->url($item) }}">
                            {{ $item }}
                        </a>
                    </li>
                @endif
            @endforeach

            {{-- Next --}}
            <li class="page-item {{ !$paginator->hasMorePages() ? 'disabled' : '' }}">
                <a class="page-link"
                   href="{{ $paginator->hasMorePages() ? $paginator->nextPageUrl() : '#' }}"
                   aria-label="{{ __('accounting.next') }}">
                    <i class="ki-duotone ki-right fs-6"></i>
                </a>
            </li>

        </ul>
    </nav>
    @endif

    {{-- Right: Per-page selector --}}
    @if($showPerPage)
    <div class="d-flex align-items-center gap-2">
        <label class="text-muted fs-7 mb-0">{{ __('accounting.show') }}:</label>
        <select class="form-select form-select-sm w-auto"
                onchange="paginationChangePerPage('{{ $pageName }}', '{{ $perPageName }}', this.value)">
            @foreach($perPageOptions as $opt)
                <option value="{{ $opt }}" {{ $currentPerPage == $opt ? 'selected' : '' }}>
                    {{ $opt }}
                </option>
            @endforeach
        </select>
    </div>
    @endif

</div>

{{-- Inline script (safe to include multiple times — guard prevents double-registration) --}}
<script>
    if (typeof paginationChangePerPage === 'undefined') {
        function paginationChangePerPage(pageName, perPageName, perPage) {
            const url = new URL(window.location.href);
            url.searchParams.set(perPageName, perPage);
            url.searchParams.set(pageName, '1'); // reset to first page
            window.location.href = url.toString();
        }
    }
</script>
@endif