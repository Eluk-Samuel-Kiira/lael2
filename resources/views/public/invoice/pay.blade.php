{{-- resources/views/public/invoice/pay.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('payments.pay_invoice') }} {{ $invoice->invoice_number }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="bi bi-credit-card me-2"></i>
                            {{ __('payments.pay_invoice') }}
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <h5>{{ __('payments.invoice') }} #{{ $invoice->invoice_number }}</h5>
                            <p class="mb-1">{{ __('payments.bill_to') }}: <strong>{{ $invoice->billing_name }}</strong></p>
                            <div class="row mt-3">
                                <div class="col-6">
                                    <div class="bg-light p-3 rounded">
                                        <small class="text-muted">{{ __('payments.total_amount') }}</small>
                                        <h4 class="text-primary">{{ currency_symbol() }} {{ number_format($invoice->total, 2) }}</h4>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="bg-light p-3 rounded">
                                        <small class="text-muted">{{ __('payments.balance_due') }}</small>
                                        <h4 class="text-danger">{{ currency_symbol() }} {{ number_format($invoice->balance_due, 2) }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <form id="paymentForm">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label fw-bold">{{ __('payments.amount') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text">{{ currency_symbol() }}</span>
                                    <input type="number" step="0.01" min="0.01" max="{{ $invoice->balance_due }}"
                                           name="amount" class="form-control form-control-lg"
                                           value="{{ number_format($invoice->balance_due, 2, '.', '') }}"
                                           required>
                                </div>
                                <small class="text-muted">{{ __('payments.max_amount') }}: {{ currency_symbol() }} {{ number_format($invoice->balance_due, 2) }}</small>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold">{{ __('payments.payment_method') }}</label>
                                <select name="payment_method_id" class="form-select form-select-lg" required>
                                    <option value="">{{ __('payments.select_payment_method') }}</option>
                                    @foreach($paymentMethods as $method)
                                    <option value="{{ $method->id }}">
                                        <i class="bi bi-{{ $method->type == 'mobile_money' ? 'phone' : ($method->type == 'bank' ? 'bank' : 'credit-card') }} me-2"></i>
                                        {{ $method->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <button type="button" id="payButton" class="btn btn-success btn-lg w-100" onclick="processPayment()">
                                <span class="indicator-label">
                                    <i class="bi bi-check-circle me-2"></i>{{ __('payments.pay_now') }}
                                </span>
                                <span class="indicator-progress" style="display: none;">
                                    {{ __('payments.processing') }} <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                </span>
                            </button>
                        </form>
                        
                        <div class="mt-3 text-center">
                            <a href="{{ route('public.invoice.show', $invoice->public_token) }}" class="text-decoration-none">
                                <i class="bi bi-arrow-left me-1"></i>{{ __('payments.back_to_invoice') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function processPayment() {
            const button = document.getElementById('payButton');
            const form = document.getElementById('paymentForm');
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            
            // Validate amount
            const amount = parseFloat(data.amount);
            const maxAmount = {{ $invoice->balance_due }};
            
            if (!amount || amount <= 0) {
                alert('Please enter a valid amount.');
                return;
            }
            
            if (amount > maxAmount) {
                alert('Amount cannot exceed balance due: {{ currency_symbol() }} ' + maxAmount.toFixed(2));
                return;
            }
            
            // Show loading
            button.querySelector('.indicator-label').style.display = 'none';
            button.querySelector('.indicator-progress').style.display = 'inline-block';
            button.disabled = true;
            
            // Send payment
            fetch('{{ route("public.invoice.process-payment", $invoice->public_token) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                button.querySelector('.indicator-label').style.display = 'inline-block';
                button.querySelector('.indicator-progress').style.display = 'none';
                button.disabled = false;
                
                if (result.success) {
                    alert('Payment successful! Redirecting...');
                    window.location.href = result.redirect || '{{ route("public.invoice.show", $invoice->public_token) }}';
                } else {
                    alert('Payment failed: ' + result.message);
                }
            })
            .catch(error => {
                button.querySelector('.indicator-label').style.display = 'inline-block';
                button.querySelector('.indicator-progress').style.display = 'none';
                button.disabled = false;
                alert('An error occurred. Please try again.');
                console.error('Error:', error);
            });
        }
    </script>
</body>
</html>