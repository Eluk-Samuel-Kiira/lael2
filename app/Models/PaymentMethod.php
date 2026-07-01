<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentMethod extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'code',
        'description',
        'provider',
        'account_name',
        'account_number',
        'iban',
        'swift_bic',
        'routing_number',
        'card_last_four',
        'card_type',
        'card_expiry_date',
        'wallet_id',
        'wallet_email',
        'transaction_fee_percentage',
        'transaction_fee_fixed',
        'min_transaction_amount',
        'max_transaction_amount',
        'daily_limit',
        'monthly_limit',
    
        // NEW: Balance fields
        'current_balance',
        'available_balance',
        'pending_balance',
        'min_balance_limit',
        'max_balance_limit',
        'allow_negative_balance',
        'last_reconciled_at',
        'last_transaction_at',
        'last_transaction_amount',
        'last_transaction_type',
    
        'is_active',
        'is_default',
        'is_online',
        'requires_verification',
        'is_verified',
        'verified_at',
        'token',
        'api_key',
        'secret_key',
        'webhook_url',
        'settings',
        'currency_id',
        'supported_currencies',
        'cash_handler_id',
        'cash_location',
        'created_by',
        'tenant_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'is_online' => 'boolean',
        'requires_verification' => 'boolean',
        'is_verified' => 'boolean',
        'allow_negative_balance' => 'boolean',
        
        // Decimal casts
        'transaction_fee_percentage' => 'decimal:2',
        'transaction_fee_fixed' => 'decimal:2',
        'min_transaction_amount' => 'decimal:2',
        'max_transaction_amount' => 'decimal:2',
        'daily_limit' => 'decimal:2',
        'monthly_limit' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'available_balance' => 'decimal:2',
        'pending_balance' => 'decimal:2',
        'min_balance_limit' => 'decimal:2',
        'max_balance_limit' => 'decimal:2',
        'last_transaction_amount' => 'decimal:2',
        
        // Date casts
        'verified_at' => 'date',
        'card_expiry_date' => 'date',
        'last_reconciled_at' => 'datetime',
        'last_transaction_at' => 'datetime',
        
        // Array casts
        'settings' => 'array',
        'supported_currencies' => 'array',
    ];

    protected $attributes = [
        'current_balance' => 5000.00,
        'available_balance' => 5000.00,
        'pending_balance' => 0.00,
        'allow_negative_balance' => true,
    ];

    // Relationships
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function createdPaymentMethods()
    {
        return $this->hasMany(PaymentMethod::class, 'created_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function cashHandler()
    {
        return $this->belongsTo(User::class, 'cash_handler_id');
    }

    public function payments()
    {
        return $this->hasMany(EmployeePayment::class, 'payment_method_id');
    }

    public function purchaseOrderItems()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    // NEW: Transaction logs relationship
    public function transactionLogs()
    {
        return $this->hasMany(PaymentTransactionLog::class);
    }

    public function debitTransactions()
    {
        return $this->hasMany(PaymentTransactionLog::class)->whereIn('transaction_type', [
            'WITHDRAWAL', 'TRANSFER_OUT', 'FEE', 'EXPENSE', 'PURCHASE_ORDER'
        ]);
    }

    public function creditTransactions()
    {
        return $this->hasMany(PaymentTransactionLog::class)->whereIn('transaction_type', [
            'DEPOSIT', 'TRANSFER_IN', 'REFUND', 'ADJUSTMENT'
        ]);
    }

    // NEW: Counterparty relationships (for transfers)
    public function outgoingTransfers()
    {
        return $this->hasMany(PaymentTransactionLog::class, 'counterparty_id');
    }

    public function incomingTransfers()
    {
        return $this->hasMany(PaymentTransactionLog::class, 'payment_method_id')
            ->whereNotNull('counterparty_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOnline($query)
    {
        return $query->where('is_online', true);
    }

    public function scopeOffline($query)
    {
        return $query->where('is_online', false);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // NEW: Balance-related scopes
    public function scopePositiveBalance($query)
    {
        return $query->where('current_balance', '>', 0);
    }

    public function scopeNegativeBalance($query)
    {
        return $query->where('current_balance', '<', 0);
    }

    public function scopeZeroBalance($query)
    {
        return $query->where('current_balance', 0);
    }

    public function scopeWithSufficientBalance($query, $amount)
    {
        return $query->where(function ($q) use ($amount) {
            $q->where('allow_negative_balance', true)
              ->orWhere('current_balance', '>=', $amount);
        });
    }

    public function scopeLowBalance($query, $threshold = 100)
    {
        return $query->where('current_balance', '<', $threshold)
                    ->where('current_balance', '>=', 0);
    }

    public function scopeCriticalBalance($query, $threshold = 0)
    {
        return $query->where('current_balance', '<', $threshold);
    }

    public function scopeByBalanceRange($query, $min = null, $max = null)
    {
        if ($min !== null) {
            $query->where('current_balance', '>=', $min);
        }
        
        if ($max !== null) {
            $query->where('current_balance', '<=', $max);
        }
        
        return $query;
    }

    // Methods
    public function getFormattedAccountNumberAttribute()
    {
        if (!$this->account_number) {
            return null;
        }

        // Show only last 4 digits for security
        return '****' . substr($this->account_number, -4);
    }

    public function getDisplayNameAttribute()
    {
        if ($this->type === 'bank_account') {
            return $this->provider . ' - ' . $this->account_name . ' (' . $this->getFormattedAccountNumberAttribute() . ')';
        } elseif ($this->type === 'card') {
            return ucfirst($this->card_type) . ' ending in ' . $this->card_last_four;
        } elseif ($this->type === 'digital_wallet') {
            return $this->provider . ' - ' . $this->wallet_email;
        }

        return $this->name;
    }

    // NEW: Balance-related methods
    public function getFormattedCurrentBalanceAttribute()
    {
        $currencyCode = $this->currency?->code ?? 'USD';
        $symbol = $this->currency?->symbol ?? '$';
        
        return $symbol . number_format($this->current_balance, 2) . " {$currencyCode}";
    }

    public function getFormattedAvailableBalanceAttribute()
    {
        $currencyCode = $this->currency?->code ?? 'USD';
        $symbol = $this->currency?->symbol ?? '$';
        
        return $symbol . number_format($this->available_balance, 2) . " {$currencyCode}";
    }

    public function getBalanceStatusAttribute()
    {
        if ($this->current_balance < 0) {
            return 'negative';
        } elseif ($this->current_balance == 0) {
            return 'zero';
        } elseif ($this->current_balance < ($this->min_balance_limit ?? 100)) {
            return 'low';
        } else {
            return 'healthy';
        }
    }

    public function getBalanceStatusColorAttribute()
    {
        return match($this->balance_status) {
            'negative' => 'danger',
            'zero' => 'warning',
            'low' => 'warning',
            'healthy' => 'success',
            default => 'secondary'
        };
    }

    public function hasSufficientBalance($amount, $includeFees = false)
    {
        if ($this->allow_negative_balance) {
            return true;
        }

        $requiredAmount = $amount;
        
        if ($includeFees) {
            $requiredAmount += $this->calculateFee($amount);
        }

        return $this->current_balance >= $requiredAmount;
    }

    public function calculateFee($amount)
    {
        $percentageFee = ($amount * $this->transaction_fee_percentage) / 100;
        return $percentageFee + $this->transaction_fee_fixed;
    }

    public function isWithinLimits($amount)
    {
        if ($this->min_transaction_amount && $amount < $this->min_transaction_amount) {
            return false;
        }

        if ($this->max_transaction_amount && $amount > $this->max_transaction_amount) {
            return false;
        }

        // Check balance limits
        if ($this->min_balance_limit && ($this->current_balance - $amount) < $this->min_balance_limit) {
            return false;
        }

        return true;
    }

    // NEW: Transaction methods
    public function recordTransaction(array $transactionData)
    {
        return app('payment-transaction')->recordTransaction(
            array_merge($transactionData, [
                'payment_method_id' => $this->id,
                'tenant_id' => $this->tenant_id
            ])
        );
    }

    public function recordExpense(array $expenseData)
    {
        return app('payment-transaction')->recordExpense(
            array_merge($expenseData, [
                'payment_method_id' => $this->id,
                'tenant_id' => $this->tenant_id
            ])
        );
    }

    public function recordPurchaseOrder(array $poData)
    {
        return app('payment-transaction')->recordPurchaseOrder(
            array_merge($poData, [
                'payment_method_id' => $this->id,
                'tenant_id' => $this->tenant_id
            ])
        );
    }

    public function recordPaymentReceived(array $paymentData)
    {
        return app('payment-transaction')->recordPaymentReceived(
            array_merge($paymentData, [
                'payment_method_id' => $this->id,
                'tenant_id' => $this->tenant_id
            ])
        );
    }

    public function recordOrderPayment(array $orderData)
    {
        return app('payment-transaction')->recordOrderPayment(
            array_merge($orderData, [
                'payment_method_id' => $this->id,
                'tenant_id' => $this->tenant_id
            ])
        );
    }

    public function getTransactionHistory(array $filters = [])
    {
        return app('payment-transaction')->getTransactionHistory($this->id, $filters);
    }

    public function updateBalance($amount, $transactionType = 'ADJUSTMENT', $description = null)
    {
        $user = auth()->user();
        
        if (!$user) {
            throw new \Exception('User authentication required');
        }

        $transactionData = [
            'user_id' => $user->id,
            'transaction_type' => $amount >= 0 ? 'ADJUSTMENT' : 'ADJUSTMENT',
            'transaction_category' => 'ADJUSTMENT',
            'amount' => abs($amount),
            'currency_id' => $this->currency_id ?? Currency::default()->id,
            'description' => $description ?? 'Balance adjustment',
            'notes' => 'Manual balance update',
        ];

        if ($amount >= 0) {
            return $this->recordTransaction($transactionData);
        } else {
            $transactionData['transaction_type'] = 'WITHDRAWAL';
            return $this->recordTransaction($transactionData);
        }
    }

    public function reconcileBalance($newBalance, $notes = null)
    {
        $difference = $newBalance - $this->current_balance;
        
        if ($difference == 0) {
            return null;
        }

        $transaction = $this->updateBalance(
            $difference,
            'RECONCILIATION',
            'Balance reconciliation'
        );

        if ($transaction) {
            $this->update([
                'last_reconciled_at' => now(),
            ]);
        }

        return $transaction;
    }

    public static function getDefaultForCurrentTenant(): ?PaymentMethod
    {
        $tenantId = current_tenant_id() ?? Auth::user()->tenant_id ?? null;
        
        if (!$tenantId) {
            return null;
        }
        
        return self::getDefaultForTenant($tenantId);
    }

    public static function getDefaultForTenant($tenantId): ?PaymentMethod
    {
        return self::where('tenant_id', $tenantId)
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();
    }

    // NEW: Balance statistics
    public function getMonthlyStats($year = null, $month = null)
    {
        $year = $year ?? date('Y');
        $month = $month ?? date('m');

        $startDate = "{$year}-{$month}-01";
        $endDate = date('Y-m-t', strtotime($startDate));

        $transactions = $this->transactionLogs()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->get();

        $credits = $transactions->whereIn('transaction_type', ['DEPOSIT', 'TRANSFER_IN', 'REFUND']);
        $debits = $transactions->whereIn('transaction_type', ['WITHDRAWAL', 'TRANSFER_OUT', 'FEE']);

        return [
            'total_credits' => $credits->sum('amount'),
            'total_debits' => $debits->sum('amount'),
            'transaction_count' => $transactions->count(),
            'average_transaction' => $transactions->avg('amount'),
            'largest_transaction' => $transactions->max('amount'),
        ];
    }

    // NEW: Validation methods
    public function validateTransaction($amount, $includeFees = false)
    {
        if (!$this->is_active) {
            return ['success' => false, 'message' => 'Payment method is not active'];
        }

        if (!$this->is_verified && $this->requires_verification) {
            return ['success' => false, 'message' => 'Payment method requires verification'];
        }

        if (!$this->hasSufficientBalance($amount, $includeFees)) {
            return ['success' => false, 'message' => 'Insufficient balance'];
        }

        if (!$this->isWithinLimits($amount)) {
            return ['success' => false, 'message' => 'Amount is outside allowed limits'];
        }

        return ['success' => true, 'message' => 'Transaction validated'];
    }

    public static function getTypeById($id): ?string
    {
        return self::where('id', $id)->value('type');
    }

    public static function findForTenant($id, $tenantId = null): ?PaymentMethod
    {
        $tenantId = $tenantId ?? auth()->user()->tenant_id ?? null;
        
        return self::where('id', $id)
            ->when($tenantId, function ($query) use ($tenantId) {
                return $query->where('tenant_id', $tenantId);
            })
            ->first();
    }

    // Add this method to your PaymentMethod model
    /**
     * Get human-readable type label
     */
    public function getTypeLabel(): string
    {
        return match($this->type) {
            'bank_account' => 'Bank Account',
            'card' => 'Credit/Debit Card',
            'digital_wallet' => 'Digital Wallet',
            'cash' => 'Cash',
            'check' => 'Check',
            'mobile_money' => 'Mobile Money',
            'crypto' => 'Cryptocurrency',
            'gift_card' => 'Gift Card',
            default => ucfirst(str_replace('_', ' ', $this->type)),
        };
    }
}