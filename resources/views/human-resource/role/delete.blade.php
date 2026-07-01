<!-- Delete Confirmation Modal -->
<div class="modal fade" id="delete_role{{ $role->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('auth.confirm_deletion') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>{{ __('auth.are_you_sure') }}</p>
                <p>{{ __('auth.action_cannot') }}</p>
            </div>
            <div class="modal-footer">
                <!-- Discard Button -->
                <button type="button" id="closeDeleteModal{{$role->id}}" class="btn btn-light me-3" data-bs-dismiss="modal">{{ __('auth._discard') }}</button>
                <!-- Confirm Button -->
                <button type="button" id="deleteButton{{$role->id}}" class="btn btn-danger" 
                    data-item-url="{{ route('role.destroy', $role->id) }}" 
                    data-item-id="{{ $role->id }}"
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

<script>
    function deleteItem(button) {
        const itemId = button.getAttribute('data-item-id');
        const deleteUrl = button.getAttribute('data-item-url');

        const deleteButton = document.getElementById('deleteButton' + itemId);
        LiveBlade.toggleButtonLoading(deleteButton, true);
        
        // Call the delete function to handle the deletion
        LiveBlade.deleteItemInLoop(deleteUrl)
            .then(noErrorStatus => {
                console.log(noErrorStatus)
                if (noErrorStatus) {
                    var closeButton = document.getElementById('closeDeleteModal' + itemId);
                    if (closeButton) {
                        closeButton.click();
                    }
                }
            })
            .catch(error => {
                console.error('An unexpected error occurred:', error);
                // Handle error gracefully
            })
            .finally(() => {
                // End loading state using reusable function
                LiveBlade.toggleButtonLoading(deleteButton, false);
            });
    }
</script>
