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
    $parentListId = $uniqueId . '_parent_list';
    $childListId = $uniqueId . '_child_list';
@endphp

<div class="row g-9 mb-8">
    <!-- Parent Select (Typable/Filterable) -->
    <div class="col-md-6 fv-row">
        <label class="fs-6 fw-semibold mb-2 required">
            {{ __($parentLabel) }}
        </label>
        <div class="position-relative">
            <input type="text" 
                   name="{{ $parentName }}_text"
                   id="{{ $parentId }}_input"
                   class="form-control form-control-solid lb-dep-parent-input"
                   list="{{ $parentListId }}"
                   placeholder="Type or select {{ __($parentLabel) }}..."
                   autocomplete="off">
            <input type="hidden" 
                   name="{{ $parentName }}" 
                   id="{{ $parentId }}" 
                   class="lb-dep-parent"
                   data-lb-child="{{ $childId }}"
                   data-lb-route="{{ $route }}"
                   data-lb-loading="{{ $loadingId }}">
            <datalist id="{{ $parentListId }}">
                @foreach ($parentOptions as $option)
                    <option value="{{ $option->name }}" data-id="{{ $option->id }}"></option>
                @endforeach
            </datalist>
        </div>
    </div>

    <!-- Child Select (Typable/Filterable) -->
    <div class="col-md-6 fv-row">
        <label class="fs-6 fw-semibold mb-2 required">
            {{ __($childLabel) }}
        </label>
        <div class="position-relative">
            <input type="text" 
                   name="{{ $childName }}_text"
                   id="{{ $childId }}_input"
                   class="form-control form-control-solid lb-dep-child-input"
                   list="{{ $childListId }}"
                   placeholder="Type or select {{ __($childLabel) }}..."
                   autocomplete="off"
                   disabled>
            <input type="hidden" 
                   name="{{ $childName }}" 
                   id="{{ $childId }}" 
                   class="lb-dep-child"
                   disabled>
            <datalist id="{{ $childListId }}"></datalist>
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
    .lb-dep-parent-input, .lb-dep-child-input {
        cursor: text;
        transition: all 0.2s ease;
    }
    
    .lb-dep-parent-input:focus, .lb-dep-child-input:focus {
        border-color: var(--bs-primary, #0d6efd);
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
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
    
    /* Datalist styling */
    datalist {
        max-height: 200px;
        overflow-y: auto;
    }
    
    /* Disabled input styling */
    input:disabled {
        background-color: #f5f8fa;
        cursor: not-allowed;
        opacity: 0.7;
    }
</style>