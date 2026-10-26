<div class="modal fade" id="updateProductLoc{{ $product->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content">

            <div class="modal-header bg-primary py-4">
                <h2 class="modal-title text-white fw-bold mb-0">
                    <i class="ki-duotone ki-geolocation fs-2 text-white me-2">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                    {{ __('pagination._dept_loc') }} — {{ $product->name }}
                </h2>
                <button type="button" class="btn-close btn-close-white"
                        data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body px-7 py-6" style="max-height: 70vh; overflow-y: auto;">
                <form id="allocateProductForm{{ $product->id }}"
                      action="{{ route('assign.product', $product) }}"
                      method="POST"
                      data-allocate-form
                      data-product-id="{{ $product->id }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="product_id" value="{{ $product->id }}">

                    {{-- TAXES --}}
                    <div class="mb-5" id="product-assign-tax-{{ $product->id }}">
                        <h6 class="fw-bold mb-3">{{ __('pagination.assign_tax') }}</h6>
                        <div class="row g-3" style="{{ $product->is_taxable == 1 ? '' : 'display:none;' }}">
                            @foreach($taxes as $tax)
                                <div class="col-md-6">
                                    <div class="form-check form-check-custom form-check-solid">
                                        <input type="checkbox"
                                               class="form-check-input"
                                               name="taxes[]"
                                               value="{{ $tax->id }}"
                                               id="tax_{{ $product->id }}_{{ $tax->id }}"
                                               {{ $product->taxes->contains($tax->id) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="tax_{{ $product->id }}_{{ $tax->id }}">
                                            {{ ucwords(str_replace('_', ' ', $tax->name)) }} — {{ $tax->code }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="alert alert-warning py-2" style="{{ $product->is_taxable == 1 ? 'display:none;' : '' }}">
                            {{ __('pagination.not_taxable') }}
                        </div>
                    </div>

                    {{-- PROMOTIONS --}}
                    <div class="mb-5">
                        <h6 class="fw-bold mb-3">{{ __('pagination.assign_promotions') }}</h6>
                        <div class="row g-3">
                            @foreach($promotions as $promotion)
                                <div class="col-md-6">
                                    <div class="form-check form-check-custom form-check-solid">
                                        <input type="checkbox"
                                               class="form-check-input"
                                               name="promotions[]"
                                               value="{{ $promotion->id }}"
                                               id="promo_{{ $product->id }}_{{ $promotion->id }}"
                                               {{ $product->promotions->contains($promotion->id) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="promo_{{ $product->id }}_{{ $promotion->id }}">
                                            {{ ucwords(str_replace('_', ' ', $promotion->name)) }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- LOCATIONS & DEPARTMENTS --}}
                    <div class="mb-5">
                        <h6 class="fw-bold mb-3">{{ __('pagination.allocate_locations_departments') }}</h6>

                        @unless(tenant_is_single_shop(auth()->user()->tenant_id))
                            @php
                                $selectedDepartments = $product->departments->pluck('id')->toArray();
                                $selectedLocations   = $product->locations->pluck('id')->toArray();
                            @endphp

                            @foreach($locations as $location)
                                @php
                                    $locationDepartments = $departments->where('location_id', $location->id);
                                    $someDeptsSelected   = $locationDepartments->count() > 0 &&
                                        $locationDepartments->some(fn($d) => in_array($d->id, $selectedDepartments));
                                    $isLocationSelected  = in_array($location->id, $selectedLocations) || $someDeptsSelected;
                                @endphp

                                <div class="card border rounded mb-3"
                                     data-location-group="{{ $location->id }}">
                                    <div class="card-header bg-light p-3">
                                        <div class="d-flex align-items-center">
                                            <div class="form-check form-check-custom form-check-solid me-3">
                                                <input type="checkbox"
                                                       class="form-check-input location-checkbox"
                                                       name="locations[]"
                                                       value="{{ $location->id }}"
                                                       id="location_{{ $product->id }}_{{ $location->id }}"
                                                       data-location="{{ $location->id }}"
                                                       {{ $isLocationSelected ? 'checked' : '' }}>
                                                <label class="form-check-label fw-bold"
                                                       for="location_{{ $product->id }}_{{ $location->id }}">
                                                    <i class="ki-duotone ki-building fs-4 me-2">
                                                        <span class="path1"></span><span class="path2"></span>
                                                    </i>
                                                    {{ $location->name }}
                                                </label>
                                            </div>
                                            <button class="btn btn-sm btn-icon btn-light-primary ms-auto"
                                                    type="button"
                                                    data-bs-toggle="collapse"
                                                    data-bs-target="#locationDepts_{{ $product->id }}_{{ $location->id }}">
                                                <i class="ki-duotone ki-down fs-4">
                                                    <span class="path1"></span><span class="path2"></span>
                                                </i>
                                            </button>
                                        </div>
                                    </div>

                                    <div id="locationDepts_{{ $product->id }}_{{ $location->id }}"
                                         class="collapse {{ $someDeptsSelected ? 'show' : '' }}">
                                        <div class="card-body">
                                            <div class="row g-3">
                                                @foreach($locationDepartments as $department)
                                                    <div class="col-md-6">
                                                        <div class="form-check form-check-custom form-check-solid">
                                                            <input type="checkbox"
                                                                   class="form-check-input department-checkbox"
                                                                   data-location="{{ $location->id }}"
                                                                   name="departments[]"
                                                                   value="{{ $department->id }}"
                                                                   id="dept_{{ $product->id }}_{{ $department->id }}"
                                                                   {{ in_array($department->id, $selectedDepartments) ? 'checked' : '' }}>
                                                            <label class="form-check-label"
                                                                   for="dept_{{ $product->id }}_{{ $department->id }}">
                                                                {{ ucwords(str_replace('_', ' ', $department->name)) }}
                                                            </label>
                                                        </div>
                                                    </div>
                                                @endforeach
                                                @if($locationDepartments->isEmpty())
                                                    <div class="col-12">
                                                        <div class="text-muted text-center py-3">
                                                            {{ __('pagination.no_departments_in_location') }}
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="alert alert-info mb-4">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>{{ __('auth.single_shop_plan') }}:</strong>
                                {{ __('auth.upgrade_for_multiple_shops') }}
                                <a href="/" class="btn btn-sm btn-outline-primary ms-2">
                                    {{ __('auth.upgrade_plan') }}
                                </a>
                            </div>
                        @endunless
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-4">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                            {{ __('auth._discard') }}
                        </button>
                        <button type="button" class="btn btn-primary" data-allocate-submit style="min-width: 140px;">
                            <span class="indicator-label">
                                <i class="bi bi-check2 me-1"></i>{{ __('auth._update') }}
                            </span>
                            <span class="indicator-progress d-none">
                                {{ __('auth.please_wait') }}
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>