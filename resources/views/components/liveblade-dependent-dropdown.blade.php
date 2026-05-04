@props([
    'parentName' => 'location_id',
    'childName' => 'department_id',
    'parentLabel' => 'Location',
    'childLabel' => 'Department',
    'parentOptions' => [],
    'route' => null,
    'id' => null,
])

@php
    $uniqueId = $id ?? 'dd_' . uniqid();
    $parentId = $uniqueId . '_parent';
    $childId = $uniqueId . '_child';
    $loadingId = $uniqueId . '_loading';
@endphp

<div class="row g-9 mb-8">
    <!-- Parent Select -->
    <div class="col-md-6 fv-row">
        <label class="fs-6 fw-semibold mb-2 required">
            {{ __($parentLabel) }}
        </label>
        <select name="{{ $parentName }}" 
                id="{{ $parentId }}" 
                class="form-select form-select-solid lb-dep-parent"
                data-lb-child="{{ $childId }}"
                data-lb-route="{{ $route }}">
            <option value="">Select {{ __($parentLabel) }}</option>
            @foreach ($parentOptions as $option)
                <option value="{{ $option->id }}">{{ $option->name }}</option>
            @endforeach
        </select>
    </div>

    <!-- Child Select with Loading Spinner -->
    <div class="col-md-6 fv-row">
        <label class="fs-6 fw-semibold mb-2 required">
            {{ __($childLabel) }}
        </label>
        <div class="position-relative">
            <select name="{{ $childName }}" 
                    id="{{ $childId }}" 
                    class="form-select form-select-solid"
                    data-lb-dep-child="true"
                    disabled>
                <option value="">Select {{ __($childLabel) }} first</option>
            </select>
            <div id="{{ $loadingId }}" class="position-absolute end-0 top-0 me-3 mt-2" style="display: none;">
                <span class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </span>
            </div>
        </div>
    </div>
</div>

<style>
    /* Dependent Dropdown Styles */
    .lb-dep-parent {
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .lb-dep-parent:hover {
        border-color: var(--bs-primary, #0d6efd);
    }
    
    .lb-dep-child:disabled {
        background-color: #f5f8fa;
        cursor: not-allowed;
        opacity: 0.7;
    }
    
    .position-relative {
        position: relative;
    }
    
    /* Loading spinner animation */
    @keyframes lb-spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    .spinner-border-sm {
        animation: lb-spin 0.7s linear infinite;
    }
    
    /* Select2 overrides for dependent dropdowns */
    .select2-container--disabled .select2-selection {
        background-color: #f5f8fa;
        cursor: not-allowed;
        opacity: 0.7;
    }
</style>

{{--
 <!-- <x-liveblade-dependent-dropdown 
    id="category_subcategory"
    parentName="category_id"
    childName="subcategory_id"
    parentLabel="Category"
    childLabel="Subcategory"
    :parentOptions="$categories"
    route="{{ route('api.dependent.options', ['child_model' => 'subcategory', 'parent_field' => 'category_id']) }}"
    componentId="reloadProductComponent"
/> 



<x-liveblade-dependent-dropdown 
    id="country_city"
    parentName="country_id"
    childName="city_id"
    parentLabel="Country"
    childLabel="City"
    :parentOptions="$countries"
    route="{{ route('api.dependent.options', ['child_model' => 'city', 'parent_field' => 'country_id']) }}"
    componentId="reloadAddressComponent"
/> --}}