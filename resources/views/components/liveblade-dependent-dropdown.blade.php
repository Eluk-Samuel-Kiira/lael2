@props([
    'parentName' => 'location_id',
    'childName' => 'department_id',
    'parentLabel' => 'Location',
    'childLabel' => 'Department',
    'parentOptions' => [],
    'childOptions' => [],  // Add this for pre-loaded departments
    'route' => null,
    'id' => null,
    'selectedParent' => null,
    'selectedChild' => null,
    'skipAjax' => false,
])

@php
    $uniqueId = $id ?? 'dd_' . uniqid();
    $parentId = $uniqueId . '_parent';
    $childId = $uniqueId . '_child';
    $loadingId = $uniqueId . '_loading';
    $parentListId = $uniqueId . '_parent_list';
    $childListId = $uniqueId . '_child_list';
    
    // Find selected parent name
    $selectedParentName = '';
    if ($selectedParent && $parentOptions) {
        $parent = $parentOptions->firstWhere('id', $selectedParent);
        $selectedParentName = $parent ? $parent->name : '';
    }
    
    // For create mode - no selected values
    $selectedChildName = '';
    if ($selectedChild && $childOptions) {
        $department = $childOptions->firstWhere('id', $selectedChild);
        $selectedChildName = $department ? $department->name : '';
    }
    
    // Build child datalist options
    $childDatalistOptions = '';
    if ($childOptions && $childOptions->count() > 0) {
        foreach ($childOptions as $option) {
            $childDatalistOptions .= '<option value="' . e($option->name) . '" data-id="' . $option->id . '" data-location="' . ($option->location_id ?? '') . '"></option>';
        }
    }
@endphp

<div class="row g-9 mb-8" data-lb-dependent-container="{{ $uniqueId }}">
    <!-- Parent Select -->
    <div class="col-md-6 fv-row">
        <label class="fs-6 fw-semibold mb-2 required">{{ __($parentLabel) }}</label>
        <div class="position-relative">
            <input type="text" 
                   name="{{ $parentName }}_text"
                   id="{{ $parentId }}_input"
                   class="form-control form-control-solid lb-dep-parent-input"
                   list="{{ $parentListId }}"
                   placeholder="Type or select {{ __($parentLabel) }}..."
                   autocomplete="off"
                   value="{{ $selectedParentName }}">
            <input type="hidden" 
                   name="{{ $parentName }}" 
                   id="{{ $parentId }}" 
                   class="lb-dep-parent"
                   data-lb-child="{{ $childId }}"
                   data-lb-route="{{ $route }}"
                   data-lb-loading="{{ $loadingId }}"
                   data-skip-ajax="{{ $skipAjax ? 'true' : 'false' }}"
                   value="{{ $selectedParent }}">
            <datalist id="{{ $parentListId }}">
                @foreach ($parentOptions as $option)
                    <option value="{{ $option->name }}" data-id="{{ $option->id }}"></option>
                @endforeach
            </datalist>
        </div>
    </div>

    <!-- Child Select -->
    <div class="col-md-6 fv-row">
        <label class="fs-6 fw-semibold mb-2 required">{{ __($childLabel) }}</label>
        <div class="position-relative">
            <input type="text" 
                   name="{{ $childName }}_text"
                   id="{{ $childId }}_input"
                   class="form-control form-control-solid lb-dep-child-input"
                   list="{{ $childListId }}"
                   placeholder="Type or select {{ __($childLabel) }}..."
                   autocomplete="off"
                   value="{{ $selectedChildName }}">
            <input type="hidden" 
                   name="{{ $childName }}" 
                   id="{{ $childId }}" 
                   class="lb-dep-child"
                   value="{{ $selectedChild }}">
            <datalist id="{{ $childListId }}">
                <option value="">Select {{ __($childLabel) }}</option>
                {!! $childDatalistOptions !!}
            </datalist>
            <div id="{{ $loadingId }}" class="position-absolute end-0 top-0 me-3 mt-2" style="display: none;">
                <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
            </div>
        </div>
    </div>
</div>