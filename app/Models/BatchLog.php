<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BatchLog extends Model
{
    use HasFactory;

    const TYPE_RECEIVED = 'received';
    const TYPE_DEPLETED = 'depleted';
    const TYPE_ADJUSTED = 'adjusted';
    const TYPE_TRANSFERRED = 'transferred';
    const TYPE_EXPIRED = 'expired';
    const TYPE_ASSIGNED = 'assigned';
    const TYPE_UNASSIGNED = 'unassigned'; 


    protected $fillable = [
        // Batch reference
        'batch_id',
        'batch_number',
        
        // Product details
        'variant_id',
        'variant_name',
        'variant_sku',
        
        // Event details
        'type', // received, depleted, adjusted, transferred, expired
        'quantity_change',
        'quantity_before',
        'quantity_after',
        
        // Cost tracking
        'unit_cost',
        'total_cost',
        
        // Reference links
        'order_id',
        'order_number',
        'purchase_order_id',
        'purchase_order_number',
        'purchase_receipt_id',
        'supplier_id',
        'supplier_name',
        
        // Tenant and location
        'tenant_id',
        'location_id',
        'department_id',
        
        // Expiry and dates
        'expiry_date',
        'event_date',
        
        // User who performed the action
        'performed_by',
        
        // Additional metadata
        'metadata',
    ];

    protected $casts = [
        'quantity_change' => 'integer',
        'quantity_before' => 'integer',
        'quantity_after' => 'integer',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'expiry_date' => 'date',
        'event_date' => 'datetime',
        'metadata' => 'array',
    ];

    // Relationships
    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function batch()
    {
        return $this->belongsTo(PurchaseReceiptItem::class, 'batch_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function purchaseReceipt()
    {
        return $this->belongsTo(PurchaseReceipt::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    // Scopes
    public function scopeByTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeReceived($query)
    {
        return $query->where('type', self::TYPE_RECEIVED);
    }

    public function scopeDepleted($query)
    {
        return $query->where('type', self::TYPE_DEPLETED);
    }

    public function scopeByVariant($query, $variantId)
    {
        return $query->where('variant_id', $variantId);
    }

    public function scopeByBatch($query, $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    public function scopeByBatchNumber($query, $batchNumber)
    {
        return $query->where('batch_number', $batchNumber);
    }

    public function scopeByLocation($query, $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('event_date', [$startDate, $endDate]);
    }

    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', now()->addDays($days))
            ->where('expiry_date', '>=', now());
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now());
    }

    public function getTypeLabelAttribute()
    {
        return [
            self::TYPE_RECEIVED => 'Received',
            self::TYPE_DEPLETED => 'Depleted',
            self::TYPE_ADJUSTED => 'Adjusted',
            self::TYPE_TRANSFERRED => 'Transferred',
            self::TYPE_ASSIGNED => 'Assigned',  // ✅ ADD THIS
            self::TYPE_UNASSIGNED => 'Unassigned',  // ✅ ADD THIS
            self::TYPE_EXPIRED => 'Expired',
        ][$this->type] ?? ucfirst($this->type);
    }

    public function getTypeColorAttribute()
    {
        return [
            self::TYPE_RECEIVED => 'success',
            self::TYPE_DEPLETED => 'danger',
            self::TYPE_ADJUSTED => 'warning',
            self::TYPE_TRANSFERRED => 'info',
            self::TYPE_ASSIGNED => 'primary',  // ✅ ADD THIS - Blue
            self::TYPE_UNASSIGNED => 'secondary',  // ✅ ADD THIS - Gray
            self::TYPE_EXPIRED => 'secondary',
        ][$this->type] ?? 'primary';
    }

    public function getTypeIconAttribute()
    {
        return [
            self::TYPE_RECEIVED => 'fa-arrow-down',
            self::TYPE_DEPLETED => 'fa-arrow-up',
            self::TYPE_ADJUSTED => 'fa-pencil',
            self::TYPE_TRANSFERRED => 'fa-arrow-right',
            self::TYPE_EXPIRED => 'fa-clock',
        ][$this->type] ?? 'fa-circle';
    }

    public function getLocationNameAttribute()
    {
        return $this->location ? $this->location->name : 'N/A';
    }

    public function getDepartmentNameAttribute()
    {
        return $this->department ? $this->department->name : 'N/A';
    }

    public function getFormattedExpiryDateAttribute()
    {
        return $this->expiry_date ? $this->expiry_date->format('Y-m-d') : 'N/A';
    }

    public function getDaysToExpiryAttribute()
    {
        if (!$this->expiry_date) {
            return null;
        }
        return now()->diffInDays($this->expiry_date, false);
    }

    public function getIsExpiredAttribute()
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function getIsExpiringSoonAttribute()
    {
        if (!$this->expiry_date) {
            return false;
        }
        $days = now()->diffInDays($this->expiry_date, false);
        return $days >= 0 && $days <= 30;
    }
}