@props([
    'name' => null,
    'label' => null,
    'options' => [],
    'selected' => null,
    'placeholder' => 'Type or select...',
    'required' => false,
    'class' => '',
    'id' => null,
])

@php
    $selectId = $id ?? 'typable_' . md5($name . uniqid());
    $selectedValue = $selected;
    $selectedText = '';
    
    // Find the selected option text
    if ($selectedValue) {
        foreach ($options as $option) {
            $optionValue = is_object($option) ? $option->id : ($option['id'] ?? null);
            if ($optionValue == $selectedValue) {
                $selectedText = is_object($option) ? $option->name : ($option['name'] ?? '');
                break;
            }
        }
    }
@endphp

<div class="fv-row mb-8 {{ $class }}">
    @if($label)
        <label class="fs-6 fw-semibold mb-2 {{ $required ? 'required' : '' }}">
            {{ __($label) }}
        </label>
    @endif
    
    <div class="position-relative">
        <input type="text" 
               id="{{ $selectId }}_input"
               class="form-control form-control-solid typable-select-input"
               list="{{ $selectId }}_list"
               placeholder="{{ $placeholder }}"
               autocomplete="off"
               value="{{ $selectedText }}"
               data-typable-input="true"
               data-hidden-id="{{ $selectId }}_hidden"
               data-list-id="{{ $selectId }}_list">
        <input type="hidden" 
               name="{{ $name }}" 
               id="{{ $selectId }}_hidden"
               class="typable-select-hidden"
               value="{{ $selectedValue }}">
        <datalist id="{{ $selectId }}_list" data-typable-list="true">
            <option value="">{{ __('auth._none') }}</option>
            @foreach($options as $option)
                @php
                    $optionValue = is_object($option) ? $option->id : ($option['id'] ?? $option);
                    $optionLabel = is_object($option) ? $option->name : ($option['name'] ?? $option);
                @endphp
                <option value="{{ $optionLabel }}" data-id="{{ $optionValue }}"></option>
            @endforeach
        </datalist>
    </div>
    <div id="{{ $name }}_error" class="text-danger small"></div>
</div>