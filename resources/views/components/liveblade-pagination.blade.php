@props([
    'paginator'      => null,
    'id'             => null,
    'route'          => null,
    'searchInputId'  => null,
    'showInfo'       => true,
    'showPerPage'    => false,
    'compact'        => false,
    'containerClass' => 'px-4 pb-4',
    'perPageOptions' => [15, 25, 50, 100],
])

{{--
    Pure HTML — zero inline JS, zero @push('scripts'), zero @push('styles').
    Mirrors x-liveblade-search exactly.

    All behaviour driven by data-lb-* attributes, read by
    initializeComponentScripts() in liveblade-imports.blade.php
    which fires after every DOM swap (SPA nav + component reload).

    Naming convention (JS auto-derives which component to reload):
        Pagination id  =  reload{X}Pagination
        Component  id  =  reload{X}Component

    Usage:
        <x-liveblade-pagination
            :paginator="$all_employees"
            id="reloadEmployeePagination"
            route="{{ route('employee.index') }}"
            search-input-id="employeeSearchInput"
            :show-info="true"
            :show-per-page="true"
        />
--}}

@if($paginator && method_exists($paginator, 'links') && $paginator->lastPage() > 1)
@php
    $current = $paginator->currentPage();
    $last    = $paginator->lastPage();
    $radius  = $compact ? 1 : 2;
    $from    = max(1, $current - $radius);
    $to      = min($last, $current + $radius);
    $baseUrl = $route ?? url()->current();
@endphp

<div id="{{ $id }}"
     class="{{ $containerClass }}"
     data-lb-pagination="true"
     data-lb-url="{{ $baseUrl }}"
     data-lb-search-input="{{ $searchInputId ?? '' }}"
     data-lb-per-page="{{ $paginator->perPage() }}"
     data-lb-component="{{ $attributes->get('data-lb-component', '') }}">

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 pt-2">

        {{-- Left: info --}}
        @if($showInfo)
        <div class="d-flex align-items-center gap-2">
            <span class="badge badge-light-primary fs-7 fw-semibold py-2 px-3">
                {{ $paginator->total() }} {{ __('total') }}
            </span>
            <span class="text-muted fs-7">
                {{ __('Showing') }}
                <span class="fw-bold text-gray-800">{{ $paginator->firstItem() ?? 0 }}</span>
                –
                <span class="fw-bold text-gray-800">{{ $paginator->lastItem() ?? 0 }}</span>
            </span>
        </div>
        @endif

        {{-- Right: controls --}}
        <div class="d-flex align-items-center gap-1">

            {{-- Prev --}}
            <button type="button"
                    class="btn btn-sm btn-icon btn-light lb-page-btn"
                    data-page="{{ $current - 1 }}"
                    @if($paginator->onFirstPage()) disabled @endif>
                <i class="ki-duotone ki-left fs-3"></i>
            </button>

            {{-- First page + ellipsis --}}
            @if($from > 1)
                <button type="button" class="btn btn-sm btn-light lb-page-btn" data-page="1">1</button>
                @if($from > 2)
                    <span class="btn btn-sm btn-light disabled opacity-50">…</span>
                @endif
            @endif

            {{-- Page window --}}
            @for($i = $from; $i <= $to; $i++)
                @if($i === $current)
                    <span class="btn btn-sm btn-primary">{{ $i }}</span>
                @else
                    <button type="button" class="btn btn-sm btn-light lb-page-btn" data-page="{{ $i }}">{{ $i }}</button>
                @endif
            @endfor

            {{-- Ellipsis + last page --}}
            @if($to < $last)
                @if($to < $last - 1)
                    <span class="btn btn-sm btn-light disabled opacity-50">…</span>
                @endif
                <button type="button" class="btn btn-sm btn-light lb-page-btn" data-page="{{ $last }}">{{ $last }}</button>
            @endif

            {{-- Next --}}
            <button type="button"
                    class="btn btn-sm btn-icon btn-light lb-page-btn"
                    data-page="{{ $current + 1 }}"
                    @if(!$paginator->hasMorePages()) disabled @endif>
                <i class="ki-duotone ki-right fs-3"></i>
            </button>

            {{-- Per-page --}}
            @if($showPerPage)
            <div class="d-flex align-items-center gap-2 ms-3">
                <span class="text-muted fs-7">{{ __('Per page') }}:</span>
                <select class="form-select form-select-sm lb-per-page-select" style="width:auto;">
                    @foreach($perPageOptions as $opt)
                        <option value="{{ $opt }}" @selected($paginator->perPage() == $opt)>{{ $opt }}</option>
                    @endforeach
                </select>
            </div>
            @endif

        </div>
    </div>
</div>

<style>
    [data-lb-pagination] .btn-sm { min-width:32px; transition:all .15s; }
    [data-lb-pagination] .lb-page-btn:hover:not(:disabled) { transform:translateY(-1px); box-shadow:0 2px 4px rgba(0,0,0,.08); }
    [data-lb-pagination] .btn-primary { box-shadow:0 2px 6px rgba(var(--bs-primary-rgb),.3); }
    [data-lb-pagination] .lb-per-page-select { cursor:pointer; }
    [data-lb-pagination] .lb-per-page-select:hover { border-color:var(--bs-primary); }
</style>
@endif