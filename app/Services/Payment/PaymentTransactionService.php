<?php

namespace App\Services\Payment;

use App\Models\PaymentMethod;
use App\Models\PaymentTransactionLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentTransactionService
{
    /**
     * Record a payment transaction and update balance
     *
     * @param array $transactionData
     * @return PaymentTransactionLog|null
     * @throws \Exception
     */
    public function recordTransaction(array $transactionData): ?PaymentTransactionLog
    {
        return DB::transaction(function () use ($transactionData) {
            try {
                // Validate required parameters
                $this->validateTransactionData($transactionData);
                
                // Get payment method with lock for update
                $paymentMethod = PaymentMethod::where('id', $transactionData['payment_method_id'])
                    ->where('tenant_id', $transactionData['tenant_id'])
                    ->lockForUpdate()
                    ->firstOrFail();
                
                // Validate balance constraints
                $this->validateBalanceConstraints($paymentMethod, $transactionData);
                
                // Calculate net amount
                $netAmount = $this->calculateNetAmount($transactionData, $paymentMethod);
                
                // Determine if it's a debit or credit
                $isDebit = $this->isDebitTransaction($transactionData['transaction_type']);
                
                // Calculate new balance
                $balanceBefore = $paymentMethod->current_balance;
                $balanceAfter = $isDebit 
                    ? $balanceBefore - $netAmount
                    : $balanceBefore + $netAmount;
                
                // Create transaction log
                $transactionLog = $this->createTransactionLog(
                    $transactionData,
                    $paymentMethod,
                    $balanceBefore,
                    $balanceAfter,
                    $netAmount
                );
                
                // Update payment method balance
                $this->updatePaymentMethodBalance($paymentMethod, $balanceAfter, $netAmount, $isDebit);
                
                // Update available balance if needed
                $this->updateAvailableBalance($paymentMethod, $transactionData);
                
                // Log the transaction
                $this->logTransactionEvent($transactionLog, $paymentMethod);
                
                return $transactionLog;
                
            } catch (\Exception $e) {
                Log::error('Payment transaction failed', [
                    'error' => $e->getMessage(),
                    'data' => $transactionData,
                    'trace' => $e->getTraceAsString()
                ]);
                
                throw $e;
            }
        });
    }
    
    /**
     * Validate transaction data
     */
    private function validateTransactionData(array $data): void
    {
        $required = [
            'tenant_id',
            'user_id',
            'payment_method_id',
            'transaction_type',
            'transaction_category',
            'amount',
            'currency_id'
        ];
        
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }
        
        // Validate amount
        if ($data['amount'] <= 0) {
            throw new \InvalidArgumentException("Amount must be greater than zero");
        }
    }
    
    /**
     * Validate balance constraints
     */
    private function validateBalanceConstraints(PaymentMethod $paymentMethod, array $transactionData): void
    {
        $isDebit = $this->isDebitTransaction($transactionData['transaction_type']);
        
        if (!$isDebit) {
            return; // Credits don't need balance validation
        }
        
        // Check if negative balance is allowed
        if (!$paymentMethod->allow_negative_balance) {
            $netAmount = $this->calculateNetAmount($transactionData, $paymentMethod);
            $newBalance = $paymentMethod->current_balance - $netAmount;
            
            if ($newBalance < 0) {
                throw new \RuntimeException(
                    "Insufficient balance. Negative balance not allowed for this payment method."
                );
            }
        }
        
        // Check minimum balance limit
        if (!is_null($paymentMethod->min_balance_limit)) {
            $netAmount = $this->calculateNetAmount($transactionData, $paymentMethod);
            $newBalance = $paymentMethod->current_balance - $netAmount;
            
            if ($newBalance < $paymentMethod->min_balance_limit) {
                throw new \RuntimeException(
                    "Transaction would violate minimum balance limit of " . 
                    number_format($paymentMethod->min_balance_limit, 2)
                );
            }
        }
    }
    
    /**
     * Calculate net amount including fees
     */
    private function calculateNetAmount(array $transactionData, PaymentMethod $paymentMethod): float
    {
        $amount = (float) $transactionData['amount'];
        $fee = 0;
        
        // Calculate transaction fees if applicable
        if (isset($transactionData['apply_fees']) && $transactionData['apply_fees']) {
            $percentageFee = ($amount * $paymentMethod->transaction_fee_percentage) / 100;
            $fixedFee = $paymentMethod->transaction_fee_fixed;
            $fee = $percentageFee + $fixedFee;
        }
        
        // Use provided fee if specified
        if (isset($transactionData['transaction_fee'])) {
            $fee = (float) $transactionData['transaction_fee'];
        }
        
        return $amount + $fee;
    }
    
    /**
     * Determine if transaction is debit
     */
    private function isDebitTransaction(string $transactionType): bool
    {
        $debitTypes = ['WITHDRAWAL', 'TRANSFER_OUT', 'FEE', 'EXPENSE', 'PURCHASE_ORDER'];
        
        // Check if transaction type starts with any debit type
        foreach ($debitTypes as $debitType) {
            if (strpos($transactionType, $debitType) !== false || 
                strpos($transactionType, '_OUT') !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Create transaction log record
     */
    private function createTransactionLog(
        array $data,
        PaymentMethod $paymentMethod,
        float $balanceBefore,
        float $balanceAfter,
        float $netAmount
    ): PaymentTransactionLog {
        $transactionData = [
            'transaction_ref' => Str::uuid(),
            'payment_method_id' => $data['payment_method_id'],
            'transaction_type' => $data['transaction_type'],
            'transaction_category' => $data['transaction_category'],
            'reference_table' => $data['reference_table'] ?? null,
            'reference_id' => $data['reference_id'] ?? null,
            'amount' => $data['amount'],
            'transaction_fee' => $data['transaction_fee'] ?? 0,
            'net_amount' => $netAmount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'currency_id' => $data['currency_id'],
            'exchange_rate' => $data['exchange_rate'] ?? 1,
            'status' => $data['status'] ?? 'COMPLETED',
            'description' => $data['description'] ?? null,
            'metadata' => $data['metadata'] ?? null,
            'notes' => $data['notes'] ?? null,
            'external_reference' => $data['external_reference'] ?? null,
            'bank_reference' => $data['bank_reference'] ?? null,
            'receipt_number' => $this->generateReceiptNumber(),
            'user_id' => $data['user_id'],
            'tenant_id' => $data['tenant_id'],
            'counterparty_id' => $data['counterparty_id'] ?? null,
            'counterparty_name' => $data['counterparty_name'] ?? null,
            'counterparty_account' => $data['counterparty_account'] ?? null,
            'transaction_date' => $data['transaction_date'] ?? now(),
            'effective_date' => $data['effective_date'] ?? now(),
            'settlement_date' => $data['settlement_date'] ?? null,
        ];
        
        return PaymentTransactionLog::create($transactionData);
    }
    
    /**
     * Generate receipt number
     */
    private function generateReceiptNumber(): string
    {
        return 'RCPT-' . date('Ymd') . '-' . Str::random(8);
    }
    
    /**
     * Update payment method balance
     */
    private function updatePaymentMethodBalance(
        PaymentMethod $paymentMethod,
        float $newBalance,
        float $netAmount,
        bool $isDebit
    ): void {
        $paymentMethod->current_balance = $newBalance;
        
        // Update tracking fields
        $paymentMethod->last_transaction_at = now();
        $paymentMethod->last_transaction_amount = $netAmount;
        $paymentMethod->last_transaction_type = $isDebit ? 'DEBIT' : 'CREDIT';
        
        $paymentMethod->save();
    }
    
    /**
     * Update available balance
     */
    private function updateAvailableBalance(PaymentMethod $paymentMethod, array $transactionData): void
    {
        // For pending transactions, adjust available balance
        if (($transactionData['status'] ?? 'COMPLETED') === 'PENDING') {
            $isDebit = $this->isDebitTransaction($transactionData['transaction_type']);
            
            if ($isDebit) {
                $paymentMethod->available_balance -= $transactionData['amount'];
                $paymentMethod->pending_balance += $transactionData['amount'];
            }
            
            $paymentMethod->save();
        }
    }
    
    /**
     * Log transaction event
     */
    private function logTransactionEvent(PaymentTransactionLog $log, PaymentMethod $paymentMethod): void
    {
        Log::info('Payment transaction recorded', [
            'transaction_ref' => $log->transaction_ref,
            'payment_method' => $paymentMethod->name,
            'payment_method_id' => $paymentMethod->id,
            'transaction_type' => $log->transaction_type,
            'amount' => $log->amount,
            'balance_before' => $log->balance_before,
            'balance_after' => $log->balance_after,
            'tenant_id' => $log->tenant_id,
            'user_id' => $log->user_id
        ]);
    }
    
    /**
     * Helper function to record expense
     */
    public function recordExpense(array $expenseData): ?PaymentTransactionLog
    {
        $transactionData = array_merge([
            'transaction_type' => 'WITHDRAWAL',
            'transaction_category' => 'EXPENSE',
            'reference_table' => 'expenses',
            'description' => 'Expense Payment',
        ], $expenseData);
        
        return $this->recordTransaction($transactionData);
    }
    
    /**
     * Helper function to record purchase order payment
     */
    public function recordPurchaseOrder(array $poData): ?PaymentTransactionLog
    {
        $transactionData = array_merge([
            'transaction_type' => 'WITHDRAWAL',
            'transaction_category' => 'PURCHASE_ORDER',
            'reference_table' => 'purchase_orders',
            'description' => 'Purchase Order Payment',
        ], $poData);
        
        return $this->recordTransaction($transactionData);
    }
    
    /**
     * Helper function to record payment received
     */
    public function recordPaymentReceived(array $paymentData): ?PaymentTransactionLog
    {
        $transactionData = array_merge([
            'transaction_type' => 'DEPOSIT',
            'transaction_category' => 'PAYMENT',
            'reference_table' => 'payments',
            'description' => 'Payment Received',
        ], $paymentData);
        
        return $this->recordTransaction($transactionData);
    }
    
    /**
     * Helper function to record order payment
     */
    public function recordOrderPayment(array $orderData): ?PaymentTransactionLog
    {
        $transactionData = array_merge([
            'transaction_type' => 'WITHDRAWAL',
            'transaction_category' => 'ORDER',
            'reference_table' => 'orders',
            'description' => 'Order Payment',
        ], $orderData);
        
        return $this->recordTransaction($transactionData);
    }
    
    /**
     * Get payment method balance
     */
    public function getBalance(int $paymentMethodId, int $tenantId): array
    {
        $paymentMethod = PaymentMethod::where('id', $paymentMethodId)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();
        
        return [
            'current_balance' => $paymentMethod->current_balance,
            'available_balance' => $paymentMethod->available_balance,
            'pending_balance' => $paymentMethod->pending_balance,
            'currency' => $paymentMethod->currency?->code ?? 'USD',
            'last_updated' => $paymentMethod->updated_at
        ];
    }
    
    /**
     * Get transaction history
     */
    public function getTransactionHistory(int $paymentMethodId, array $filters = []): array
    {
        $query = PaymentTransactionLog::where('payment_method_id', $paymentMethodId)
            ->orderBy('transaction_date', 'desc');
        
        // Apply filters
        if (!empty($filters['start_date'])) {
            $query->where('transaction_date', '>=', $filters['start_date']);
        }
        
        if (!empty($filters['end_date'])) {
            $query->where('transaction_date', '<=', $filters['end_date']);
        }
        
        if (!empty($filters['transaction_type'])) {
            $query->where('transaction_type', $filters['transaction_type']);
        }
        
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        $transactions = $query->paginate($filters['per_page'] ?? 50);
        
        return [
            'transactions' => $transactions->items(),
            'pagination' => [
                'total' => $transactions->total(),
                'per_page' => $transactions->perPage(),
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
            ]
        ];
    }
}