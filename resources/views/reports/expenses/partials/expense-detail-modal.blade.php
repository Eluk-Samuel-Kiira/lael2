<script>
window.openExpenseDetail = function (expense) {
    const sym = (window.ExpenseSummary && window.ExpenseSummary.currency) ||
                '{{ currency_symbol() }}';

    const money = (v) => sym + ' ' + (Number(v) || 0).toLocaleString(undefined, {
        minimumFractionDigits: 2, maximumFractionDigits: 2,
    });

    document.getElementById('expDetailNumber').textContent        = '· ' + (expense.expense_number || '');
    document.getElementById('expDetailDate').textContent          = expense.date || '—';
    document.getElementById('expDetailDescription').textContent   = expense.description || '—';
    document.getElementById('expDetailVendor').textContent        = expense.vendor_name || 'N/A';
    document.getElementById('expDetailCategory').textContent      = expense.category || 'N/A';
    document.getElementById('expDetailGross').textContent         = money(expense.gross_amount);
    document.getElementById('expDetailTax').textContent           = money(expense.tax_amount);
    document.getElementById('expDetailTotal').textContent         = money(expense.amount);
    document.getElementById('expDetailPaymentMethod').textContent = expense.payment_method || '—';
    document.getElementById('expDetailEmployee').textContent      = expense.employee || '—';

    const statusColors = { pending: 'warning', paid: 'success', reimbursed: 'info' };
    const color = statusColors[expense.status] || 'secondary';
    document.getElementById('expDetailStatus').innerHTML =
        `<span class="badge badge-light-${color}">${(expense.status || '').charAt(0).toUpperCase() + (expense.status || '').slice(1)}</span>`;

    const recurringWrap = document.getElementById('expDetailRecurringWrap');
    if (expense.is_recurring) {
        document.getElementById('expDetailRecurringFrequency').textContent =
            (expense.recurring_frequency || '').charAt(0).toUpperCase() +
            (expense.recurring_frequency || '').slice(1);
        recurringWrap.classList.remove('d-none');
    } else {
        recurringWrap.classList.add('d-none');
    }

    const receiptWrap = document.getElementById('expDetailReceiptWrap');
    const receiptLink = document.getElementById('expDetailReceiptLink');
    if (expense.receipt_url) {
        receiptLink.href = expense.receipt_url;
        receiptWrap.classList.remove('d-none');
    } else {
        receiptWrap.classList.add('d-none');
    }

    bootstrap.Modal.getOrCreateInstance(document.getElementById('expenseDetailModal')).show();
};
</script>