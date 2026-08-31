<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasTenant;


class PurchaseReceiptItem extends Model
{
    use HasFactory, HasTenant;

    protected $fillable = [
        'purchase_receipt_id',
        'purchase_order_item_id',
        'quantity_received',
        'quantity_remaining',
        'location_id',
        'department_id',
        'batch_number',
        'expiry_date',
        'tenant_id',
    ];

    protected $casts = [
        'quantity_received' => 'integer',
        'quantity_remaining' => 'integer',
        'expiry_date' => 'date',
    ];

    public function purchaseReceipt(): BelongsTo
    {
        return $this->belongsTo(PurchaseReceipt::class);
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}