<!-- Recipe Ingredients Modal -->
<div class="modal fade" id="recipeIngredientsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">
                    <i class="bi bi-journal-bookmark-fill me-2 text-warning"></i>
                    {{ __('pagination.recipe_ingredients') }}
                    <span id="recipeProductName" class="fs-6 text-muted ms-2"></span>
                </h2>
                <button type="button" class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"></i>
                </button>
            </div>
            <div class="modal-body px-5 my-7">
                <form id="recipeIngredientsForm" class="form">
                    @csrf
                    <input type="hidden" name="recipe_id" id="recipeId" value="">
                    <input type="hidden" name="variant_id" id="recipeVariantId" value="">
                    
                    <div class="mb-5">
                        <div class="alert alert-info d-flex align-items-center">
                            <i class="bi bi-info-circle fs-2 me-3"></i>
                            <div>
                                <span class="fw-bold">{{ __('pagination.recipe_ingredients_info') }}</span>
                                <br>
                                <small>{{ __('pagination.add_ingredients_to_define_recipe') }}</small>
                            </div>
                        </div>
                    </div>

                    <!-- Ingredient Rows -->
                    <div id="ingredientRows">
                        <!-- Rows will be added dynamically -->
                    </div>

                    <!-- Add Ingredient Button -->
                    <div class="mt-4">
                        <button type="button" class="btn btn-light-primary" id="addIngredientBtn">
                            <i class="bi bi-plus-circle me-1"></i>
                            {{ __('pagination.add_ingredient') }}
                        </button>
                    </div>

                    <div class="mt-6">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">
                            {{ __('auth._discard') }}
                        </button>
                        <button type="button" id="saveRecipeIngredientsBtn" class="btn btn-primary">
                            <span class="indicator-label">{{ __('auth.save') }}</span>
                            <span class="indicator-progress" style="display: none;">
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

<!-- Recipe Ingredient Row Template -->
<template id="ingredientRowTemplate">
    <div class="ingredient-row card card-dashed mb-3">
        <div class="card-body py-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label fw-semibold">{{ __('pagination.ingredient_variant') }}</label>
                    <select name="ingredient_variant_id[]" class="form-select ingredient-variant-select" data-control="select2" data-placeholder="{{ __('pagination.select_ingredient') }}" required>
                        <option value="">{{ __('pagination.select_ingredient') }}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">{{ __('pagination.quantity_required') }}</label>
                    <input type="number" name="quantity_required[]" class="form-control quantity-required" step="0.0001" min="0.0001" placeholder="1.0" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">{{ __('pagination.weight_unit') }}</label>
                    <select name="unit_id[]" class="form-select unit-select" data-control="select2" data-placeholder="{{ __('pagination.select_unit') }}">
                        <option value="">{{ __('pagination.select_unit') }}</option>
                        @foreach($uoms ?? [] as $uom)
                            <option value="{{ $uom->id }}">{{ $uom->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-sm btn-icon btn-danger remove-ingredient-btn" title="{{ __('pagination.remove_ingredient') }}">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>


<script>
    let availableVariants = [];
    let currentIngredients = [];

    /**
     * Open the recipe ingredients modal
     */
    function openRecipeIngredientsModal(variantId, variantName) {
        // Set the variant name in the modal header
        document.getElementById('recipeProductName').textContent = '- ' + variantName;
        document.getElementById('recipeVariantId').value = variantId;
        
        // Clear existing rows
        document.getElementById('ingredientRows').innerHTML = '';
        
        // Show loading state
        document.getElementById('ingredientRows').innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3 text-muted">{{ __('pagination.loading_ingredients') }}</p>
            </div>
        `;

        // Fetch recipe ingredients
        fetch(`/recipe/ingredients/${variantId}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                availableVariants = data.available_variants || [];
                currentIngredients = data.ingredients || [];
                document.getElementById('recipeId').value = data.recipe_id || '';
                
                // Update header with product and variant info
                if (data.product_name) {
                    document.getElementById('recipeProductName').textContent = 
                        '- ' + data.product_name + ' (' + data.variant_name + ')';
                }
                
                renderIngredientRows(currentIngredients);
            } else {
                document.getElementById('ingredientRows').innerHTML = `
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        ${data.message || '{{ __('pagination.no_recipe_found') }}'}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading recipe ingredients:', error);
            document.getElementById('ingredientRows').innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle me-2"></i>
                    {{ __('pagination.error_loading_ingredients') }}
                    <br><small class="text-muted">${error.message}</small>
                </div>
            `;
        });
    }

    /**
     * Render ingredient rows
     */
    function renderIngredientRows(ingredients) {
        const container = document.getElementById('ingredientRows');
        container.innerHTML = '';
        
        if (ingredients.length === 0) {
            // Add an empty row if no ingredients
            addIngredientRow();
            return;
        }

        ingredients.forEach((ingredient, index) => {
            addIngredientRow(ingredient, index);
        });
    }

    /**
     * Add an ingredient row
     */
    function addIngredientRow(existingData = null, index = null) {
        const container = document.getElementById('ingredientRows');
        const template = document.getElementById('ingredientRowTemplate');
        const clone = template.content.cloneNode(true);
        
        const row = clone.querySelector('.ingredient-row');
        
        // Set the index for data attributes
        const rowIndex = index !== null ? index : container.children.length;
        row.dataset.index = rowIndex;

        // Set existing data if provided
        if (existingData) {
            const variantSelect = row.querySelector('.ingredient-variant-select');
            const quantityInput = row.querySelector('.quantity-required');
            const unitSelect = row.querySelector('.unit-select');

            // Populate variant select
            variantSelect.innerHTML = '<option value="">{{ __("pagination.select_ingredient") }}</option>';
            availableVariants.forEach(variant => {
                const option = document.createElement('option');
                option.value = variant.id;
                option.textContent = `${variant.name} (${variant.sku})`;
                if (variant.id === existingData.ingredient_variant_id) {
                    option.selected = true;
                }
                variantSelect.appendChild(option);
            });

            if (quantityInput) {
                quantityInput.value = existingData.quantity_required || 1;
            }

            if (unitSelect) {
                // Unit select is already populated from the blade template
                const options = unitSelect.options;
                for (let i = 0; i < options.length; i++) {
                    if (options[i].value == existingData.unit_id) {
                        options[i].selected = true;
                    }
                }
            }
        } else {
            // Populate variant select with available variants
            const variantSelect = row.querySelector('.ingredient-variant-select');
            variantSelect.innerHTML = '<option value="">{{ __("pagination.select_ingredient") }}</option>';
            availableVariants.forEach(variant => {
                const option = document.createElement('option');
                option.value = variant.id;
                option.textContent = `${variant.name} (${variant.sku})`;
                variantSelect.appendChild(option);
            });
        }

        // Add remove functionality
        const removeBtn = row.querySelector('.remove-ingredient-btn');
        if (removeBtn) {
            removeBtn.addEventListener('click', function() {
                const rowToRemove = this.closest('.ingredient-row');
                if (rowToRemove) {
                    const ingredientId = rowToRemove.dataset.ingredientId;
                    if (ingredientId) {
                        // If it's an existing ingredient, delete it from the server
                        deleteIngredient(ingredientId, rowToRemove);
                    } else {
                        rowToRemove.remove();
                    }
                }
            });
        }

        // If it's an existing ingredient, store the ID
        if (existingData && existingData.id) {
            row.dataset.ingredientId = existingData.id;
        }

        container.appendChild(row);
        
        // Reinitialize Select2 for the new select elements
        if (typeof $ !== 'undefined' && $.fn.select2) {
            $(row).find('.ingredient-variant-select, .unit-select').select2({
                dropdownParent: $('#recipeIngredientsModal')
            });
        }
    }

    /**
     * Delete an ingredient
     */
    function deleteIngredient(ingredientId, rowElement) {
        if (!confirm('{{ __("pagination.confirm_delete_ingredient") }}')) {
            return;
        }

        fetch(`/recipe/ingredient/${ingredientId}`, {
            method: 'DELETE',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                rowElement.remove();
                toastr.success(data.message || '{{ __("pagination.ingredient_removed") }}');
            } else {
                toastr.error(data.message || '{{ __("pagination.error_removing_ingredient") }}');
            }
        })
        .catch(error => {
            console.error('Error deleting ingredient:', error);
            toastr.error('{{ __("pagination.error_removing_ingredient") }}');
        });
    }

    /**
     * Save recipe ingredients
     */
    document.getElementById('saveRecipeIngredientsBtn')?.addEventListener('click', function() {
        const form = document.getElementById('recipeIngredientsForm');
        const submitBtn = this;
        const indicatorLabel = submitBtn.querySelector('.indicator-label');
        const indicatorProgress = submitBtn.querySelector('.indicator-progress');

        // Show loading
        submitBtn.disabled = true;
        indicatorLabel.style.display = 'none';
        indicatorProgress.style.display = 'inline';

        // Gather form data
        const formData = new FormData(form);
        const recipeId = document.getElementById('recipeId').value;
        const rows = document.querySelectorAll('.ingredient-row');
        
        // Remove any previous error messages
        document.querySelectorAll('.ingredient-row .is-invalid').forEach(el => el.classList.remove('is-invalid'));
        document.querySelectorAll('.ingredient-row .invalid-feedback').forEach(el => el.remove());

        let hasErrors = false;

        // Validate rows
        rows.forEach((row) => {
            const variantSelect = row.querySelector('.ingredient-variant-select');
            const quantityInput = row.querySelector('.quantity-required');

            if (!variantSelect.value) {
                variantSelect.classList.add('is-invalid');
                const feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                feedback.textContent = '{{ __("pagination.select_ingredient_required") }}';
                variantSelect.parentNode.appendChild(feedback);
                hasErrors = true;
            }

            if (!quantityInput.value || parseFloat(quantityInput.value) <= 0) {
                quantityInput.classList.add('is-invalid');
                const feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                feedback.textContent = '{{ __("pagination.quantity_required_positive") }}';
                quantityInput.parentNode.appendChild(feedback);
                hasErrors = true;
            }
        });

        if (hasErrors) {
            submitBtn.disabled = false;
            indicatorLabel.style.display = 'inline';
            indicatorProgress.style.display = 'none';
            return;
        }

        // Build ingredients array
        const ingredients = [];
        rows.forEach((row) => {
            const variantSelect = row.querySelector('.ingredient-variant-select');
            const quantityInput = row.querySelector('.quantity-required');
            const unitSelect = row.querySelector('.unit-select');

            if (variantSelect.value && quantityInput.value) {
                ingredients.push({
                    ingredient_variant_id: variantSelect.value,
                    quantity_required: parseFloat(quantityInput.value),
                    unit_id: unitSelect.value || null,
                });
            }
        });

        // Prepare data for submission
        const data = {
            recipe_id: recipeId,
            ingredients: ingredients
        };

        // Submit via AJAX
        fetch('/recipe/ingredients/store', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            submitBtn.disabled = false;
            indicatorLabel.style.display = 'inline';
            indicatorProgress.style.display = 'none';

            if (data.success) {
                toastr.success(data.message || '{{ __("pagination.recipe_ingredients_saved") }}');
                
                // Close modal after short delay
                setTimeout(() => {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('recipeIngredientsModal'));
                    if (modal) {
                        modal.hide();
                    }
                }, 1500);
            } else {
                toastr.error(data.message || '{{ __("pagination.error_saving_ingredients") }}');
            }
        })
        .catch(error => {
            console.error('Error saving recipe ingredients:', error);
            submitBtn.disabled = false;
            indicatorLabel.style.display = 'inline';
            indicatorProgress.style.display = 'none';
            toastr.error('{{ __("pagination.error_saving_ingredients") }}');
        });
    });

    // Add ingredient row button
    document.getElementById('addIngredientBtn')?.addEventListener('click', function() {
        addIngredientRow();
    });

    // Initialize when modal is opened
    document.getElementById('recipeIngredientsModal')?.addEventListener('shown.bs.modal', function() {
        // Initialize Select2 for any existing selects
        if (typeof $ !== 'undefined' && $.fn.select2) {
            $(this).find('.ingredient-variant-select, .unit-select').select2({
                dropdownParent: $(this)
            });
        }
    });

    // Reset form when modal is closed
    document.getElementById('recipeIngredientsModal')?.addEventListener('hidden.bs.modal', function() {
        document.getElementById('ingredientRows').innerHTML = '';
        document.getElementById('recipeId').value = '';
        document.getElementById('recipeVariantId').value = '';
    });
</script>