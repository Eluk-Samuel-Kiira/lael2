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

                <div id="variantsContainer">
                    @if(old('variants'))
                        @foreach(old('variants') as $index => $variant)
                        <div class="card card-flush border border-gray-300 border-dashed mb-6 variant-card" id="variant_row_{{ $index }}">
                            <div class="card-header min-h-50px bg-light-primary px-6">
                                <h4 class="card-title fw-bold text-gray-800 variant-title">
                                    <i class="ki-duotone ki-abstract-26 fs-3 text-primary me-2"><span class="path1"></span><span class="path2"></span></i>
                                    {{ __('pagination._variant') }} #{{ $index + 1 }}
                                </h4>
                                <div class="card-toolbar">
                                    <button type="button" class="btn btn-sm btn-icon btn-light-danger removeVariantBtn" {{ count(old('variants')) === 1 ? 'disabled' : '' }}>
                                        <i class="ki-duotone ki-trash fs-3">
                                            <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span>
                                        </i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body pt-6">

                                {{-- Image + Preview --}}
                                <div class="row g-4 mb-4">
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">{{ __('pagination._thumbnail') }}</label>
                                        <div class="d-flex align-items-center gap-4">
                                            <div class="symbol symbol-90px symbol-fixed">
                                                <img src="data:image/svg+xml;charset=UTF-8,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2290%22 height=%2290%22%3E%3Crect width=%2290%22 height=%2290%22 fill=%22%23f1f1f2%22/%3E%3Cline x1=%2220%22 y1=%2220%22 x2=%2270%22 y2=%2270%22 stroke=%22%23c4c4c4%22 stroke-width=%223%22/%3E%3Cline x1=%2270%22 y1=%2220%22 x2=%2220%22 y2=%2270%22 stroke=%22%23c4c4c4%22 stroke-width=%223%22/%3E%3C/svg%3E"
                                                     class="w-100 h-100 object-fit-cover rounded border border-gray-300 variant-image-preview"
                                                     alt="preview">
                                            </div>
                                            <div class="flex-grow-1">
                                                <input type="file" name="variants[{{ $index }}][image]" class="form-control variant-image-input" accept="image/*">
                                                <div class="form-text">{{ __('pagination.image_upload_hint') ?? 'PNG or JPG, up to 2MB' }}</div>
                                                @error("variants.$index.image")
                                                    <small class="text-danger d-block mt-1">{{ $message }}</small>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Name + SKU --}}
                                <div class="row g-4 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold required">{{ __('pagination._variant_name') }}</label>
                                        <input type="text" name="variants[{{ $index }}][name]" class="form-control" value="{{ $variant['name'] ?? '' }}" required>
                                        @error("variants.$index.name")
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">{{ __('pagination._sku') }}</label>
                                        <input type="text" name="variants[{{ $index }}][sku]" class="form-control" value="{{ $variant['sku'] ?? '' }}">
                                        @error("variants.$index.sku")
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Barcode + Price --}}
                                <div class="row g-4 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">{{ __('pagination._barcode') }}</label>
                                        <input type="text" name="variants[{{ $index }}][barcode]" class="form-control" value="{{ $variant['barcode'] ?? '' }}">
                                        @error("variants.$index.barcode")
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold required">{{ __('pagination._price') }} ({{ currency_code() }})</label>
                                        <input type="number" name="variants[{{ $index }}][price]" class="form-control" step="0.01" value="{{ $variant['price'] ?? '' }}" required>
                                        @error("variants.$index.price")
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Cost Price + Weight --}}
                                <div class="row g-4 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold required">{{ __('pagination.cost_price') }} ({{ currency_code() }})</label>
                                        <input type="number" name="variants[{{ $index }}][cost_price]" class="form-control" step="0.01" value="{{ $variant['cost_price'] ?? '' }}" required>
                                        @error("variants.$index.cost_price")
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold required">{{ __('pagination._weight') }}</label>
                                        <input type="number" name="variants[{{ $index }}][weight]" class="form-control" min="0" step="0.01" value="{{ $variant['weight'] ?? '' }}" required>
                                        @error("variants.$index.weight")
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Weight Unit --}}
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">{{ __('pagination.weight_unit') }}</label>
                                        <x-typable-select
                                            name="variants[{{ $index }}][weight_unit]"
                                            :options="$uoms"
                                            selected="{{ $variant['weight_unit'] ?? '' }}"
                                            placeholder="Type or select weight unit..."
                                        />
                                        @error("variants.$index.weight_unit")
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>

                            </div>
                        </div>
                        @endforeach
                    @else
                        {{-- Default first variant card --}}
                        <div class="card card-flush border border-gray-300 border-dashed mb-6 variant-card" id="variant_row_0">
                            <div class="card-header min-h-50px bg-light-primary px-6">
                                <h4 class="card-title fw-bold text-gray-800 variant-title">
                                    <i class="ki-duotone ki-abstract-26 fs-3 text-primary me-2"><span class="path1"></span><span class="path2"></span></i>
                                    {{ __('passwords._variant') }} #1
                                </h4>
                                <div class="card-toolbar">
                                    <button type="button" class="btn btn-sm btn-icon btn-light-danger removeVariantBtn" disabled>
                                        <i class="ki-duotone ki-trash fs-3">
                                            <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span>
                                        </i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body pt-6">

                                {{-- Image + Preview --}}
                                <div class="row g-4 mb-4">
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">{{ __('pagination._thumbnail') }}</label>
                                        <div class="d-flex align-items-center gap-4">
                                            <div class="symbol symbol-90px symbol-fixed">
                                                <img src="data:image/svg+xml;charset=UTF-8,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2290%22 height=%2290%22%3E%3Crect width=%2290%22 height=%2290%22 fill=%22%23f1f1f2%22/%3E%3Cline x1=%2220%22 y1=%2220%22 x2=%2270%22 y2=%2270%22 stroke=%22%23c4c4c4%22 stroke-width=%223%22/%3E%3Cline x1=%2270%22 y1=%2220%22 x2=%2220%22 y2=%2270%22 stroke=%22%23c4c4c4%22 stroke-width=%223%22/%3E%3C/svg%3E"
                                                     class="w-100 h-100 object-fit-cover rounded border border-gray-300 variant-image-preview"
                                                     alt="preview">
                                            </div>
                                            <div class="flex-grow-1">
                                                <input type="file" name="variants[0][image]" class="form-control variant-image-input" accept="image/*">
                                                <div class="form-text">{{ __('pagination.image_upload_hint') ?? 'PNG or JPG, up to 2MB' }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Name + SKU --}}
                                <div class="row g-4 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold required">{{ __('pagination._variant_name') }}</label>
                                        <input type="text" name="variants[0][name]" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">{{ __('pagination._sku') }}</label>
                                        <input type="text" name="variants[0][sku]" class="form-control">
                                    </div>
                                </div>

                                {{-- Barcode + Price --}}
                                <div class="row g-4 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">{{ __('pagination._barcode') }}</label>
                                        <input type="text" name="variants[0][barcode]" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold required">{{ __('pagination._price') }} ({{ currency_code() }})</label>
                                        <input type="number" name="variants[0][price]" class="form-control" step="0.01" required>
                                    </div>
                                </div>

                                {{-- Cost Price + Weight --}}
                                <div class="row g-4 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold required">{{ __('pagination.cost_price') }} ({{ currency_code() }})</label>
                                        <input type="number" name="variants[0][cost_price]" class="form-control" step="0.01" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold required">{{ __('pagination._weight') }}</label>
                                        <input type="number" name="variants[0][weight]" class="form-control" min="0" step="0.01" required>
                                    </div>
                                </div>

                                {{-- Weight Unit --}}
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">{{ __('pagination.weight_unit') }}</label>
                                        <x-typable-select
                                            name="variants[0][weight_unit]"
                                            :options="$uoms"
                                            selected=""
                                            placeholder="Type or select weight unit..."
                                        />
                                    </div>
                                </div>

                            </div>
                        </div>
                    @endif
                </div>

                <div class="d-flex flex-column flex-sm-row gap-3 mt-2">
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
        // ── Placeholder used for freshly-added rows / cleared file inputs ──
        const VARIANT_IMAGE_PLACEHOLDER = 'data:image/svg+xml;charset=UTF-8,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2290%22 height=%2290%22%3E%3Crect width=%2290%22 height=%2290%22 fill=%22%23f1f1f2%22/%3E%3Cline x1=%2220%22 y1=%2220%22 x2=%2270%22 y2=%2270%22 stroke=%22%23c4c4c4%22 stroke-width=%223%22/%3E%3Cline x1=%2270%22 y1=%2220%22 x2=%2220%22 y2=%2270%22 stroke=%22%23c4c4c4%22 stroke-width=%223%22/%3E%3C/svg%3E';

        function updateRowIndices() {
            const cards = document.querySelectorAll('#variantsContainer .variant-card');
            cards.forEach((card, newIndex) => {
                card.querySelectorAll('input, select').forEach(input => {
                    if (input.name) {
                        input.name = input.name.replace(/variants\[\d+\]/g, `variants[${newIndex}]`);
                    }
                });
                card.id = `variant_row_${newIndex}`;
                const title = card.querySelector('.variant-title');
                if (title) {
                    title.innerHTML = `<i class="ki-duotone ki-abstract-26 fs-3 text-primary me-2"><span class="path1"></span><span class="path2"></span></i>{{ __('pagination._variant') }} #${newIndex + 1}`;
                }
            });
        }

        // ── Live image preview ──────────────────────────────────────────
        document.addEventListener('change', function (e) {
            if (!e.target.classList.contains('variant-image-input')) return;

            const input   = e.target;
            const wrapper = input.closest('.d-flex');
            const preview = wrapper ? wrapper.querySelector('.variant-image-preview') : null;
            if (!preview) return;

            const file = input.files && input.files[0];
            if (!file) { preview.src = VARIANT_IMAGE_PLACEHOLDER; return; }

            const reader = new FileReader();
            reader.onload = function (evt) { preview.src = evt.target.result; };
            reader.readAsDataURL(file);
        });

        document.getElementById('addVariantBtn').addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const container = document.getElementById('variantsContainer');
            if (!container) return;

            const currentIndex = document.querySelectorAll('#variantsContainer .variant-card').length;
            const newIndex = currentIndex;

            let uomOptions = '';
            @foreach($uoms as $umo)
                uomOptions += `<option value="{{ $umo->name }}" data-id="{{ $umo->id }}"></option>`;
            @endforeach

            const newCard = document.createElement('div');
            newCard.className = 'card card-flush border border-gray-300 border-dashed mb-6 variant-card';
            newCard.id = `variant_row_${newIndex}`;
            newCard.innerHTML = `
                <div class="card-header min-h-50px bg-light-primary px-6">
                    <h4 class="card-title fw-bold text-gray-800 variant-title">
                        <i class="ki-duotone ki-abstract-26 fs-3 text-primary me-2"><span class="path1"></span><span class="path2"></span></i>
                        {{ __('pagination._variant') }} #${newIndex + 1}
                    </h4>
                    <div class="card-toolbar">
                        <button type="button" class="btn btn-sm btn-icon btn-light-danger removeVariantBtn" onclick="removeRow(this)">
                            <i class="ki-duotone ki-trash fs-3">
                                <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span>
                            </i>
                        </button>
                    </div>
                </div>
                <div class="card-body pt-6">

                    <div class="row g-4 mb-4">
                        <div class="col-12">
                            <label class="form-label fw-semibold">{{ __('pagination._thumbnail') }}</label>
                            <div class="d-flex align-items-center gap-4">
                                <div class="symbol symbol-90px symbol-fixed">
                                    <img src="${VARIANT_IMAGE_PLACEHOLDER}" class="w-100 h-100 object-fit-cover rounded border border-gray-300 variant-image-preview" alt="preview">
                                </div>
                                <div class="flex-grow-1">
                                    <input type="file" name="variants[${newIndex}][image]" class="form-control variant-image-input" accept="image/*">
                                    <div class="form-text">{{ __('pagination.image_upload_hint') ?? 'PNG or JPG, up to 2MB' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold required">{{ __('pagination._variant_name') }}</label>
                            <input type="text" name="variants[${newIndex}][name]" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('pagination._sku') }}</label>
                            <input type="text" name="variants[${newIndex}][sku]" class="form-control">
                        </div>
                    </div>

                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('pagination._barcode') }}</label>
                            <input type="text" name="variants[${newIndex}][barcode]" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold required">{{ __('pagination._price') }} ({{ currency_code() }})</label>
                            <input type="number" name="variants[${newIndex}][price]" class="form-control" step="0.01" required>
                        </div>
                    </div>

                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold required">{{ __('pagination.cost_price') }} ({{ currency_code() }})</label>
                            <input type="number" name="variants[${newIndex}][cost_price]" class="form-control" step="0.01" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold required">{{ __('pagination._weight') }}</label>
                            <input type="number" name="variants[${newIndex}][weight]" class="form-control" min="0" step="0.01" required>
                        </div>
                    </div>

                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('pagination.weight_unit') }}</label>
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
                        </div>
                    </div>

                </div>
            `;

            container.appendChild(newCard);

            if (typeof window.LiveBladeRefresh === 'function') {
                window.LiveBladeRefresh();
            }

            document.querySelectorAll('#variantsContainer .removeVariantBtn').forEach(btn => btn.disabled = false);
        });

        function removeRow(btn) {
            const card = btn.closest('.variant-card');
            if (card) {
                card.remove();
                updateRowIndices();

                const cards = document.querySelectorAll('#variantsContainer .variant-card');
                cards.forEach(c => {
                    const removeBtn = c.querySelector('.removeVariantBtn');
                    if (removeBtn) removeBtn.disabled = cards.length === 1;
                });
            }
        }

        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.removeVariantBtn');
            if (btn && !btn.disabled) removeRow(btn);
        });

        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('#variantsContainer .variant-card');
            cards.forEach(c => {
                const removeBtn = c.querySelector('.removeVariantBtn');
                if (removeBtn) removeBtn.disabled = cards.length === 1;
            });
        });
    </script>

    @endsection
</x-app-layout>