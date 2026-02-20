<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'supplier_id',
        'location_id',
        'po_number',
        'status',
        'expected_delivery_date',
        'subtotal',
        'tax_total',
        'total',
        'notes',
        'created_by',
        
        // Status tracking fields
        'submitted_at',
        'submitted_by',
        'approved_at', 
        'approved_by',
        'sent_at',
        'sent_by',
        'cancelled_at',
        'cancelled_by',
        'received_at',
        'received_by',
    ];

    protected $casts = [
        'expected_delivery_date' => 'date',
        // Money fields - stored as integers in DB
        'subtotal' => 'integer',
        'tax_total' => 'integer',
        'total' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'sent_at' => 'datetime',
        'received_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Accessors - Convert from stored integer to display float
     */
    public function getSubtotalAttribute(?int $value): ?float
    {
        return from_base_currency($value);
    }

    public function getTaxTotalAttribute(?int $value): ?float
    {
        return from_base_currency($value);
    }

    public function getTotalAttribute(?int $value): ?float
    {
        return from_base_currency($value);
    }

    /**
     * Mutators - Convert from display float to stored integer
     */
    public function setSubtotalAttribute($value): void
    {
        $this->attributes['subtotal'] = to_base_currency($value);
    }

    public function setTaxTotalAttribute($value): void
    {
        $this->attributes['tax_total'] = to_base_currency($value);
    }

    public function setTotalAttribute($value): void
    {
        $this->attributes['total'] = to_base_currency($value);
    }


    





    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(PurchaseReceipt::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function sentBy()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }
    

    // 👇 Accessors for timestamp fields
    public function getSubmittedAtAttribute($value)
    {
        return $value ? Carbon::parse($value) : null;
    }

    public function getApprovedAtAttribute($value)
    {
        return $value ? Carbon::parse($value) : null;
    }

    public function getSentAtAttribute($value)
    {
        return $value ? Carbon::parse($value) : null;
    }

    public function getReceivedAtAttribute($value)
    {
        return $value ? Carbon::parse($value) : null;
    }

    public function getCancelledAtAttribute($value)
    {
        return $value ? Carbon::parse($value) : null;
    }


    public function getPaymentMethodsUsedAttribute()
    {
        return $this->items()
            ->with('paymentMethod')
            ->get()
            ->pluck('paymentMethod')
            ->unique('id')
            ->filter();
    }

    public function getPaymentSummaryAttribute()
    {
        $summary = [
            'total_items' => $this->items()->count(),
            'total_amount' => $this->items()->sum('total_cost'),
            'paid_amount' => $this->items()->where('payment_status', PurchaseOrderItem::PAYMENT_STATUS_PAID)->sum('total_cost'),
            'pending_amount' => $this->items()->where('payment_status', PurchaseOrderItem::PAYMENT_STATUS_PENDING)->sum('total_cost'),
            'partial_amount' => $this->items()->where('payment_status', PurchaseOrderItem::PAYMENT_STATUS_PARTIAL)->sum('total_cost'),
        ];
        
        $summary['payment_progress'] = $summary['total_amount'] > 0 
            ? round(($summary['paid_amount'] / $summary['total_amount']) * 100, 2)
            : 0;
            
        return $summary;
    }

    public function getOverallPaymentStatusAttribute()
    {
        $items = $this->items;
        
        if ($items->isEmpty()) {
            return PurchaseOrderItem::PAYMENT_STATUS_PENDING;
        }
        
        $paidCount = $items->where('payment_status', PurchaseOrderItem::PAYMENT_STATUS_PAID)->count();
        $partialCount = $items->where('payment_status', PurchaseOrderItem::PAYMENT_STATUS_PARTIAL)->count();
        $pendingCount = $items->where('payment_status', PurchaseOrderItem::PAYMENT_STATUS_PENDING)->count();
        $overdueCount = $items->where('payment_status', PurchaseOrderItem::PAYMENT_STATUS_OVERDUE)->count();
        
        if ($overdueCount > 0) {
            return PurchaseOrderItem::PAYMENT_STATUS_OVERDUE;
        }
        
        if ($paidCount === $items->count()) {
            return PurchaseOrderItem::PAYMENT_STATUS_PAID;
        }
        
        if ($partialCount > 0) {
            return PurchaseOrderItem::PAYMENT_STATUS_PARTIAL;
        }
        
        return PurchaseOrderItem::PAYMENT_STATUS_PENDING;
    }

    public function getItemsWithPaymentMethodAttribute(): array
    {
        $items = $this->items()->with('paymentMethod')->get();
        
        // Group items by payment_method_id and payment_date
        $groupedItems = [];
        
        foreach ($items as $item) {
            $key = $item->payment_method_id . '_' . $item->payment_date;
            
            if (!isset($groupedItems[$key])) {
                $groupedItems[$key] = [
                    'method' => $item->paymentMethod,
                    'payment_date' => $item->payment_date,
                    'items' => [],
                    'subtotal' => 0,
                    'tax' => 0,
                    'total' => 0,
                ];
            }
            
            $groupedItems[$key]['items'][] = [
                'id' => $item->id,
                'product_name' => $item->product_name,
                'sku' => $item->sku,
                'quantity' => $item->quantity,
                'received_quantity' => $item->received_quantity,
                'unit_cost' => $item->unit_cost,
                'tax_amount' => $item->tax_amount,
                'total_cost' => $item->total_cost,
                'payment_status' => $item->payment_status,
                'payment_date' => $item->payment_date,
            ];
            
            // Calculate group totals
            $groupedItems[$key]['subtotal'] += ($item->unit_cost * $item->quantity);
            $groupedItems[$key]['tax'] += $item->tax_amount;
            $groupedItems[$key]['total'] += $item->total_cost;
        }
        
        return $groupedItems;
    }

        /**
     * Get items grouped by payment method and payment date
     */
    public function getItemsGroupedByPaymentMethod(): array
    {
        // Check if items are stored as JSON or have a relationship
        if ($this->items && is_string($this->items)) {
            $items = json_decode($this->items, true) ?? [];
        } elseif ($this->relationLoaded('purchaseOrderItems')) {
            // If you're using the PurchaseOrderItem model relationship
            $items = $this->purchaseOrderItems->toArray();
        } else {
            return [];
        }

        $groupedItems = [];

        foreach ($items as $item) {
            // Create a unique key for grouping by payment method and date
            $key = ($item['payment_method_id'] ?? 'none') . '_' . ($item['payment_date'] ?? 'no-date');
            
            if (!isset($groupedItems[$key])) {
                $groupedItems[$key] = [
                    'method' => isset($item['payment_method_id']) ? 
                        \App\Models\PaymentMethod::find($item['payment_method_id']) : null,
                    'payment_date' => $item['payment_date'] ?? null,
                    'items' => [],
                    'subtotal' => 0,
                    'tax' => 0,
                    'total' => 0,
                ];
            }

            $quantity = $item['quantity'] ?? 0;
            $unitCost = $item['unit_cost'] ?? 0;
            $taxAmount = $item['tax_amount'] ?? 0;
            $totalCost = $item['total_cost'] ?? 0;

            $groupedItems[$key]['items'][] = $item;
            $groupedItems[$key]['subtotal'] += ($quantity * $unitCost);
            $groupedItems[$key]['tax'] += $taxAmount;
            $groupedItems[$key]['total'] += $totalCost;
        }

        return $groupedItems;
    }
}