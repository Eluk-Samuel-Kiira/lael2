<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SerialNumber extends Model
{
    use HasFactory;

    const STATUS_AVAILABLE = 'available';
    const STATUS_SOLD = 'sold';
    const STATUS_RESERVED = 'reserved';
    const STATUS_RETURNED = 'returned';
    const STATUS_LOST = 'lost';
    const STATUS_DAMAGED = 'damaged';

    protected $fillable = [
        'variant_id',  // ✅ This is the correct column name
        'tenant_id',
        'serial_number',
        'status',
        'order_id',
        'sold_at',
        'sold_by',
        'location_id',
        'department_id',
        'purchase_order_id',
        'purchase_receipt_id',
        'batch_id',
        'expiry_date',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'sold_at' => 'datetime',
        'expiry_date' => 'date',
    ];

    // ─── Relationships ──────────────────────────────────────

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id'); // ✅ Explicit foreign key
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function purchaseReceipt(): BelongsTo
    {
        return $this->belongsTo(PurchaseReceipt::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(PurchaseReceiptItem::class, 'batch_id');
    }

    public function soldBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sold_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Accessors ──────────────────────────────────────────

    public function getLocationNameAttribute()
    {
        return $this->location ? $this->location->name : 'N/A';
    }

    public function getDepartmentNameAttribute()
    {
        return $this->department ? $this->department->name : 'N/A';
    }

    public function getStatusLabelAttribute()
    {
        return [
            self::STATUS_AVAILABLE => 'Available',
            self::STATUS_SOLD => 'Sold',
            self::STATUS_RESERVED => 'Reserved',
            self::STATUS_RETURNED => 'Returned',
            self::STATUS_LOST => 'Lost',
            self::STATUS_DAMAGED => 'Damaged',
        ][$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute()
    {
        return [
            self::STATUS_AVAILABLE => 'success',
            self::STATUS_SOLD => 'danger',
            self::STATUS_RESERVED => 'warning',
            self::STATUS_RETURNED => 'info',
            self::STATUS_LOST => 'secondary',
            self::STATUS_DAMAGED => 'dark',
        ][$this->status] ?? 'secondary';
    }

    // ─── Scopes ─────────────────────────────────────────────

    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_AVAILABLE);
    }

    public function scopeSold($query)
    {
        return $query->where('status', self::STATUS_SOLD);
    }

    public function scopeByVariant($query, $variantId)
    {
        return $query->where('variant_id', $variantId);
    }

    public function scopeByTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    // ─── Helper Methods ────────────────────────────────────

    public static function generateSerialNumber($variantId, $prefix = null)
    {
        $variant = ProductVariant::find($variantId);
        $prefix = $prefix ?? ($variant ? strtoupper(substr($variant->sku, 0, 4)) : 'SN');
        $year = date('Y');
        $random = strtoupper(Str::random(6));
        $unique = uniqid();
        
        return "{$prefix}-{$year}-{$random}-{$unique}";
    }
}