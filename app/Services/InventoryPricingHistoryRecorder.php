<?php

namespace App\Services;

use App\Models\InventoryItems;
use App\Models\InventoryPricingHistory;
use Illuminate\Support\Facades\Auth;

class InventoryPricingHistoryRecorder
{
    /**
     * Fields tracked on inventory_items that carry pricing / override data.
     * Anything not in this list is ignored by the recorder.
     */
    protected const TRACKED_FIELDS = [
        'has_custom_pricing',
        'supplier_cost_price',
        'total_shipping_cost',
        'ura_taxes_applied',
        'additional_expenses',
        'grand_total_cost_price',
        'selling_price',
        'discount_selling_price',
        'discount_percentage',
        'markup_percentage',
        'pricing_source',
    ];

    /**
     * Record a pricing change on an inventory item.
     *
     * @param  InventoryItems  $item        The inventory item AFTER mutation.
     * @param  array           $oldValues   The tracked fields BEFORE mutation.
     * @param  string          $changeType  One of InventoryPricingHistory::CHANGE_*
     * @param  string|null     $reason      Optional free-text reason.
     */
    public function record(
        $source,
        array $oldValues,
        string $changeType,
        ?string $reason = null
    ): \App\Models\InventoryPricingHistory {
        $user = \Illuminate\Support\Facades\Auth::user();

        $fqcn = is_object($source) ? get_class($source) : '';

        // ── Normalise the source into a common shape ──────────
        if ($fqcn === \App\Models\InventoryItems::class) {
            $variant        = $source->variant;
            $inventoryId    = $source->id;
            $variantId      = $source->variant_id;
            $locationId     = $source->location_id;
            $departmentId   = $source->department_id;
            $tenantId       = $source->tenant_id;
            $scopeLevel     = \App\Models\InventoryPricingHistory::SCOPE_ITEM;
            $locationName   = $source->itemLocation?->name   ?? "Location #{$source->location_id}";
            $departmentName = $source->departmentItem?->name ?? "Department #{$source->department_id}";
            $context        = " at {$locationName} ({$departmentName})";
            $hasAfter       = (bool) ($source->has_custom_pricing ?? false);

        } elseif ($fqcn === \App\Models\ProductVariant::class) {
            $variant        = $source;
            $inventoryId    = null;
            $variantId      = $source->id;
            $locationId     = null;
            $departmentId   = null;
            $tenantId       = $source->tenant_id;
            $scopeLevel     = \App\Models\InventoryPricingHistory::SCOPE_VARIANT;
            $context        = '';
            $hasAfter       = false;

        } else {
            throw new \InvalidArgumentException(
                "Source must be InventoryItems or ProductVariant. Received: {$fqcn}"
            );
        }

        $newValues = $this->snapshot($source);

        // ── Diff ─────────────────────────────────────────────
        $changedFields = [];
        foreach (self::TRACKED_FIELDS as $field) {
            $old = $oldValues[$field] ?? null;
            $new = $newValues[$field] ?? null;
            if ($this->hasChanged($old, $new)) {
                $changedFields[] = $field;
            }
        }

        if ($changeType === \App\Models\InventoryPricingHistory::CHANGE_DISABLED) {
            $changedFields = array_unique(array_merge($changedFields, ['has_custom_pricing']));
        }

        $hadBefore = (bool) ($oldValues['has_custom_pricing'] ?? false);

        $narrative = $this->buildNarrative(
            $variant, $user, $changeType,
            $hadBefore, $hasAfter, $oldValues, $newValues,
            $changedFields, $reason, $context, $scopeLevel
        );

        return \App\Models\InventoryPricingHistory::create([
            'tenant_id'                 => $tenantId,
            'inventory_id'              => $inventoryId,
            'variant_id'                => $variantId,
            'location_id'               => $locationId,
            'department_id'             => $departmentId,
            'scope_level'               => $scopeLevel,
            'change_type'               => $changeType,
            'had_custom_pricing_before' => $hadBefore,
            'has_custom_pricing_after'  => $hasAfter,
            'old_values'                => $oldValues,
            'new_values'                => $newValues,
            'changed_fields'            => array_values($changedFields),
            'narrative'                 => $narrative,
            'changed_by'                => $user?->id,
            'changed_by_name'           => $user?->name,
            'changed_by_email'          => $user?->email,
            'reason'                    => $reason,
            'changed_at'                => now(),
        ]);
    }

    /**
     * Capture the tracked pricing fields from an InventoryItems instance.
     */
    public function snapshot($source): array
    {
        $out = [];
        foreach (self::TRACKED_FIELDS as $field) {
            $out[$field] = $source->{$field} ?? null;
        }
        return $out;
    }

    protected function hasChanged($old, $new): bool
    {
        // Normalise booleans
        if (is_bool($old) || is_bool($new)) {
            return (bool) $old !== (bool) $new;
        }
        // Normalise null vs 0
        if ($old === null && ($new === null || $new === 0 || $new === '0' || $new === '')) {
            return false;
        }
        if ($new === null && ($old === null || $old === 0 || $old === '0' || $old === '')) {
            return false;
        }
        // Numeric compare
        if (is_numeric($old) && is_numeric($new)) {
            return abs((float) $old - (float) $new) > 0.0001;
        }
        // String compare
        return (string) $old !== (string) $new;
    }

    protected function buildNarrative(
        $variant,
        $user,
        string $changeType,
        bool $hadBefore,
        bool $hasAfter,
        array $oldValues,
        array $newValues,
        array $changedFields,
        ?string $reason,
        string $context,
        string $scopeLevel
    ): string {
        $actor       = $user ? "{$user->name} ({$user->email})" : 'System';
        $variantName = $variant?->name ?? 'Unknown Item';
        $variantSku  = $variant?->sku  ?? 'N/A';

        // Scope-aware subject: "the variant" vs "the variant at Main Store (Sales)"
        $subject = $scopeLevel === InventoryPricingHistory::SCOPE_VARIANT
            ? "the base pricing of \"{$variantName}\" (SKU {$variantSku})\""
            : "\"{$variantName}\" (SKU {$variantSku}){$context}";

        $diffs = [];
        foreach ($changedFields as $field) {
            if ($field === 'has_custom_pricing') continue;
            $diffs[] = sprintf(
                '%s: %s → %s',
                $field,
                $this->fmt($oldValues[$field] ?? null),
                $this->fmt($newValues[$field] ?? null)
            );
        }
        $diffStr   = $diffs ? ' Changes: ' . implode('; ', $diffs) . '.' : '';
        $reasonStr = $reason ? " Reason: {$reason}." : '';

        $verbPrefix = $scopeLevel === InventoryPricingHistory::SCOPE_VARIANT
            ? 'base pricing'
            : 'custom pricing';

        return match ($changeType) {
            InventoryPricingHistory::CHANGE_ENABLED => sprintf(
                '%s enabled %s on %s — this item no longer inherits price from the variant. Selling price set to %s.%s%s',
                $actor, $verbPrefix, $subject,
                $this->fmt($newValues['selling_price'] ?? null),
                $diffStr, $reasonStr
            ),
            InventoryPricingHistory::CHANGE_DISABLED => sprintf(
                '%s disabled %s on %s — the item now inherits price from its variant again. All override values were cleared.%s',
                $actor, $verbPrefix, $subject, $reasonStr
            ),
            InventoryPricingHistory::CHANGE_UPDATED => sprintf(
                '%s updated %s on %s.%s%s',
                $actor, $verbPrefix, $subject, $diffStr, $reasonStr
            ),
            default => sprintf(
                '%s made an automatic %s change on %s.%s%s',
                $actor, $verbPrefix, $subject, $diffStr, $reasonStr
            ),
        };
    }

    protected function fmt($value): string
    {
        if ($value === null || $value === '') return 'null';
        if (is_bool($value)) return $value ? 'yes' : 'no';
        if (is_numeric($value)) return number_format((float) $value, 2);
        return (string) $value;
    }
}