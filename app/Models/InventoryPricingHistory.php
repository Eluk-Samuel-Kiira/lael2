<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTenant;

class InventoryPricingHistory extends Model
{
    use HasTenant;

    protected $table = 'inventory_pricing_history';

    // ── Change-type constants ─────────────────────────────────
    const CHANGE_ENABLED  = 'enabled';   // turned custom pricing ON
    const CHANGE_DISABLED = 'disabled';  // turned custom pricing OFF
    const CHANGE_UPDATED  = 'updated';   // changed values while already ON
    const CHANGE_AUTO     = 'auto';      // system/bulk operation

    const CHANGE_LABELS = [
        self::CHANGE_ENABLED  => 'Custom pricing enabled',
        self::CHANGE_DISABLED => 'Custom pricing disabled',
        self::CHANGE_UPDATED  => 'Custom pricing updated',
        self::CHANGE_AUTO     => 'Automatic change',
    ];

    protected $fillable = [
        'tenant_id',
        'inventory_id',
        'variant_id',
        'location_id',
        'department_id',
        'change_type',
        'had_custom_pricing_before',
        'has_custom_pricing_after',
        'old_values',
        'new_values',
        'changed_fields',
        'narrative',
        'changed_by',
        'changed_by_name',
        'changed_by_email',
        'reason',
        'changed_at',
        'scope_level',
    ];

    protected $casts = [
        'had_custom_pricing_before' => 'boolean',
        'has_custom_pricing_after'  => 'boolean',
        'old_values'                => 'array',
        'new_values'                => 'array',
        'changed_fields'            => 'array',
        'changed_at'                => 'datetime',
    ];

    const SCOPE_VARIANT = 'variant';
    const SCOPE_ITEM    = 'item';
    const SCOPE_SERIAL  = 'serial';

    // ============================================================
    // RELATIONSHIPS
    // ============================================================

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItems::class, 'inventory_id');
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    // ============================================================
    // HELPERS
    // ============================================================

    public function getChangeLabelAttribute(): string
    {
        return self::CHANGE_LABELS[$this->change_type] ?? $this->change_type;
    }

    /**
     * Human-readable list of what changed, e.g.
     * ["selling_price: 100.00 → 120.00", "discount_percentage: 0 → 10"]
     */
    public function getChangeSummaryAttribute(): array
    {
        $changes = [];
        $old = $this->old_values ?? [];
        $new = $this->new_values ?? [];

        foreach (($this->changed_fields ?? []) as $field) {
            $from = $old[$field] ?? null;
            $to   = $new[$field] ?? null;

            $changes[] = sprintf(
                '%s: %s → %s',
                $field,
                $from === null ? 'null' : (is_numeric($from) ? number_format((float) $from, 2) : $from),
                $to   === null ? 'null' : (is_numeric($to)   ? number_format((float) $to, 2)   : $to)
            );
        }

        return $changes;
    }

    // ============================================================
    // SCOPES
    // ============================================================

    public function scopeByTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByVariant($query, $variantId)
    {
        return $query->where('variant_id', $variantId);
    }

    public function scopeByInventory($query, $inventoryId)
    {
        return $query->where('inventory_id', $inventoryId);
    }

    public function scopeByActor($query, $userId)
    {
        return $query->where('changed_by', $userId);
    }

    public function scopeRecentFirst($query)
    {
        return $query->orderByDesc('changed_at')->orderByDesc('id');
    }
}