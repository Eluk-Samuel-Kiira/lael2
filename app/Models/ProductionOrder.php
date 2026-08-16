<?php
// app/Models/ProductionOrder.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class ProductionOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'location_id',
        'production_number',
        'status',
        'scheduled_date',
        'started_at',
        'completed_at',
        'cancelled_at',
        'total_input_quantity',
        'total_output_quantity',
        'total_input_cost',
        'total_output_cost',
        'total_cost',
        'estimated_cost',
        'created_by',
        'started_by',
        'completed_by',
        'cancelled_by',
        'notes',
        'payment_method_id',
        'payment_transaction_ref',
    ];

    protected $casts = [
        'total_input_quantity' => 'decimal:4',
        'total_output_quantity' => 'decimal:4',
        'total_input_cost' => 'integer',
        'total_output_cost' => 'integer',
        'total_cost' => 'integer',
        'estimated_cost' => 'integer',
        'scheduled_date' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    // ============================================================
    // ACCESSORS & MUTATORS - Money Fields
    // ============================================================
    
    // ─── Total Input Cost ──────────────────────────────────────
    public function getTotalInputCostAttribute($value): float
    {
        return from_base_currency($value);
    }

    public function setTotalInputCostAttribute($value): void
    {
        $this->attributes['total_input_cost'] = to_base_currency($value);
    }

    // ─── Total Output Cost ─────────────────────────────────────
    public function getTotalOutputCostAttribute($value): float
    {
        return from_base_currency($value);
    }

    public function setTotalOutputCostAttribute($value): void
    {
        $this->attributes['total_output_cost'] = to_base_currency($value);
    }

    // ─── Total Cost ────────────────────────────────────────────
    public function getTotalCostAttribute($value): float
    {
        return from_base_currency($value);
    }

    public function setTotalCostAttribute($value): void
    {
        $this->attributes['total_cost'] = to_base_currency($value);
    }

    // ─── Estimated Cost ────────────────────────────────────────
    public function getEstimatedCostAttribute($value): float
    {
        return from_base_currency($value);
    }

    public function setEstimatedCostAttribute($value): void
    {
        $this->attributes['estimated_cost'] = to_base_currency($value);
    }


    // ─── Relationships ──────────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function inputs(): HasMany
    {
        return $this->hasMany(ProductionOrderInput::class);
    }

    public function outputs(): HasMany
    {
        return $this->hasMany(ProductionOrderOutput::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function startedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    // ─── Scopes ──────────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeByTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────

    public static function generateProductionNumber($tenantId): string
    {
        $prefix = 'PRD';
        $year = date('Y');
        
        $maxSequence = self::where('tenant_id', $tenantId)
            ->where('production_number', 'like', $prefix . '-' . $year . '-%')
            ->max(DB::raw('CAST(SUBSTRING_INDEX(production_number, "-", -1) AS UNSIGNED)'));
        
        $sequence = ($maxSequence ?? 0) + 1;
        return $prefix . '-' . $year . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    public function getStatusBadgeAttribute(): string
    {
        return [
            self::STATUS_DRAFT => 'secondary',
            self::STATUS_IN_PROGRESS => 'warning',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_CANCELLED => 'danger',
        ][$this->status] ?? 'secondary';
    }

    public function getStatusLabelAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->status));
    }

    public function canStart(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function canComplete(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function canCancel(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_IN_PROGRESS]);
    }

    // ─── Business Logic ──────────────────────────────────────────────────

    /**
     * Start production - consumes raw materials from overal_quantity_at_hand
     */
    public function start(): self
    {
        if (!$this->canStart()) {
            throw new \Exception("Cannot start production order with status: {$this->status}");
        }

        DB::transaction(function () {
            // ✅ VALIDATE: All inputs have sufficient stock
            $this->validateInputStock();

            // ✅ CONSUME: All inputs (deplete overal_quantity_at_hand)
            foreach ($this->inputs as $input) {
                $this->consumeRawMaterial($input);
            }

            $this->update([
                'status' => self::STATUS_IN_PROGRESS,
                'started_at' => now(),
                'started_by' => auth()->id(),
            ]);
        });

        return $this;
    }

    /**
     * Start production with payment withdrawal
     */
    public function startWithPayment($paymentMethodId, $withdrawalAmount, $notes = null): self
    {
        if (!$this->canStart()) {
            throw new \Exception("Cannot start production order with status: {$this->status}");
        }

        DB::transaction(function () use ($paymentMethodId, $withdrawalAmount, $notes) {
            // ✅ VALIDATE: All inputs have sufficient stock
            $this->validateInputStock();

            // ✅ Record payment withdrawal if amount > 0
            if ($withdrawalAmount > 0) {
                $paymentMethod = PaymentMethod::find($paymentMethodId);
                if (!$paymentMethod) {
                    throw new \Exception("Payment method not found");
                }

                $transactionData = [
                    'user_id' => auth()->id(),
                    'tenant_id' => $this->tenant_id,
                    'payment_method_id' => $paymentMethod->id,
                    'transaction_type' => 'WITHDRAWAL',
                    'transaction_category' => 'PRODUCTION',
                    'amount' => $withdrawalAmount,
                    'currency_id' => $paymentMethod->currency_id ?? Currency::default()->id,
                    'reference_table' => 'production_orders',
                    'reference_id' => $this->id,
                    'description' => 'Production Cost Withdrawal - #' . $this->production_number,
                    'notes' => 'Withdrawal of ' . number_format($withdrawalAmount, 2) . ' for production #' . $this->production_number . 
                            ($notes ? ' - ' . $notes : ''),
                    'metadata' => [
                        'production_number' => $this->production_number,
                        'withdrawal_amount' => $withdrawalAmount,
                        'payment_method' => $paymentMethod->name,
                        'transaction_nature' => 'PRODUCTION_COST_WITHDRAWAL',
                        'processed_by_id' => auth()->id(),
                        'processed_by_name' => auth()->user()->name,
                    ],
                ];

                $transactionLog = app('payment-transaction')->recordTransaction($transactionData);

                $paymentMethod->current_balance -= $withdrawalAmount;
                $paymentMethod->save();

                $this->payment_method_id = $paymentMethod->id;
                $this->payment_transaction_ref = $transactionLog->transaction_ref ?? null;
            }

            // ✅ CONSUME: All inputs (deplete overal_quantity_at_hand)
            foreach ($this->inputs as $input) {
                $this->consumeRawMaterial($input);
            }

            $this->update([
                'status' => self::STATUS_IN_PROGRESS,
                'started_at' => now(),
                'started_by' => auth()->id(),
            ]);
        });

        return $this;
    }

    

    /**
     * Complete production - produces finished goods
     */
    public function complete(): self
    {
        if (!$this->canComplete()) {
            throw new \Exception("Cannot complete production order with status: {$this->status}");
        }

        DB::transaction(function () {
            // ✅ PRODUCE: All outputs (increase inventory based on strategy)
            // ✅ Only produce outputs that have actual_quantity > 0
            foreach ($this->outputs as $output) {
                if ($output->actual_quantity > 0) {
                    $this->produceFinishedGood($output);
                }
            }

            // ✅ Calculate totals (including outputs with 0 quantity)
            $totalInputQuantity = $this->inputs->sum('actual_quantity');
            $totalOutputQuantity = $this->outputs->sum('actual_quantity');
            $totalInputCost = $this->inputs->sum('actual_cost');
            $totalOutputCost = $this->outputs->sum('production_cost');
            $totalCost = $totalInputCost + $totalOutputCost;

            $this->update([
                'status' => self::STATUS_COMPLETED,
                'completed_at' => now(),
                'completed_by' => auth()->id(),
                'total_input_quantity' => $totalInputQuantity,
                'total_output_quantity' => $totalOutputQuantity,
                'total_input_cost' => $totalInputCost,
                'total_output_cost' => $totalOutputCost,
                'total_cost' => $totalCost,
            ]);

            // ✅ Record accounting if cost > 0
            if ($totalCost > 0) {
                $this->recordAccountingTransaction($totalCost);
            }
        });

        return $this;
    }

private function produceFinishedGood(ProductionOrderOutput $output): void
{
    $variant = $output->productVariant;
    if (!$variant) return;

    $quantity = $output->actual_quantity;
    $strategy = $output->inventory_strategy ?? 'quantity';

    if ($strategy === 'batch') {
        $batchNumber = $this->production_number . '-' . $variant->id . '-' . date('Ymd');
        $output->batch_number = $batchNumber;
        $output->save();

        // ✅ production_cost is already TOTAL cost, so use it directly
        $unitCost = $output->production_cost / max(1, $quantity); // Calculate per-unit for batch

        $batch = PurchaseReceiptItem::create([
            'purchase_receipt_id' => null,
            'purchase_order_item_id' => null,
            'quantity_received' => $quantity,
            'quantity_remaining' => $quantity,
            'unit_cost' => $unitCost, // ✅ Per-unit cost
            'batch_number' => $batchNumber,
            'expiry_date' => null,
            'location_id' => $this->location_id,
            'tenant_id' => $this->tenant_id,
            'notes' => "Produced from production #{$this->production_number}",
        ]);

        BatchLog::create([
            'batch_id' => $batch->id,
            'batch_number' => $batch->batch_number,
            'variant_id' => $variant->id,
            'variant_name' => $variant->name,
            'variant_sku' => $variant->sku,
            'type' => 'produced',
            'quantity_change' => $quantity,
            'quantity_before' => 0,
            'quantity_after' => $quantity,
            'unit_cost' => $unitCost,
            'total_cost' => $output->production_cost, // ✅ TOTAL cost
            'production_order_id' => $this->id,
            'production_order_output_id' => $output->id,
            'tenant_id' => $this->tenant_id,
            'location_id' => $this->location_id,
            'event_date' => now(),
            'performed_by' => auth()->id(),
        ]);
    } 
    elseif ($strategy === 'quantity' || $strategy === 'serial') {
            $before = $variant->overal_quantity_at_hand ?? 0;
            $after = $before + $quantity;
            
            $variant->overal_quantity_at_hand = $after;
            $variant->save();

            // ✅ Log inventory movement (audit trail)
            SingleShopInventoryLog::create([
                'variant_id' => $variant->id,
                'order_id' => $this->id,
                'tenant_id' => $this->tenant_id,
                'created_by' => auth()->id(),
                'quantity_before' => $before,
                'quantity_after' => $after,
                'quantity_change' => $quantity,
                'reason' => 'production_output',
                'notes' => "Produced from production #{$this->production_number} - {$variant->name}",
                'source' => 'production',
                'metadata' => [
                    'output_id' => $output->id,
                    'production_number' => $this->production_number,
                ],
            ]);

            // ✅ If serial strategy, create serial numbers
            if ($strategy === 'serial') {
                for ($i = 0; $i < $quantity; $i++) {
                    $serialNumber = SerialNumber::generateSerialNumber($variant->id);
                    SerialNumber::create([
                        'variant_id' => $variant->id,
                        'tenant_id' => $this->tenant_id,
                        'serial_number' => $serialNumber,
                        'status' => SerialNumber::STATUS_AVAILABLE,
                        'location_id' => $this->location_id,
                        'production_order_id' => $this->id,
                        'production_order_output_id' => $output->id,
                        'created_by' => auth()->id(),
                        'notes' => "Produced from production #{$this->production_number}",
                    ]);
                }
            }
        }
    }

    /**
     * Cancel production - reverses consumption
     */
    public function cancel(): self
    {
        if (!$this->canCancel()) {
            throw new \Exception("Cannot cancel production order with status: {$this->status}");
        }

        DB::transaction(function () {
            if ($this->status === self::STATUS_IN_PROGRESS) {
                foreach ($this->inputs as $input) {
                    $this->restoreRawMaterial($input);
                }
            }

            $this->update([
                'status' => self::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'cancelled_by' => auth()->id(),
            ]);
        });

        return $this;
    }

    // ─── Private Methods ──────────────────────────────────────────────────

    /**
     * ✅ VALIDATE: All inputs have sufficient stock in overal_quantity_at_hand
     */
    private function validateInputStock(): void
    {
        foreach ($this->inputs as $input) {
            $variant = $input->productVariant;
            if (!$variant) continue;

            $product = $variant->product;
            $strategy = $product?->resolvedInventoryStrategy() ?? 'quantity';
            $quantityNeeded = $input->planned_quantity;

            // ✅ For BATCH strategy: Check batch quantity_remaining
            if ($strategy === 'batch') {
                if ($input->purchase_receipt_item_id) {
                    $batch = PurchaseReceiptItem::find($input->purchase_receipt_item_id);
                    if (!$batch) {
                        throw new \Exception("Batch not found for {$variant->name}");
                    }
                    
                    $available = $batch->quantity_remaining ?? $batch->quantity_received ?? 0;
                    if ($available < $quantityNeeded) {
                        throw new \Exception("Insufficient batch stock for {$variant->name}. Available: {$available}, Required: {$quantityNeeded}");
                    }
                } else {
                    $available = $variant->batches()
                        ->where(function($q) {
                            $q->where('quantity_remaining', '>', 0)
                              ->orWhereNull('quantity_remaining');
                        })
                        ->sum('quantity_remaining');
                    
                    if ($available < $quantityNeeded) {
                        throw new \Exception("Insufficient batch stock for {$variant->name}. Available: {$available}, Required: {$quantityNeeded}");
                    }
                }
            }
            // ✅ For QUANTITY strategy: Check overal_quantity_at_hand
            elseif ($strategy === 'quantity') {
                $available = $variant->overal_quantity_at_hand ?? 0;
                if ($available < $quantityNeeded) {
                    throw new \Exception("Insufficient stock for {$variant->name}. Available: {$available}, Required: {$quantityNeeded}");
                }
            }
            // ✅ For SERIAL strategy: Check available serials
            elseif ($strategy === 'serial') {
                $available = SerialNumber::where('variant_id', $variant->id)
                    ->where('status', SerialNumber::STATUS_AVAILABLE)
                    ->where('tenant_id', $this->tenant_id)
                    ->count();
                
                if ($available < $quantityNeeded) {
                    throw new \Exception("Insufficient serial numbers for {$variant->name}. Available: {$available}, Required: {$quantityNeeded}");
                }
            }
        }
    }

    /**
     * ✅ CONSUME: Deplete overal_quantity_at_hand (master stock)
     */
    private function consumeRawMaterial(ProductionOrderInput $input): void
    {
        $variant = $input->productVariant;
        if (!$variant) return;

        $product = $variant->product;
        $strategy = $product?->resolvedInventoryStrategy() ?? 'quantity';
        $quantity = $input->planned_quantity;

        \Log::info('[Production] Consuming raw material', [
            'variant' => $variant->name,
            'strategy' => $strategy,
            'quantity' => $quantity,
            'current_stock' => $variant->overal_quantity_at_hand,
        ]);

        // Update input with actual quantity
        $input->update([
            'actual_quantity' => $quantity,
            'actual_cost' => $input->estimated_cost,
        ]);

        // ✅ BATCH STRATEGY: Reduce batch quantity_remaining
        if ($strategy === 'batch' && $input->purchase_receipt_item_id) {
            $batch = PurchaseReceiptItem::find($input->purchase_receipt_item_id);
            if ($batch) {
                $before = $batch->quantity_remaining ?? $batch->quantity_received ?? 0;
                $after = max(0, $before - $quantity);
                
                $batch->quantity_remaining = $after;
                $batch->save();

                // ✅ Log batch consumption
                BatchLog::create([
                    'batch_id' => $batch->id,
                    'batch_number' => $batch->batch_number,
                    'variant_id' => $variant->id,
                    'variant_name' => $variant->name,
                    'variant_sku' => $variant->sku,
                    'type' => 'consumed',
                    'quantity_change' => -$quantity,
                    'quantity_before' => $before,
                    'quantity_after' => $after,
                    'unit_cost' => $batch->unit_cost ?? 0,
                    'total_cost' => ($batch->unit_cost ?? 0) * $quantity,
                    'production_order_id' => $this->id,
                    'production_order_input_id' => $input->id,
                    'tenant_id' => $this->tenant_id,
                    'location_id' => $this->location_id,
                    'expiry_date' => $batch->expiry_date,
                    'event_date' => now(),
                    'performed_by' => auth()->id(),
                ]);
            }
        }
        // ✅ QUANTITY STRATEGY: Reduce overal_quantity_at_hand
        elseif ($strategy === 'quantity') {
            $before = $variant->overal_quantity_at_hand ?? 0;
            $after = max(0, $before - $quantity);
            
            $variant->overal_quantity_at_hand = $after;
            $variant->save();

            // ✅ Log inventory movement (audit trail)
            SingleShopInventoryLog::create([
                'variant_id' => $variant->id,
                'order_id' => $this->id,
                'tenant_id' => $this->tenant_id,
                'created_by' => auth()->id(),
                'quantity_before' => $before,
                'quantity_after' => $after,
                'quantity_change' => -$quantity,
                'reason' => 'production_consumption',
                'notes' => "Consumed in production #{$this->production_number} - {$variant->name}",
                'source' => 'production',
                'metadata' => [
                    'input_id' => $input->id,
                    'production_number' => $this->production_number,
                ],
            ]);
        }
        // ✅ SERIAL STRATEGY: Mark serials as reserved
        elseif ($strategy === 'serial') {
            $serials = SerialNumber::where('variant_id', $variant->id)
                ->where('status', SerialNumber::STATUS_AVAILABLE)
                ->where('tenant_id', $this->tenant_id)
                ->limit($quantity)
                ->get();

            foreach ($serials as $serial) {
                $serial->update([
                    'status' => SerialNumber::STATUS_RESERVED,
                    'production_order_id' => $this->id,
                    'notes' => "Consumed in production #{$this->production_number}",
                ]);
            }
        }
    }


    /**
     * Restore raw material (reverse consumption for cancellation)
     */
    private function restoreRawMaterial(ProductionOrderInput $input): void
    {
        $variant = $input->productVariant;
        if (!$variant) return;

        $product = $variant->product;
        $strategy = $product?->resolvedInventoryStrategy() ?? 'quantity';
        $quantity = $input->actual_quantity;

        if ($strategy === 'batch' && $input->purchase_receipt_item_id) {
            $batch = PurchaseReceiptItem::find($input->purchase_receipt_item_id);
            if ($batch) {
                $batch->quantity_remaining += $quantity;
                $batch->save();
            }
        } elseif ($strategy === 'quantity') {
            $variant->overal_quantity_at_hand += $quantity;
            $variant->save();
        } elseif ($strategy === 'serial') {
            SerialNumber::where('variant_id', $variant->id)
                ->where('production_order_id', $this->id)
                ->where('status', SerialNumber::STATUS_RESERVED)
                ->update([
                    'status' => SerialNumber::STATUS_AVAILABLE,
                    'production_order_id' => null,
                    'notes' => null,
                ]);
        }
    }

    /**
     * Record accounting transaction
     */
    private function recordAccountingTransaction($totalCost): void
    {
        try {
            $paymentMethod = PaymentMethod::where('tenant_id', $this->tenant_id)
                ->where('type', 'cash')
                ->first();

            if ($paymentMethod) {
                $transactionData = [
                    'tenant_id' => $this->tenant_id,
                    'user_id' => auth()->id(),
                    'payment_method_id' => $paymentMethod->id,
                    'transaction_type' => 'ADJUSTMENT',
                    'transaction_category' => 'PRODUCTION',
                    'amount' => $totalCost,
                    'currency_id' => $paymentMethod->currency_id ?? Currency::default()->id,
                    'reference_table' => 'production_orders',
                    'reference_id' => $this->id,
                    'description' => 'Production Cost - #' . $this->production_number,
                    'notes' => 'Cost of production',
                    'metadata' => [
                        'production_number' => $this->production_number,
                        'total_input_cost' => $this->total_input_cost,
                        'total_output_cost' => $this->total_output_cost,
                        'transaction_nature' => 'PRODUCTION_COST',
                    ],
                ];

                app('payment-transaction')->recordTransaction($transactionData);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to record production accounting: ' . $e->getMessage());
        }
    }
}