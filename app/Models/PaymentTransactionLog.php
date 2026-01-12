<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;

class PaymentTransactionLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'payment_transaction_logs';

    protected $fillable = [
        'transaction_ref',
        'payment_method_id',
        'transaction_type',
        'transaction_category',
        'reference_table',
        'reference_id',
        'amount',
        'transaction_fee',
        'net_amount',
        'balance_before',
        'balance_after',
        'currency_id',
        'exchange_rate',
        'status',
        'transaction_date',
        'effective_date',
        'settlement_date',
        'description',
        'metadata',
        'notes',
        'external_reference',
        'bank_reference',
        'receipt_number',
        'user_id',
        'tenant_id',
        'counterparty_id',
        'counterparty_name',
        'counterparty_account',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'metadata' => 'array',
        'transaction_date' => 'datetime',
        'effective_date' => 'datetime',
        'settlement_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'COMPLETED',
        'exchange_rate' => 1,
    ];

    // Transaction type constants
    const TYPE_DEPOSIT = 'DEPOSIT';
    const TYPE_WITHDRAWAL = 'WITHDRAWAL';
    const TYPE_TRANSFER_IN = 'TRANSFER_IN';
    const TYPE_TRANSFER_OUT = 'TRANSFER_OUT';
    const TYPE_FEE = 'FEE';
    const TYPE_REFUND = 'REFUND';
    const TYPE_ADJUSTMENT = 'ADJUSTMENT';
    const TYPE_RECONCILIATION = 'RECONCILIATION';

    // Transaction category constants
    const CATEGORY_EXPENSE = 'EXPENSE';
    const CATEGORY_PURCHASE_ORDER = 'PURCHASE_ORDER';
    const CATEGORY_PAYMENT = 'PAYMENT';
    const CATEGORY_ORDER = 'ORDER';
    const CATEGORY_SALARY = 'SALARY';
    const CATEGORY_INVOICE = 'INVOICE';
    const CATEGORY_REFUND = 'REFUND';
    const CATEGORY_FEE = 'FEE';
    const CATEGORY_ADJUSTMENT = 'ADJUSTMENT';

    // Status constants
    const STATUS_PENDING = 'PENDING';
    const STATUS_COMPLETED = 'COMPLETED';
    const STATUS_FAILED = 'FAILED';
    const STATUS_CANCELLED = 'CANCELLED';
    const STATUS_REVERSED = 'REVERSED';

    // Relationships
    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function counterparty()
    {
        return $this->belongsTo(PaymentMethod::class, 'counterparty_id');
    }

    public function reference()
    {
        if (!$this->reference_table || !$this->reference_id) {
            return null;
        }

        return $this->morphTo(__FUNCTION__, 'reference_table', 'reference_id');
    }

    // Scopes
    public function scopeDeposits($query)
    {
        return $query->where('transaction_type', self::TYPE_DEPOSIT);
    }

    public function scopeWithdrawals($query)
    {
        return $query->where('transaction_type', self::TYPE_WITHDRAWAL);
    }

    public function scopeTransfers($query)
    {
        return $query->whereIn('transaction_type', [self::TYPE_TRANSFER_IN, self::TYPE_TRANSFER_OUT]);
    }

    public function scopeFees($query)
    {
        return $query->where('transaction_type', self::TYPE_FEE);
    }

    public function scopeRefunds($query)
    {
        return $query->where('transaction_type', self::TYPE_REFUND);
    }

    public function scopeAdjustments($query)
    {
        return $query->where('transaction_type', self::TYPE_ADJUSTMENT);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('transaction_category', $category);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('transaction_type', $type);
    }

    public function scopeDebits($query)
    {
        return $query->whereIn('transaction_type', [
            self::TYPE_WITHDRAWAL,
            self::TYPE_TRANSFER_OUT,
            self::TYPE_FEE,
            self::CATEGORY_EXPENSE,
            self::CATEGORY_PURCHASE_ORDER,
        ]);
    }

    public function scopeCredits($query)
    {
        return $query->whereIn('transaction_type', [
            self::TYPE_DEPOSIT,
            self::TYPE_TRANSFER_IN,
            self::TYPE_REFUND,
            self::TYPE_ADJUSTMENT,
        ]);
    }

    public function scopeForPaymentMethod($query, $paymentMethodId)
    {
        return $query->where('payment_method_id', $paymentMethodId);
    }

    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, $limit = 10)
    {
        return $query->orderBy('transaction_date', 'desc')->limit($limit);
    }

    public function scopeLargeTransactions($query, $threshold = 1000)
    {
        return $query->where('amount', '>=', $threshold);
    }

    // Accessors & Mutators
    protected function formattedAmount(): Attribute
    {
        return Attribute::make(
            get: function () {
                $currency = $this->currency;
                $symbol = $currency?->symbol ?? '$';
                $code = $currency?->code ?? 'USD';
                
                return $symbol . number_format($this->amount, 2) . ' ' . $code;
            }
        );
    }

    protected function formattedBalanceBefore(): Attribute
    {
        return Attribute::make(
            get: function () {
                $currency = $this->currency;
                $symbol = $currency?->symbol ?? '$';
                $code = $currency?->code ?? 'USD';
                
                return $symbol . number_format($this->balance_before, 2) . ' ' . $code;
            }
        );
    }

    protected function formattedBalanceAfter(): Attribute
    {
        return Attribute::make(
            get: function () {
                $currency = $this->currency;
                $symbol = $currency?->symbol ?? '$';
                $code = $currency?->code ?? 'USD';
                
                return $symbol . number_format($this->balance_after, 2) . ' ' . $code;
            }
        );
    }

    protected function isCredit(): Attribute
    {
        return Attribute::make(
            get: function () {
                return in_array($this->transaction_type, [
                    self::TYPE_DEPOSIT,
                    self::TYPE_TRANSFER_IN,
                    self::TYPE_REFUND,
                    self::TYPE_ADJUSTMENT,
                    self::TYPE_RECONCILIATION,
                ]);
            }
        );
    }

    protected function isDebit(): Attribute
    {
        return Attribute::make(
            get: function () {
                return in_array($this->transaction_type, [
                    self::TYPE_WITHDRAWAL,
                    self::TYPE_TRANSFER_OUT,
                    self::TYPE_FEE,
                ]);
            }
        );
    }

    protected function isPending(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status === self::STATUS_PENDING
        );
    }

    protected function isCompleted(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status === self::STATUS_COMPLETED
        );
    }

    protected function isFailed(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status === self::STATUS_FAILED
        );
    }

    protected function direction(): Attribute
    {
        return Attribute::make(
            get: function () {
                return $this->is_credit ? 'credit' : 'debit';
            }
        );
    }

    protected function directionSymbol(): Attribute
    {
        return Attribute::make(
            get: function () {
                return $this->is_credit ? '+' : '-';
            }
        );
    }

    protected function amountWithSymbol(): Attribute
    {
        return Attribute::make(
            get: function () {
                $symbol = $this->direction_symbol;
                $currency = $this->currency;
                $code = $currency?->code ?? 'USD';
                
                return $symbol . $currency?->symbol . number_format($this->amount, 2) . ' ' . $code;
            }
        );
    }

    protected function transactionDateFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->transaction_date?->format('M d, Y h:i A')
        );
    }

    protected function effectiveDateFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->effective_date?->format('M d, Y h:i A')
        );
    }

    protected function settlementDateFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->settlement_date?->format('M d, Y h:i A')
        );
    }

    // Methods
    public function getTransactionTypeLabel(): string
    {
        return match($this->transaction_type) {
            self::TYPE_DEPOSIT => 'Deposit',
            self::TYPE_WITHDRAWAL => 'Withdrawal',
            self::TYPE_TRANSFER_IN => 'Transfer In',
            self::TYPE_TRANSFER_OUT => 'Transfer Out',
            self::TYPE_FEE => 'Fee',
            self::TYPE_REFUND => 'Refund',
            self::TYPE_ADJUSTMENT => 'Adjustment',
            self::TYPE_RECONCILIATION => 'Reconciliation',
            default => ucfirst(strtolower($this->transaction_type)),
        };
    }

    public function getTransactionCategoryLabel(): string
    {
        return match($this->transaction_category) {
            self::CATEGORY_EXPENSE => 'Expense',
            self::CATEGORY_PURCHASE_ORDER => 'Purchase Order',
            self::CATEGORY_PAYMENT => 'Payment',
            self::CATEGORY_ORDER => 'Order',
            self::CATEGORY_SALARY => 'Salary',
            self::CATEGORY_INVOICE => 'Invoice',
            self::CATEGORY_REFUND => 'Refund',
            self::CATEGORY_FEE => 'Fee',
            self::CATEGORY_ADJUSTMENT => 'Adjustment',
            default => ucfirst(strtolower(str_replace('_', ' ', $this->transaction_category))),
        };
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_REVERSED => 'Reversed',
            default => ucfirst(strtolower($this->status)),
        };
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_FAILED => 'danger',
            self::STATUS_CANCELLED => 'secondary',
            self::STATUS_REVERSED => 'info',
            default => 'secondary',
        };
    }

    public function getBalanceChange(): float
    {
        return $this->balance_after - $this->balance_before;
    }

    public function getFormattedBalanceChange(): string
    {
        $change = $this->getBalanceChange();
        $symbol = $change >= 0 ? '+' : '';
        $currency = $this->currency;
        $code = $currency?->code ?? 'USD';
        
        return $symbol . $currency?->symbol . number_format($change, 2) . ' ' . $code;
    }

    public function isTransfer(): bool
    {
        return in_array($this->transaction_type, [self::TYPE_TRANSFER_IN, self::TYPE_TRANSFER_OUT]);
    }

    public function hasCounterparty(): bool
    {
        return !is_null($this->counterparty_id) || !is_null($this->counterparty_name);
    }

    public function getCounterpartyDisplay(): ?string
    {
        if ($this->counterparty) {
            return $this->counterparty->display_name;
        }
        
        if ($this->counterparty_name) {
            return $this->counterparty_name . ($this->counterparty_account ? " ({$this->counterparty_account})" : '');
        }
        
        return null;
    }

    public function getReferenceDisplay(): ?string
    {
        if (!$this->reference_table || !$this->reference_id) {
            return null;
        }

        $reference = $this->reference;
        
        if (!$reference) {
            return "{$this->reference_table} #{$this->reference_id}";
        }

        if (method_exists($reference, 'getTransactionReferenceDisplay')) {
            return $reference->getTransactionReferenceDisplay();
        }

        if (isset($reference->reference_number)) {
            return $reference->reference_number;
        }

        if (isset($reference->invoice_number)) {
            return $reference->invoice_number;
        }

        if (isset($reference->order_number)) {
            return $reference->order_number;
        }

        return "{$this->reference_table} #{$this->reference_id}";
    }

    public function canBeReversed(): bool
    {
        return $this->status === self::STATUS_COMPLETED && 
               !in_array($this->transaction_type, [self::TYPE_RECONCILIATION]);
    }

    public function reverse(string $reason = null): bool
    {
        if (!$this->canBeReversed()) {
            return false;
        }

        try {
            // Create reverse transaction
            $reverseData = [
                'transaction_ref' => \Illuminate\Support\Str::uuid(),
                'payment_method_id' => $this->payment_method_id,
                'transaction_type' => $this->is_credit ? self::TYPE_WITHDRAWAL : self::TYPE_DEPOSIT,
                'transaction_category' => self::CATEGORY_REFUND,
                'reference_table' => $this->reference_table,
                'reference_id' => $this->reference_id,
                'amount' => $this->amount,
                'transaction_fee' => 0,
                'net_amount' => $this->amount,
                'currency_id' => $this->currency_id,
                'exchange_rate' => $this->exchange_rate,
                'status' => self::STATUS_COMPLETED,
                'description' => 'Reversal of ' . $this->description,
                'notes' => $reason ?? 'Transaction reversed',
                'external_reference' => null,
                'bank_reference' => null,
                'user_id' => auth()->id() ?? $this->user_id,
                'tenant_id' => $this->tenant_id,
                'counterparty_id' => $this->counterparty_id,
                'counterparty_name' => $this->counterparty_name,
                'counterparty_account' => $this->counterparty_account,
                'metadata' => [
                    'reversed_transaction_id' => $this->id,
                    'original_transaction_ref' => $this->transaction_ref,
                    'reversal_reason' => $reason,
                ],
            ];

            // Update original transaction
            $this->update([
                'status' => self::STATUS_REVERSED,
                'notes' => ($this->notes ? $this->notes . ' | ' : '') . 'Reversed: ' . $reason,
            ]);

            // Create the reversal
            self::create($reverseData);

            return true;

        } catch (\Exception $e) {
            \Log::error('Failed to reverse transaction', [
                'transaction_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    public static function getTransactionTypes(): array
    {
        return [
            self::TYPE_DEPOSIT => 'Deposit',
            self::TYPE_WITHDRAWAL => 'Withdrawal',
            self::TYPE_TRANSFER_IN => 'Transfer In',
            self::TYPE_TRANSFER_OUT => 'Transfer Out',
            self::TYPE_FEE => 'Fee',
            self::TYPE_REFUND => 'Refund',
            self::TYPE_ADJUSTMENT => 'Adjustment',
            self::TYPE_RECONCILIATION => 'Reconciliation',
        ];
    }

    public static function getTransactionCategories(): array
    {
        return [
            self::CATEGORY_EXPENSE => 'Expense',
            self::CATEGORY_PURCHASE_ORDER => 'Purchase Order',
            self::CATEGORY_PAYMENT => 'Payment',
            self::CATEGORY_ORDER => 'Order',
            self::CATEGORY_SALARY => 'Salary',
            self::CATEGORY_INVOICE => 'Invoice',
            self::CATEGORY_REFUND => 'Refund',
            self::CATEGORY_FEE => 'Fee',
            self::CATEGORY_ADJUSTMENT => 'Adjustment',
        ];
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_REVERSED => 'Reversed',
        ];
    }

    // Business Logic Methods
    public function markAsCompleted(): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        // Update the payment method's pending balance
        $paymentMethod = $this->paymentMethod;
        if ($paymentMethod && $this->is_debit) {
            $paymentMethod->pending_balance = max(0, $paymentMethod->pending_balance - $this->amount);
            $paymentMethod->available_balance = $paymentMethod->current_balance - $paymentMethod->pending_balance;
            $paymentMethod->save();
        }

        return $this->update(['status' => self::STATUS_COMPLETED]);
    }

    public function markAsFailed(string $reason = null): bool
    {
        if (!in_array($this->status, [self::STATUS_PENDING, self::STATUS_COMPLETED])) {
            return false;
        }

        // Reverse the balance if it was completed
        if ($this->status === self::STATUS_COMPLETED) {
            $paymentMethod = $this->paymentMethod;
            if ($paymentMethod) {
                if ($this->is_credit) {
                    $paymentMethod->current_balance -= $this->net_amount;
                } else {
                    $paymentMethod->current_balance += $this->net_amount;
                }
                
                // Update pending balance if it was pending
                if ($this->was_pending) {
                    $paymentMethod->pending_balance = max(0, $paymentMethod->pending_balance - $this->amount);
                }
                
                $paymentMethod->available_balance = $paymentMethod->current_balance - $paymentMethod->pending_balance;
                $paymentMethod->save();
            }
        }

        return $this->update([
            'status' => self::STATUS_FAILED,
            'notes' => ($this->notes ? $this->notes . ' | ' : '') . 'Failed: ' . $reason,
        ]);
    }
}