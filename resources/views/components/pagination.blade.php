@props([
    'paginator',
    'pageName' => 'page',
    'perPageName' => 'per_page',
    'showPerPage' => true
])

@php
    // Check if paginator is a LengthAwarePaginator instance
    $isPaginator = $paginator instanceof \Illuminate\Pagination\LengthAwarePaginator;
@endphp

@if(($isPaginator && $paginator->hasPages()) || $showPerPage)
<div class="d-flex flex-column flex-sm-row align-items-center justify-content-between gap-4 py-3">
    {{-- Per Page Selector --}}
    @if($showPerPage && $isPaginator)
    <div class="d-flex align-items-center gap-2">
        <label class="form-label mb-0 text-muted">{{ __('accounting.show') }}:</label>
        <select class="form-select form-select-sm w-auto" 
                onchange="window.changePerPage('{{ $pageName }}', '{{ $perPageName }}', this.value)">
            @foreach([10, 15, 25, 50, 100] as $option)
                <option value="{{ $option }}" {{ ($paginator->perPage() ?? 15) == $option ? 'selected' : '' }}>
                    {{ $option }}
                </option>
            @endforeach
        </select>
        <span class="text-muted">{{ __('accounting.entries') }}</span>
    </div>
    @elseif($showPerPage && !$isPaginator)
    <div class="d-flex align-items-center gap-2">
        <label class="form-label mb-0 text-muted">{{ __('accounting.show') }}:</label>
        <select class="form-select form-select-sm w-auto" 
                onchange="window.changePerPage('{{ $pageName }}', '{{ $perPageName }}', this.value)">
            @foreach([10, 15, 25, 50, 100] as $option)
                <option value="{{ $option }}" {{ (request()->get($perPageName, 15) == $option) ? 'selected' : '' }}>
                    {{ $option }}
                </option>
            @endforeach
        </select>
        <span class="text-muted">{{ __('accounting.entries') }}</span>
    </div>
    @endif
    
    {{-- Pagination Links --}}
    @if($isPaginator)
    <div class="d-flex align-items-center gap-3 flex-wrap">
        {{-- Page Info --}}
        @if($paginator->count() > 0)
        <div class="text-muted fs-7">
            {{ __('accounting.showing') }}
            <strong>{{ $paginator->firstItem() }}</strong>
            {{ __('accounting.to') }}
            <strong>{{ $paginator->lastItem() }}</strong>
            {{ __('accounting.of') }}
            <strong>{{ $paginator->total() }}</strong>
            {{ __('accounting.results') }}
        </div>
        @endif
        
        {{ $paginator->appends(request()->except($pageName, $perPageName))->links() }}
    </div>
    @else
    <div class="text-muted fs-7">
        {{ __('accounting.showing') }}
        <strong>{{ $paginator->count() }}</strong>
        {{ __('accounting.results') }}
    </div>
    @endif
</div>

@if($isPaginator)
<script>
// Global function for changing per page
if (typeof window.changePerPage !== 'function') {
    window.changePerPage = function(pageName, perPageName, perPage) {
        const url = new URL(window.location.href);
        url.searchParams.set(perPageName, perPage);
        url.searchParams.set(pageName, '1');
        url.hash = '';
        window.location.href = url.toString();
    };
}
</script>
@endif
@endif