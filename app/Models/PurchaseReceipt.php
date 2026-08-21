<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasTenant;


class PurchaseReceipt extends Model
{
    use HasFactory, HasTenant;

    protected $fillable = [
        'purchase_order_id',
        'received_by',
        'received_at',
        'notes',
        'subtotal',
        'tax_total',
        'total',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'subtotal' => 'integer',
        'tax_total' => 'integer',
        'total' => 'integer',
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

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReceiptItem::class);
    }
}