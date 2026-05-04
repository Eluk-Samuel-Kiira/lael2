@props([
    'id' => null,
    'componentId' => 'reloadComponent',
    'route' => null,
    'filters' => [],
    'defaultText' => 'All',
    'class' => '',
])

@php
    $filterId = $id ?? 'filter-' . uniqid();
@endphp

<div {{ $attributes->merge(['class' => "d-flex gap-2 flex-wrap {$class}"]) }} data-lb-filter-container="{{ $componentId }}">
    @foreach($filters as $filter)
        @php
            $filterName = $filter['name'];
            $filterLabel = $filter['label'];
            $filterOptions = $filter['options'] ?? [];
            $valueKey = $filter['value_key'] ?? 'id';
            $labelKey = $filter['label_key'] ?? 'name';
            $currentValue = request($filterName, $filter['default'] ?? '');
        @endphp
        
        <div class="w-100 w-sm-auto" style="min-width: 140px;">
            <select 
                id="{{ $filterId }}_{{ $filterName }}"
                class="form-select form-select-sm lb-filter-select"
                data-lb-filter="{{ $filterName }}"
                data-lb-component="{{ $componentId }}"
                data-lb-url="{{ $route ?? url()->current() }}"
                style="background-position: right 0.5rem center; padding-right: 2rem;">
                <option value="">{{ $filterLabel }} - {{ $defaultText }}</option>
                @foreach($filterOptions as $option)
                    <option value="{{ is_object($option) ? $option->$valueKey : $option['id'] ?? $option }}" 
                            {{ $currentValue == (is_object($option) ? $option->$valueKey : ($option['id'] ?? $option)) ? 'selected' : '' }}>
                        {{ is_object($option) ? $option->$labelKey : ($option['name'] ?? $option) }}
                    </option>
                @endforeach
            </select>
        </div>
    @endforeach
</div>