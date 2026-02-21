
<!-- Delete Modal -->
<div class="modal fade" id="deleteTenantModal{{$tenant->id}}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('auth.confirm_deletion') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger d-flex align-items-center p-5 mb-5">
                    <i class="ki-duotone ki-information-5 fs-2hx text-danger me-4"></i>
                    <div class="d-flex flex-column">
                        <h4 class="mb-1 text-danger">{{ __('payments.warning') }}</h4>
                        <span>{{ __('payments.delete_warning_message') }}</span>
                    </div>
                </div>
                
                <p class="fw-bold mb-2">{{ __('payments.about_to_delete') }}</p>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <i class="bi bi-building fs-5 text-muted me-2"></i>
                        <span class="fw-bold">{{ $tenant->name }}</span>
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-globe fs-5 text-muted me-2"></i>
                        <span>{{ $tenant->subdomain }}</span>
                    </li>
                    <li>
                        <i class="bi bi-calendar fs-5 text-muted me-2"></i>
                        <span>{{ __('payments.created') }}: {{ $tenant->created_at->format('d M Y') }}</span>
                    </li>
                </ul>
                
                <p class="text-muted mt-3">{{ __('auth.are_you_sure') }}</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">{{ __('auth._discard') }}</button>
                <button type="button" class="btn btn-danger" 
                    data-item-url="{{ route('tenant.destroy', $tenant->id) }}" 
                    data-item-id="{{ $tenant->id }}"
                    onclick="deleteItem(this)">
                    <span class="indicator-label">{{ __('auth._confirm') }}</span>
                    <span class="indicator-progress" style="display: none;">
                        {{__('auth.please_wait') }}
                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>