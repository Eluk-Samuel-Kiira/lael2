<x-app-layout>
    @section('title', __('pagination.product_variant'))
    @section('content')
    
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-4 gap-md-0">
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-md-1 flex-column my-0">
                    {{__('pagination.variant_create')}} {{ $product->name }}
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ url()->previous() }}" class="text-muted text-hover-primary">
                            {{ __('auth._back') }}
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">{{__('pagination.product_variant_new')}}</li>
                </ul>
            </div>
        </div>
    </div>
    
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <form id="addVariantsForm" method="POST" action="{{ route('variants.store')}}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}" required>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle" id="variantsTable">
                        <thead class="bg-light">
                            <tr>
                                <th class="fw-bold">{{ __('pagination._thumbnail') }}</th>
                                <th class="fw-bold">{{ __('pagination._variant_name') }}</th>
                                <th class="fw-bold">{{ __('pagination._sku') }}</th>
                                <th class="fw-bold">{{ __('pagination._barcode') }}</th>
                                <th class="fw-bold">{{ __('pagination._price') }} {{ currency_code() }}</th>
                                <th class="fw-bold">{{ __('pagination.cost_price') }} {{ currency_code() }}</th>
                                <th class="fw-bold">{{ __('pagination._weight') }}</th>
                                <th class="fw-bold">{{ __('pagination.weight_unit') }}</th>
                                <th class="fw-bold" style="width: 40px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(old('variants'))
                                @foreach(old('variants') as $index => $variant)
                                    <tr>
                                        <td>
                                            <input type="file" name="variants[{{ $index }}][image]" class="form-control" accept="image/*">
                                            @error("variants.$index.image")
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </td>
                                        <td>
                                            <input type="text" name="variants[{{ $index }}][name]" class="form-control" value="{{ $variant['name'] ?? '' }}" required>
                                            @error("variants.$index.name")
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </td>
                                        <td>
                                            <input type="text" name="variants[{{ $index }}][sku]" class="form-control" value="{{ $variant['sku'] ?? '' }}">
                                            @error("variants.$index.sku")
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </td>
                                        <td>
                                            <input type="text" name="variants[{{ $index }}][barcode]" class="form-control" value="{{ $variant['barcode'] ?? '' }}">
                                            @error("variants.$index.barcode")
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </td>
                                        <td>
                                            <input type="number" name="variants[{{ $index }}][price]" class="form-control" step="0.01" value="{{ $variant['price'] ?? '' }}" required>
                                            @error("variants.$index.price")
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </td>
                                        <td>
                                            <input type="number" name="variants[{{ $index }}][cost_price]" class="form-control" step="0.01" value="{{ $variant['cost_price'] ?? '' }}" required>
                                            @error("variants.$index.cost_price")
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </td>
                                        <td>
                                            <input type="number" name="variants[{{ $index }}][weight]" class="form-control" min="0" step="0.01" value="{{ $variant['weight'] ?? '' }}" required>
                                            @error("variants.$index.weight")
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </td>
                                        <td>
                                            <x-typable-select 
                                                name="variants[{{ $index }}][weight_unit]"
                                                :options="$uoms"
                                                selected="{{ $variant['weight_unit'] ?? '' }}"
                                                placeholder="Type or select weight unit..."
                                            />
                                            @error("variants.$index.weight_unit")
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-icon btn-danger removeVariantBtn" {{ count(old('variants')) === 1 ? 'disabled' : '' }}>
                                                <i class="ki-duotone ki-trash fs-2"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                {{-- Default first row --}}
                                <tr id="variant_row_0">
                                    <td>
                                        <input type="file" name="variants[0][image]" class="form-control" accept="image/*" required>
                                    </td>
                                    <td>
                                        <input type="text" name="variants[0][name]" class="form-control" required>
                                    </td>
                                    <td>
                                        <input type="text" name="variants[0][sku]" class="form-control">
                                    </td>
                                    <td>
                                        <input type="text" name="variants[0][barcode]" class="form-control">
                                    </td>
                                    <td>
                                        <input type="number" name="variants[0][price]" class="form-control" step="0.01" required>
                                    </td>
                                    <td>
                                        <input type="number" name="variants[0][cost_price]" class="form-control" step="0.01" required>
                                    </td>
                                    <td>
                                        <input type="number" name="variants[0][weight]" class="form-control" min="0" step="0.01" required>
                                    </td>
                                    <td>
                                        <x-typable-select 
                                            name="variants[0][weight_unit]"
                                            :options="$uoms"
                                            selected=""
                                            placeholder="Type or select weight unit..."
                                        />
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-icon btn-danger removeVariantBtn" disabled>
                                            <i class="ki-duotone ki-trash fs-2"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                <div class="d-flex flex-column flex-sm-row gap-3 mt-5">
                    <button type="button" id="addVariantBtn" class="btn btn-primary flex-grow-1 flex-sm-grow-0">
                        <i class="ki-duotone ki-plus fs-2 me-2"></i>
                        <span class="d-none d-sm-inline">{{ __('pagination._add_variant') }}</span>
                        <span class="d-inline d-sm-none">{{ __('auth._add') }}</span>
                    </button>

                   <button type="submit" class="btn btn-success flex-grow-1 flex-sm-grow-0">
                        <i class="ki-duotone ki-check-circle fs-2 me-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        <span class="d-none d-sm-inline">{{ __('auth.submit') }}</span>
                        <span class="d-inline d-sm-none">{{ __('auth.save') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function updateRowIndices() {
            const rows = document.querySelectorAll('#variantsTable tbody tr');
            rows.forEach((row, newIndex) => {
                // Update all input names with the new index
                row.querySelectorAll('input, select').forEach(input => {
                    if (input.name) {
                        input.name = input.name.replace(/variants\[\d+\]/g, `variants[${newIndex}]`);
                    }
                });
                // Update the row ID
                row.id = `variant_row_${newIndex}`;
            });
        }

        document.getElementById('addVariantBtn').addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const tableBody = document.querySelector('#variantsTable tbody');
            if (!tableBody) return;
            
            const currentIndex = tableBody.rows.length;
            const newIndex = currentIndex;
            
            // Get the first row as template
            const firstRow = tableBody.rows[0];
            if (!firstRow) return;
            
            // Build UOM options correctly
            let uomOptions = '';
            
            // Try to get options from existing datalist
            const existingSelect = firstRow.querySelector('select, .position-relative');
            if (existingSelect) {
                const existingDatalist = existingSelect.querySelector('datalist');
                if (existingDatalist) {
                    const options = existingDatalist.querySelectorAll('option');
                    options.forEach(opt => {
                        if (opt.value && opt.value !== 'Select weight unit' && opt.value !== 'None' && opt.value !== '') {
                            uomOptions += `<option value="${opt.value}" data-id="${opt.getAttribute('data-id') || ''}"></option>`;
                        }
                    });
                }
            }
            
            // If no options found, use Blade data
            if (!uomOptions) {
                @foreach($uoms as $umo)
                    uomOptions += `<option value="{{ $umo->name }}" data-id="{{ $umo->id }}"></option>`;
                @endforeach
            }
            
            const newRow = document.createElement('tr');
            newRow.id = `variant_row_${newIndex}`;
            newRow.innerHTML = `
                <td>
                    <input type="file" name="variants[${newIndex}][image]" class="form-control" accept="image/*">
                </td>
                <td>
                    <input type="text" name="variants[${newIndex}][name]" class="form-control" required>
                </td>
                <td>
                    <input type="text" name="variants[${newIndex}][sku]" class="form-control">
                </td>
                <td>
                    <input type="text" name="variants[${newIndex}][barcode]" class="form-control">
                </td>
                <td>
                    <input type="number" name="variants[${newIndex}][price]" class="form-control" step="0.01" required>
                </td>
                <td>
                    <input type="number" name="variants[${newIndex}][cost_price]" class="form-control" step="0.01" required>
                </td>
                <td>
                    <input type="number" name="variants[${newIndex}][weight]" class="form-control" min="0" step="0.01" required>
                </td>
                <td>
                    <div class="position-relative">
                        <input type="text" 
                            id="weight_unit_input_${newIndex}"
                            class="form-control typable-select-input"
                            list="weight_unit_list_${newIndex}"
                            placeholder="Type or select weight unit..."
                            autocomplete="off"
                            data-typable-input="true"
                            data-hidden-id="weight_unit_hidden_${newIndex}"
                            data-list-id="weight_unit_list_${newIndex}">
                        <input type="hidden" 
                            name="variants[${newIndex}][weight_unit]" 
                            id="weight_unit_hidden_${newIndex}"
                            class="typable-select-hidden"
                            value="">
                        <datalist id="weight_unit_list_${newIndex}">
                            <option value="">Select weight unit</option>
                            ${uomOptions}
                        </datalist>
                    </div>
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-icon btn-danger removeVariantBtn" onclick="removeRow(this)">
                        <i class="ki-duotone ki-trash fs-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                            <span class="path3"></span>
                            <span class="path4"></span>
                            <span class="path5"></span>
                        </i>
                    </button>
                </td>
            `;
            
            tableBody.appendChild(newRow);
            
            // Re-initialize typable selects for the new row
            if (typeof window.LiveBladeRefresh === 'function') {
                window.LiveBladeRefresh();
            }
        });
        
        function removeRow(btn) {
            const row = btn.closest('tr');
            if (row) {
                row.remove();
                updateRowIndices();
                
                // Disable remove button if only one row left
                const rows = document.querySelectorAll('#variantsTable tbody tr');
                rows.forEach(r => {
                    const removeBtn = r.querySelector('.removeVariantBtn');
                    if (removeBtn) {
                        removeBtn.disabled = rows.length === 1;
                    }
                });
            }
        }
        
        // Initial setup - disable remove button if only one row
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('#variantsTable tbody tr');
            rows.forEach(r => {
                const removeBtn = r.querySelector('.removeVariantBtn');
                if (removeBtn) {
                    removeBtn.disabled = rows.length === 1;
                }
            });
        });
    </script>
       
    @endsection
</x-app-layout>