@props([
    'id'          => 'searchInput',
    'componentId' => 'reloadComponent',
    'route'       => null,
    'placeholder' => 'Search...',
    'debounce'    => 380,
    'minChars'    => 0,
])

{{--
    Pure HTML — zero inline JS, zero @push('scripts'), zero Blade vars in JS.
    All behaviour driven by data-lb-* attributes read by
    LiveBlade.initializeComponentScripts() which runs after every DOM swap.
--}}
<div class="w-100 w-sm-250px">
    <div class="input-group input-group-solid">
        <span class="input-group-text bg-body border-0">
            <i class="ki-duotone ki-magnifier fs-3 text-gray-500"></i>
        </span>
        <input type="text"
               id="{{ $id }}"
               name="search"
               value="{{ request('search') }}"
               class="form-control form-control-solid border-0 ps-0 lb-search-input"
               placeholder="{{ $placeholder }}"
               autocomplete="off"
               data-lb-component="{{ $componentId }}"
               data-lb-url="{{ $route ?? url()->current() }}"
               data-lb-debounce="{{ $debounce }}"
               data-lb-min-chars="{{ $minChars }}">
    </div>
</div>