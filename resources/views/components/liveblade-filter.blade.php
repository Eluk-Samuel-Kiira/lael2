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
    @foreach($filters as $index => $filter)
        @php
            $filterName = $filter['name'];
            $filterLabel = $filter['label'];
            $filterOptions = $filter['options'] ?? [];
            $valueKey = $filter['value_key'] ?? 'id';
            $labelKey = $filter['label_key'] ?? 'name';
            $currentValue = request($filterName, $filter['default'] ?? '');
            $dependsOn = $filter['depends_on'] ?? null;
            $parentIdField = $filter['parent_id_field'] ?? 'location_id';
            $placeholder = $filter['placeholder'] ?? "Type or select {$filterLabel}...";
            $searchable = $filter['searchable'] ?? true;
        @endphp
        
        <div class="w-100 w-sm-auto filter-item" style="min-width: 180px;" data-filter-name="{{ $filterName }}" data-depends-on="{{ $dependsOn }}">
            <!-- <label class="form-label fs-8 fw-semibold mb-1">{{ $filterLabel }}</label> -->
            <div class="position-relative">
                @if($searchable)
                    <!-- Searchable Input with Datalist -->
                    <input type="text"
                           id="{{ $filterId }}_{{ $filterName }}_input"
                           class="form-control form-control-solid form-control-sm lb-filter-input"
                           list="{{ $filterId }}_{{ $filterName }}_list"
                           placeholder="{{ $placeholder }}"
                           value="{{ $currentValue ? (is_object($filterOptions->firstWhere($valueKey, $currentValue)) ? $filterOptions->firstWhere($valueKey, $currentValue)->$labelKey : '') : '' }}"
                           autocomplete="off"
                           data-lb-filter="{{ $filterName }}"
                           data-lb-component="{{ $componentId }}"
                           data-lb-url="{{ $route ?? url()->current() }}"
                           data-lb-depends-on="{{ $dependsOn }}"
                           data-lb-parent-id-field="{{ $parentIdField }}">
                    <input type="hidden"
                           name="{{ $filterName }}"
                           id="{{ $filterId }}_{{ $filterName }}"
                           class="lb-filter-hidden"
                           data-lb-filter="{{ $filterName }}"
                           value="{{ $currentValue }}">
                    <datalist id="{{ $filterId }}_{{ $filterName }}_list">
                        <option value="">{{ $filterLabel }} - {{ $defaultText }}</option>
                        @foreach($filterOptions as $option)
                            @php
                                $optionValue = is_object($option) ? $option->$valueKey : ($option[$valueKey] ?? $option);
                                $optionLabel = is_object($option) ? $option->$labelKey : ($option[$labelKey] ?? $option);
                                $parentId = is_object($option) ? ($option->$parentIdField ?? '') : ($option[$parentIdField] ?? '');
                            @endphp
                            <option value="{{ $optionLabel }}" data-value="{{ $optionValue }}" data-parent-id="{{ $parentId }}"></option>
                        @endforeach
                    </datalist>
                @else
                    <!-- Regular Select Dropdown -->
                    <select 
                        id="{{ $filterId }}_{{ $filterName }}"
                        class="form-select form-select-sm lb-filter-select"
                        data-lb-filter="{{ $filterName }}"
                        data-lb-component="{{ $componentId }}"
                        data-lb-url="{{ $route ?? url()->current() }}"
                        data-lb-depends-on="{{ $dependsOn }}"
                        data-lb-parent-id-field="{{ $parentIdField }}"
                        style="background-position: right 0.5rem center; padding-right: 2rem;">
                        <option value="">{{ $filterLabel }} - {{ $defaultText }}</option>
                        @foreach($filterOptions as $option)
                            @php
                                $optionValue = is_object($option) ? $option->$valueKey : ($option[$valueKey] ?? $option);
                                $optionLabel = is_object($option) ? $option->$labelKey : ($option[$labelKey] ?? $option);
                                $parentId = is_object($option) ? ($option->$parentIdField ?? '') : ($option[$parentIdField] ?? '');
                            @endphp
                            <option value="{{ $optionValue }}" 
                                    data-parent-id="{{ $parentId }}"
                                    {{ $currentValue == $optionValue ? 'selected' : '' }}>
                                {{ $optionLabel }}
                            </option>
                        @endforeach
                    </select>
                @endif
                <div class="position-absolute end-0 top-0 me-2 mt-2" style="display: none;">
                    <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                </div>
            </div>
        </div>
    @endforeach
</div>

<style>
    /* Filter Input Styles */
    .lb-filter-input {
        cursor: text;
        transition: all 0.2s ease;
        background-color: #ffffff !important;
        border: 1px solid #e2e8f0 !important;
        font-size: 0.875rem;
        padding: 0.5rem 0.75rem;
    }
    
    .lb-filter-input:focus {
        border-color: var(--bs-primary, #0d6efd) !important;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25) !important;
        background-color: #ffffff !important;
    }
    
    /* Datalist dropdown styling */
    datalist {
        background-color: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        max-height: 200px;
        overflow-y: auto;
    }
    
    datalist option {
        background-color: #ffffff;
        color: #1e293b;
        padding: 8px 12px;
        cursor: pointer;
    }
    
    datalist option:hover {
        background-color: #f1f5f9;
    }
    
    ::-webkit-calendar-picker-indicator {
        background-color: #f8fafc;
        padding: 4px;
        border-radius: 4px;
        cursor: pointer;
    }
    
    ::-webkit-calendar-picker-indicator:hover {
        background-color: #e2e8f0;
    }
    
    .filter-item label {
        font-size: 0.75rem;
        font-weight: 600;
        margin-bottom: 0.25rem;
        color: #5f6368;
    }
</style>


{{--
Example 1: No Dependency (Just filters the table)
<x-liveblade-filter 
    componentId="reloadItemComponent"
    route="{{ route('items.index') }}"
    :filters="[
        [
            'name' => 'status', 
            'label' => 'Status', 
            'options' => ['active' => 'Active', 'inactive' => 'Inactive']
        ],
        [
            'name' => 'category_id', 
            'label' => 'Category', 
            'options' => $categories
        ]
    ]"
/>






Example 2: With Dependency (Filters both table and next filter)

<x-liveblade-filter 
    componentId="reloadItemComponent"
    route="{{ route('items.index') }}"
    :filters="[
        [
            'name' => 'location_id', 
            'label' => __('pagination._location'), 
            'options' => $locations
        ],
        [
            'name' => 'department_id', 
            'label' => __('auth._department'), 
            'options' => $departments,
            'depends_on' => 'location_id',  // ← Only update department filter when location changes
            'parent_id_field' => 'location_id'  // ← The field in department that links to location
        ]
    ]"
/>
--}}