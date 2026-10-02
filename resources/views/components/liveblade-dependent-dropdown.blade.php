@props([
    'parentName'   => 'location_id',
    'childName'    => 'department_id',
    'parentLabel'  => 'Location',
    'childLabel'   => 'Department',
    'parentOptions' => [],
    'childOptions'  => [],
    'route'         => null,
    'id'            => null,
    'selectedParent' => null,
    'selectedChild'  => null,
    'skipAjax'      => false,
    'required'      => false,
])

@php
    $uniqueId = $id ?? 'dd_' . uniqid();
    $parentId = $uniqueId . '_parent';
    $childId  = $uniqueId . '_child';
    $loadingId = $uniqueId . '_loading';
    $parentListId = $uniqueId . '_parent_list';
    $childListId  = $uniqueId . '_child_list';

    // Resolve display names for current selections
    $selectedParentName = '';
    if ($selectedParent && $parentOptions) {
        $parent = collect($parentOptions)->firstWhere('id', $selectedParent);
        $selectedParentName = $parent ? $parent->name : '';
    }

    $selectedChildName = '';
    if ($selectedChild && $childOptions) {
        $department = collect($childOptions)->firstWhere('id', $selectedChild);
        $selectedChildName = $department ? $department->name : '';
    }

    // Build child datalist options
    $childDatalistOptions = '';
    if ($childOptions && collect($childOptions)->count() > 0) {
        foreach ($childOptions as $option) {
            $childDatalistOptions .= '<option value="' . e($option->name)
                . '" data-id="' . $option->id
                . '" data-location="' . ($option->location_id ?? '') . '"></option>';
        }
    }
@endphp

{{-- ★ Outer row now stacks on small screens and respects narrow content areas --}}
<div class="row g-3 g-lg-4 mb-4"
     data-lb-dependent-container="{{ $uniqueId }}">

    <!-- Parent (Location) -->
    <div class="col-12 col-lg-6 fv-row">
        <label class="fs-7 fw-semibold mb-2 {{ $required ? 'required' : '' }}">
            {{ __($parentLabel) }}
        </label>
        <div class="position-relative">
            <input type="text"
                   name="{{ $parentName }}_text"
                   id="{{ $parentId }}_input"
                   class="form-control lb-dep-parent-input"
                   list="{{ $parentListId }}"
                   placeholder="{{ __('Select or type') }} {{ __($parentLabel) }}..."
                   autocomplete="off"
                   value="{{ $selectedParentName }}"
                   style="width: 100%; min-width: 0;">
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

    <!-- Child (Department) -->
    <div class="col-12 col-lg-6 fv-row">
        <label class="fs-7 fw-semibold mb-2 {{ $required ? 'required' : '' }}">
            {{ __($childLabel) }}
        </label>
        <div class="position-relative">
            <input type="text"
                   name="{{ $childName }}_text"
                   id="{{ $childId }}_input"
                   class="form-control lb-dep-child-input"
                   list="{{ $childListId }}"
                   placeholder="{{ __('Select or type') }} {{ __($childLabel) }}..."
                   autocomplete="off"
                   value="{{ $selectedChildName }}"
                   style="width: 100%; min-width: 0;">
            <input type="hidden"
                   name="{{ $childName }}"
                   id="{{ $childId }}"
                   class="lb-dep-child"
                   value="{{ $selectedChild }}">
            <datalist id="{{ $childListId }}">
                <option value="">{{ __('Select') }} {{ __($childLabel) }}</option>
                {!! $childDatalistOptions !!}
            </datalist>
            <div id="{{ $loadingId }}"
                 class="position-absolute end-0 top-0 me-3 mt-2"
                 style="display: none;">
                <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
            </div>
        </div>
    </div>
</div>

{{-- ★ Component-scoped CSS — fixes the "invisible input" issue --}}
@once
@push('styles')
<style>
    /* Solid inputs disappear against light cards when narrow; give them
       a subtle border so they're always visible. */
    .lb-dep-parent-input,
    .lb-dep-child-input {
        width: 100% !important;
        min-width: 0 !important;
        max-width: 100% !important;
        border: 1px solid #E4E6EF !important;
        background-color: #ffffff !important;
        background-clip: padding-box;
        transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
    }
    .lb-dep-parent-input:focus,
    .lb-dep-child-input:focus {
        border-color: #3E97FF !important;
        box-shadow: 0 0 0 3px rgba(62,151,255,.15) !important;
        background-color: #ffffff !important;
    }
    .lb-dep-parent-input:disabled,
    .lb-dep-child-input:disabled {
        background-color: #F5F8FA !important;
        opacity: .85;
    }
    /* Kill the huge row gutter on narrow viewports so fields don't
       collapse into 40px slivers on HP-class laptops. */
    @media (max-width: 1399.98px) {
        [data-lb-dependent-container] {
            --bs-gutter-x: 1rem;
            --bs-gutter-y: 1rem;
        }
    }
</style>
@endpush
@endonce