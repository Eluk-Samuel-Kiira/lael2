<script>
    // ============================================================
// DEPARTMENT FILTER — the ONLY handler for this element. Do not
// add a second one anywhere else in the page; two listeners on
// the same <select> both calling window.location.href = ... is
// what caused the previous "first selection sometimes does
// nothing" bug — whichever listener's setup ran last could wipe
// out the other's binding via node cloning, mid-race, right as
// the user's first click tried to use it.
// ============================================================
(function() {
    function init() {
        const departmentFilter = document.getElementById('departmentFilter');
        if (!departmentFilter) {
            setTimeout(init, 100);
            return;
        }

        departmentFilter.addEventListener('change', function() {
            const selectedDept = this.value;
            const url = new URL(window.location.href);

            if (selectedDept) {
                url.searchParams.set('department', selectedDept);
            } else {
                url.searchParams.delete('department');
            }

            window.location.href = url.toString();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>