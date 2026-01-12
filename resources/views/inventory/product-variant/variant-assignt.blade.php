
<div class="modal fade delete-user-modal" id="variantAssign{{$product->id}}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-750px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">{{ __('pagination.variant_assign') }} {{ $product->name }}</h2>
                <button type="button" class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ki-duotone ki-cross fs-1"></i>
                </button>
            </div>
            <div class="modal-body scroll-y mx-lg-5 my-7">

                <form class="form" action="{{ route('assign.variant', $product->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="variant_id" value="{{ $product->id }}">
                    <input type="hidden" name="created_by" value="{{ auth()->id() }}">
                    <input type="hidden" name="tenant_id" value="1">

        
                    <!-- TAX ASSIGNMENT -->
                    <div class="mb-5" id="variant-assign-tax-{{ $product->id }}">
                        <h6 class="fw-bold mb-3">{{ __('pagination.assign_tax') }}</h6>

                        {{-- Tax options --}}
                        <div class="row g-3 tax-options" style="{{ $product->is_taxable == 1 ? '' : 'display:none;' }}">
                            @foreach($taxes as $tax)
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input type="checkbox" 
                                            class="form-check-input"
                                            name="taxes[]" 
                                            value="{{ $tax->id }}"
                                            {{ in_array($tax->id, old('taxes', $product->variantTaxes->pluck('id')->toArray() ?? [])) ? 'checked' : '' }}>
                                        <label class="form-check-label">
                                            {{ ucwords(str_replace('_', ' ', $tax->name)) }} - {{ $tax->code }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Not taxable message --}}
                        <div class="alert alert-warning py-2 not-taxable-msg" style="{{ $product->is_taxable == 1 ? 'display:none;' : '' }}">
                            {{ __('pagination.not_taxable') }}
                        </div>
                    </div>


                    <!-- PROMOTIONS ASSIGNMENT -->
                    <div class="mb-5">
                        <h6 class="fw-bold mb-3">{{ __('pagination.assign_promotions') }}</h6>
                        <div class="row g-3">
                            @foreach($promotions as $promotion)
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input type="checkbox" 
                                            class="form-check-input @error('promotions.*') is-invalid @enderror"
                                            name="promotions[]" 
                                            value="{{ $promotion->id }}"
                                            {{ in_array($promotion->id, old('promotions', $product->Variantpromotions->pluck('id')->toArray() ?? [])) ? 'checked' : '' }}>
                                        <label class="form-check-label">
                                            {{ ucwords(str_replace('_', ' ', $promotion->name)) }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @error('promotions.*')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- ACTION BUTTONS -->
                    <div class="d-flex justify-content-center pt-10">
                        <button type="reset" class="btn btn-light me-3" data-bs-dismiss="modal">
                            {{ __('auth._discard') }}
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <span class="indicator-label">{{ __('auth._update') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>





