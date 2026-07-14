<div class="modal fade delete-user-modal" id="updateProductLoc{{$product->id}}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">{{ __('pagination._dept_loc') }} {{ $product->name }}</h2>
                <button type="button" class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </button>
            </div>
            <div class="modal-body scroll-y mx-lg-5 my-7">
                <form id="edit_user_form{{ $product->id }}" 
                    class="form" 
                    action="{{ route('assign.product', $product) }}" 
                    method="POST">
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="product_id" value="{{ $product->id }}">

                    <!-- TAX ASSIGNMENT -->
                    <div class="mb-5" id="product-assign-tax-{{ $product->id }}">
                        <h6 class="fw-bold mb-3">{{ __('pagination.assign_tax') }}</h6>

                        <div class="row g-3 tax-options" style="{{ $product->is_taxable == 1 ? '' : 'display:none;' }}">
                            @foreach($taxes as $tax)
                                @php
                                    // ✅ Check if this tax is already assigned to the product
                                    $isTaxAssigned = $product->taxes->contains($tax->id);
                                @endphp
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input type="checkbox" 
                                            class="form-check-input"
                                            name="taxes[]" 
                                            value="{{ $tax->id }}"
                                            id="tax_{{ $product->id }}_{{ $tax->id }}"
                                            {{ $isTaxAssigned ? 'checked' : '' }}>
                                        <label class="form-check-label" for="tax_{{ $product->id }}_{{ $tax->id }}">
                                            {{ ucwords(str_replace('_', ' ', $tax->name)) }} - {{ $tax->code }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="alert alert-warning py-2 not-taxable-msg" style="{{ $product->is_taxable == 1 ? 'display:none;' : '' }}">
                            {{ __('pagination.not_taxable') }}
                        </div>
                    </div>

                    <!-- PROMOTIONS ASSIGNMENT -->
                    <div class="mb-5">
                        <h6 class="fw-bold mb-3">{{ __('pagination.assign_promotions') }}</h6>
                        <div class="row g-3">
                            @foreach($promotions as $promotion)
                                @php
                                    // ✅ Check if this promotion is already assigned to the product
                                    $isPromoAssigned = $product->promotions->contains($promotion->id);
                                @endphp
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input type="checkbox" 
                                            class="form-check-input"
                                            name="promotions[]" 
                                            value="{{ $promotion->id }}"
                                            id="promo_{{ $product->id }}_{{ $promotion->id }}"
                                            {{ $isPromoAssigned ? 'checked' : '' }}>
                                        <label class="form-check-label" for="promo_{{ $product->id }}_{{ $promotion->id }}">
                                            {{ ucwords(str_replace('_', ' ', $promotion->name)) }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- LOCATIONS & DEPARTMENTS ASSIGNMENT -->
                    <div class="mb-5">
                        <h6 class="fw-bold mb-3">{{ __('pagination.allocate_locations_departments') }}</h6>
                        @unless(tenant_is_single_shop(auth()->user()->tenant_id))
                            @php
                                // ✅ Get currently selected departments and locations
                                $selectedDepartments = $product->departments->pluck('id')->toArray();
                                $selectedLocations = $product->locations->pluck('id')->toArray();
                            @endphp

                            @foreach($locations as $location)
                                @php
                                    $locationDepartments = $departments->where('location_id', $location->id);
                                    $isLocationSelected = in_array($location->id, $selectedLocations);
                                    $allDeptsSelected = $locationDepartments->count() > 0 && 
                                        $locationDepartments->every(fn($dept) => in_array($dept->id, $selectedDepartments));
                                    $someDeptsSelected = $locationDepartments->count() > 0 && 
                                        $locationDepartments->some(fn($dept) => in_array($dept->id, $selectedDepartments));
                                @endphp
                                
                                <div class="card border rounded mb-3">
                                    <div class="card-header bg-light p-3">
                                        <div class="d-flex align-items-center">
                                            <div class="form-check me-3">
                                                <input type="checkbox" 
                                                    class="form-check-input location-checkbox"
                                                    name="locations[]" 
                                                    value="{{ $location->id }}"
                                                    id="location_{{ $product->id }}_{{ $location->id }}"
                                                    {{ $isLocationSelected ? 'checked' : '' }}>
                                                <label class="form-check-label fw-bold" for="location_{{ $product->id }}_{{ $location->id }}">
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
                                                        <div class="form-check">
                                                            <input type="checkbox" 
                                                                class="form-check-input department-checkbox"
                                                                data-location="{{ $location->id }}"
                                                                name="departments[]" 
                                                                value="{{ $department->id }}"
                                                                id="dept_{{ $product->id }}_{{ $department->id }}"
                                                                {{ in_array($department->id, $selectedDepartments) ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="dept_{{ $product->id }}_{{ $department->id }}">
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

                    <div class="d-flex justify-content-center pt-10">
                        <button type="reset" class="btn btn-light me-3" data-bs-dismiss="modal">
                            {{ __('auth._discard') }}
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <span class="indicator-label">{{ __('auth._update') }}</span>
                            <span class="indicator-progress">
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