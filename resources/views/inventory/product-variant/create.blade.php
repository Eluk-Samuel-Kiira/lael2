<x-app-layout>
    @section('title', __('pagination.product_variant'))
    @section('content')
    

    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">{{__('pagination.variant_create')}} {{ $product->name }}</h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        @php
                            $previousUrl = url()->previous();
                            $previousRouteName = optional(app('router')->getRoutes()->match(request()->create($previousUrl)))->getName();
                            $formattedRouteName = $previousRouteName 
                                ? Str::of($previousRouteName)->replace('.', ' ')->title() 
                                : __('auth._back');
                        @endphp
                        <a href="{{ $previousUrl }}" class="text-muted text-hover-primary">
                            {{ $formattedRouteName }}
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
                <input type="hidden" name="product_id" value="{{ $product->id }}" class="form-control" required>

                <table class="table table-bordered" id="variantsTable">
                    <thead>
                        <tr>
                            <th>{{ __('pagination._thumbnail') }}</th>
                            <th>{{ __('pagination._variant_name') }}</th>
                            <th>{{ __('pagination._sku') }}</th>
                            <th>{{ __('pagination._barcode') }}</th>
                            <th>{{ __('pagination._price') }}  {{ currency_code() }}</th>
                            <th>{{ __('pagination.cost_price') }}  {{ currency_code() }}</th>
                            <th>{{ __('pagination._weight') }}</th>
                            <th>{{ __('pagination.weight_unit') }}</th>
                            <th style="width: 40px;"></th>
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
                                        <input type="number" name="variants[{{ $index }}][weight]" class="form-control" min="0" value="{{ $variant['weight'] ?? '' }}" required>
                                        @error("variants.$index.weight")
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </td>
                                    <td>
                                        <select name="variants[{{ $index }}][weight_unit]" class="form-select" data-control="select2" data-placeholder="{{__('auth._select')}}">
                                            <option></option>
                                            @foreach ($uoms as $umo)
                                                <option value="{{ $umo->id }}" {{ (isset($variant['weight_unit']) && $variant['weight_unit']==$umo->id) ? 'selected' : '' }}>{{ $umo->name }}</option>
                                            @endforeach
                                        </select>
                                        @error("variants.$index.weight_unit")
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-danger removeVariantBtn" {{ count(old('variants')) === 1 ? 'disabled' : '' }}>&times;</button>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            {{-- Default first row --}}
                            <tr>
                                <td>
                                    <input type="file" name="variants[0][image]" class="form-control" accept="image/*" required>
                                </td>
                                <td>
                                    <input type="text" name="variants[0][name]" class="form-control" required>
                                </td>
                                <td><input type="text" name="variants[0][sku]" class="form-control"></td>
                                <td><input type="text" name="variants[0][barcode]" class="form-control"></td>
                                <td><input type="number" name="variants[0][price]" class="form-control" step="0.01" required></td>
                                <td><input type="number" name="variants[0][cost_price]" class="form-control" step="0.01" required></td>
                                <td><input type="number" name="variants[0][weight]" class="form-control" min="0" required></td>
                                <td>
                                    <select name="variants[0][weight_unit]" class="form-select" data-control="select2" data-placeholder="{{__('auth._select')}}">
                                        <option></option>
                                        @foreach ($uoms as $umo)
                                            <option value="{{ $umo->id }}">{{ $umo->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><button type="button" class="btn btn-sm btn-danger removeVariantBtn" disabled>&times;</button></td>
                            </tr>
                        @endif
                    </tbody>

                </table>


                <button type="button" id="addVariantBtn" class="btn btn-primary mt-3">
                    + {{ __('pagination._add_variant') }}
                </button>

                <button type="submit" class="btn btn-success mt-3">
                    {{ __('auth.submit') }}
                </button>
            </form>
        </div>
    </div>



    <!-- Ensure the main button has the correct type -->
    <script>
        // One more direct approach
        document.addEventListener('click', function(e) {
            if (e.target && (e.target.id === 'addVariantBtn' || e.target.closest('#addVariantBtn'))) {
                e.preventDefault();
                e.stopPropagation();
                
                const btn = document.getElementById('addVariantBtn');
                if (btn) {
                    console.log('Global click handler - button clicked');
                    
                    const tableBody = document.querySelector('#variantsTable tbody');
                    if (tableBody) {
                        const index = tableBody.rows.length;
                        const newRow = document.createElement('tr');
                        newRow.innerHTML = `
                            <td><input type="file" name="variants[${index}][image]" class="form-control" accept="image/*"></td>
                            <td><input type="text" name="variants[${index}][name]" class="form-control" required></td>
                            <td><input type="text" name="variants[${index}][sku]" class="form-control"></td>
                            <td><input type="text" name="variants[${index}][barcode]" class="form-control"></td>
                            <td><input type="number" name="variants[${index}][price]" class="form-control" step="0.01" required></td>
                            <td><input type="number" name="variants[${index}][cost_price]" class="form-control" step="0.01" required></td>
                            <td><input type="number" name="variants[${index}][weight]" class="form-control" min="0" required></td>
                            <td>
                                <select name="variants[${index}][weight_unit]" class="form-control" required>
                                    <option value="">Select unit</option>
                                    @foreach($uoms as $umo)
                                        <option value="{{ $umo->id }}">{{ $umo->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td><button type="button" class="btn btn-sm btn-danger removeVariantBtn" onclick="this.closest('tr').remove(); updateRowIndices();">×</button></td>
                        `;
                        tableBody.appendChild(newRow);
                    }
                }
            }
        });
    </script>
       
    @endsection
</x-app-layout>

