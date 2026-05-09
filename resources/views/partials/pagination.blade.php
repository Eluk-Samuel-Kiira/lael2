@props(['paginator', 'pageName' => 'page', 'perPageName' => 'per_page'])

@if($paginator->hasPages())
<nav class="d-flex justify-content-between align-items-center flex-wrap gap-3" aria-label="Pagination">
    {{-- Info Text --}}
    <div class="text-muted fs-7">
        {{ __('accounting.showing') }} 
        <strong class="text-gray-800">{{ $paginator->firstItem() ?? 0 }}</strong> 
        {{ __('accounting.to') }} 
        <strong class="text-gray-800">{{ $paginator->lastItem() ?? 0 }}</strong> 
        {{ __('accounting.of') }} 
        <strong class="text-gray-800">{{ $paginator->total() }}</strong> 
        {{ $paginator->total() == 1 ? __('accounting.entry') : __('accounting.entries') }}
    </div>

    {{-- Per Page Selector --}}
    @if(isset($showPerPage) && $showPerPage)
    <div class="d-flex align-items-center gap-2">
        <label class="form-label fs-7 mb-0 text-muted">{{ __('accounting.show') }}:</label>
        <select class="form-select form-select-sm form-select-solid w-auto" 
                onchange="changePerPage('{{ $pageName }}', '{{ $perPageName }}', this.value, '{{ request()->fullUrl() }}')">
            @foreach([10, 15, 25, 50, 100] as $option)
                <option value="{{ $option }}" {{ $paginator->perPage() == $option ? 'selected' : '' }}>
                    {{ $option }}
                </option>
            @endforeach
        </select>
    </div>
    @endif

    {{-- Pagination Links --}}
    <ul class="pagination mb-0">
        {{-- Previous Page --}}
        @if($paginator->onFirstPage())
            <li class="page-item disabled">
                <span class="page-link">
                    <i class="ki-duotone ki-arrow-left fs-2"></i>
                </span>
            </li>
        @else
            <li class="page-item">
                <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('accounting.previous') }}">
                    <i class="ki-duotone ki-arrow-left fs-2"></i>
                </a>
            </li>
        @endif

        {{-- Page Numbers --}}
        @foreach($paginator->getUrlRange(1, $paginator->lastPage()) as $page => $url)
            @if($page == $paginator->currentPage())
                <li class="page-item active" aria-current="page">
                    <span class="page-link">{{ $page }}</span>
                </li>
            @elseif($page >= $paginator->currentPage() - 2 && $page <= $paginator->currentPage() + 2)
                <li class="page-item">
                    <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                </li>
            @elseif($page == 1 || $page == $paginator->lastPage())
                <li class="page-item">
                    <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                </li>
            @elseif($page == $paginator->currentPage() - 3 || $page == $paginator->currentPage() + 3)
                <li class="page-item disabled">
                    <span class="page-link">...</span>
                </li>
            @endif
        @endforeach

        {{-- Next Page --}}
        @if($paginator->hasMorePages())
            <li class="page-item">
                <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('accounting.next') }}">
                    <i class="ki-duotone ki-arrow-right fs-2"></i>
                </a>
            </li>
        @else
            <li class="page-item disabled">
                <span class="page-link">
                    <i class="ki-duotone ki-arrow-right fs-2"></i>
                </span>
            </li>
        @endif
    </ul>
</nav>

@push('scripts')
<script>
function changePerPage(pageName, perPageName, perPage, baseUrl) {
    const url = new URL(baseUrl);
    url.searchParams.set(perPageName, perPage);
    url.searchParams.set(pageName, '1'); // Reset to first page
    window.location.href = url.toString();
}
</script>
@endpush
@endif