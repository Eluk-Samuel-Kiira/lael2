<section class="space-y-6">
    <div class="row mb-3">
        <label for="fullName" class="col-md-4 col-lg-3 col-form-label">{{__('auth.delete_account') }}</label>
        <div class="col-md-8 col-lg-9">
            <div class="text-center">
                <button 
                    id="deleteAccountButton" 
                    class="btn btn-danger" 
                    disabled>
                    {{__('auth.delete_account') }}
                </button>
            </div>
        </div>
    </div>

    <form id="deleteAccountForm" class="row g-3 needs-validation" novalidate>
        @csrf
        @method('delete')
        
        <div class="card">
            <div class="card-body">
                <div class="modal fade" id="deleteAccount" tabindex="-1" data-bs-backdrop="false">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">{{__('auth.delete_account') }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                {{__('auth.delete_account_message') }}
                                <div class="col-8 mt-3">
                                    <label for="yourPassword" class="form-label">{{__('auth.enter_password') }}</label>
                                    <input type="password" name="password" class="form-control" required>
                                </div>
                                <div id="password"></div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{__('auth._close') }}</button>
                                <button id="submitDeleteButton" type="submit" class="btn btn-primary">
                                    <span class="indicator-label">{{__('auth._confirm') }}</span>
                                    <span class="indicator-progress" style="display: none;">
                                        {{__('auth.please_wait') }}
                                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <div class="form-check mt-3">
        <input 
            id="confirmDeleteCheckbox" 
            class="form-check-input" 
            type="checkbox">
        <label for="confirmDeleteCheckbox" class="form-check-label">
            {{__('auth.delete_my_acc') }}
        </label>
    </div>

</section>


<script>
    document.addEventListener('DOMContentLoaded', function () {
        const confirmCheckbox = document.getElementById('confirmDeleteCheckbox');
        const deleteButton = document.getElementById('deleteAccountButton');

        // Enable/disable the button based on checkbox state
        confirmCheckbox.addEventListener('change', function () {
            deleteButton.disabled = !this.checked;
        });

        // Trigger modal on button click
        deleteButton.addEventListener('click', function () {
            if (confirmCheckbox.checked) {
                const modal = new bootstrap.Modal(document.getElementById('deleteAccount'));
                modal.show();
            }
        });
    });

</script>
