<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class TenantSetting extends Model
{
    use HasFactory;

    protected $primaryKey = 'setting_id';

    protected $fillable = [
        'tenant_id',
        'setting_key',
        'setting_value',
        'data_type',
        'category',
        'updated_by',
    ];

    protected $casts = [
        'updated_at' => 'datetime',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::saved(function ($setting) {
            // Clear cache when settings are updated
            Cache::forget("tenant_settings_{$setting->tenant_id}");
        });

        static::deleted(function ($setting) {
            // Clear cache when settings are deleted
            Cache::forget("tenant_settings_{$setting->tenant_id}");
        });
    }

    /**
     * Get the tenant that owns the settings.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Get the user who last updated the settings.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get setting value with proper casting
     */
    public function getValueAttribute()
    {
        return match($this->data_type) {
            'integer' => (int) $this->setting_value,
            'boolean' => (bool) $this->setting_value,
            'json' => json_decode($this->setting_value, true),
            'datetime' => $this->setting_value ? \Carbon\Carbon::parse($this->setting_value) : null,
            default => $this->setting_value,
        };
    }

    /**
     * Set setting value with proper serialization
     */
    public function setValueAttribute($value): void
    {
        $this->attributes['setting_value'] = match($this->data_type) {
            'boolean' => $value ? '1' : '0',
            'json' => json_encode($value),
            'datetime' => $value instanceof \Carbon\Carbon ? $value->toDateTimeString() : $value,
            default => (string) $value,
        };
    }

    /**
     * Get all settings for a tenant as a key-value array with caching
     */
    public static function getAllSettingsForTenant($tenantId): array
    {
        return Cache::remember("tenant_settings_{$tenantId}", 3600, function () use ($tenantId) {
            return static::where('tenant_id', $tenantId)
                ->get()
                ->mapWithKeys(function ($setting) {
                    return [$setting->setting_key => $setting->value];
                })
                ->toArray();
        });
    }

    /**
     * Scope for a specific category
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for specific keys
     */
    public function scopeKeys($query, array $keys)
    {
        return $query->whereIn('setting_key', $keys);
    }
}