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
        'recipe_id',
        'production_number',
        'status',
        'scheduled_date',
        'started_at',
        'completed_at',
        'total_input_quantity',
        'total_output_quantity',
        'estimated_cost',
        'actual_cost',
        'created_by',
        'approved_by',
        'started_by',
        'completed_by',
        'cancelled_by',
        'approved_at',
        'cancelled_at',
        'notes',
        'quality_notes',
    ];

    protected $casts = [
        'total_input_quantity' => 'decimal:4',
        'total_output_quantity' => 'decimal:4',
        'estimated_cost' => 'integer',
        'actual_cost' => 'integer',
        'scheduled_date' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'approved_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    // Status Constants
    const STATUS_DRAFT = 'draft';
    const STATUS_PLANNED = 'planned';
    const STATUS_APPROVED = 'approved';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_QUALITY_CHECK = 'quality_check';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
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

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
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

    // Scopes
    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_PLANNED, self::STATUS_APPROVED]);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    // ✅ Business Logic - Start Production
    public function start(): self
    {
        DB::transaction(function () {
            // Update production order status
            $this->update([
                'status' => self::STATUS_IN_PROGRESS,
                'started_at' => now(),
                'started_by' => auth()->id(),
            ]);
        });

        return $this;
    }

    // ✅ Business Logic - Complete Production
    public function complete(): self
    {
        DB::transaction(function () {
            // 1. Process all inputs (consume raw materials)
            foreach ($this->inputs as $input) {
                // Decrease the variant quantity
                $variant = $input->productVariant;
                $variant->decrement('quantity', $input->actual_quantity);

                // Create inventory transaction for input
                $this->createInventoryTransaction(
                    $input->product_variant_id,
                    'production_input',
                    -$input->actual_quantity,
                    $variant->quantity,
                    $input->actual_cost,
                    $input->batch_no
                );
            }

            // 2. Process all outputs (create finished goods)
            foreach ($this->outputs as $output) {
                // ✅ Auto-generate batch number from production number
                $output->batch_no = $this->production_number . '-' . $output->product_variant_id;
                $output->save();

                // Increase the variant quantity
                $variant = $output->productVariant;
                $variant->increment('quantity', $output->actual_quantity);

                // Create inventory transaction for output
                $this->createInventoryTransaction(
                    $output->product_variant_id,
                    'production_output',
                    $output->actual_quantity,
                    $variant->quantity,
                    $output->production_cost,
                    $output->batch_no
                );
            }

            // 3. Update production order
            $this->update([
                'status' => self::STATUS_COMPLETED,
                'completed_at' => now(),
                'completed_by' => auth()->id(),
                'actual_cost' => $this->inputs->sum('actual_cost') + $this->outputs->sum('production_cost'),
            ]);
        });

        return $this;
    }

    // Helper to create inventory transactions
    protected function createInventoryTransaction(
        int $variantId,
        string $type,
        float $quantityChange,
        float $newQuantity,
        int $cost,
        ?string $batchNo = null
    ): void {
        InventoryTransaction::create([
            'tenant_id' => $this->tenant_id,
            'location_id' => $this->location_id,
            'product_variant_id' => $variantId,
            'transaction_type' => $type,
            'reference_type' => 'production_order',
            'reference_id' => $this->id,
            'batch_no' => $batchNo,
            'quantity_change' => $quantityChange,
            'previous_quantity' => $newQuantity - $quantityChange,
            'new_quantity' => $newQuantity,
            'unit_cost' => $cost / max(1, abs($quantityChange)),
            'total_cost' => $cost,
            'created_by' => auth()->id(),
        ]);
    }

    public function cancel(): self
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancelled_by' => auth()->id(),
        ]);

        return $this;
    }
}