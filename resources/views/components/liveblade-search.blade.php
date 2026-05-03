@props([
    'id' => 'searchInput',
    'componentId' => 'reloadComponent',
    'route' => null,
    'placeholder' => 'Search...',
])

<div class="w-100 w-sm-250px">
    <div class="input-group input-group-solid">
        <span class="input-group-text bg-body border-0">
            <i class="ki-duotone ki-magnifier fs-3 text-gray-500"></i>
        </span>
        <input type="text" 
               id="{{ $id }}" 
               name="search"
               value="{{ request('search') }}"
               class="form-control form-control-solid border-0 ps-0" 
               placeholder="{{ $placeholder }}"
               data-lb-component="{{ $componentId }}"
               data-lb-url="{{ $route }}">
    </div>
</div>

@push('scripts')
<script>
// One script to rule them all - works for EVERY table
document.addEventListener('DOMContentLoaded', function() {
    // Auto-submit search on typing
    const searchInput = document.getElementById('{{ $id }}');
    if (searchInput) {
        let timeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                this.closest('form')?.submit() || 
                (window.location.href = new URL(this.dataset.lbUrl, window.location.origin) + '?search=' + encodeURIComponent(this.value));
            }, 400);
        });
    }
    
    // Preserve search when clicking pagination
    document.querySelectorAll('.pagination a').forEach(link => {
        link.addEventListener('click', function(e) {
            const searchValue = document.querySelector('input[name="search"]')?.value;
            if (searchValue) {
                e.preventDefault();
                const url = new URL(this.href);
                url.searchParams.set('search', searchValue);
                window.location.href = url.toString();
            }
        });
    });
});
</script>
@endpush