<?php
// app/Models/TenantUsageTracking.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class TenantUsageTracking extends Model
{
    use HasFactory;

    protected $table = 'tenant_usage_tracking';
    protected $primaryKey = 'usage_id';

    protected $fillable = [
        'tenant_id',
        'tracking_date',
        'current_shops',
        'current_locations',
        'current_departments',
        'current_users',
        'current_products',
        'current_customers',
        'current_employees',
        'current_api_keys',
        'current_webhooks',
        'current_integrations',
        'monthly_sales_count',
        'monthly_api_calls',
        'monthly_storage_mb',
        'average_response_time_ms',
        'error_rate_percent',
        'active_sessions',
        'concurrent_users',
        'estimated_cost',
        'resource_utilization_percent',
        'exceeds_plan_limits',
    ];

    protected $casts = [
        'tracking_date' => 'date',
        'monthly_storage_mb' => 'decimal:2',
        'average_response_time_ms' => 'decimal:2',
        'error_rate_percent' => 'decimal:2',
        'estimated_cost' => 'decimal:2',
        'resource_utilization_percent' => 'decimal:2',
        'exceeds_plan_limits' => 'boolean',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    // Scopes
    public function scopeForToday($query)
    {
        return $query->where('tracking_date', Carbon::today());
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('tracking_date', $date);
    }

    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('tracking_date', Carbon::now()->month)
                     ->whereYear('tracking_date', Carbon::now()->year);
    }

    public function scopeExceedingLimits($query)
    {
        return $query->where('exceeds_plan_limits', true);
    }

    // Methods
    public function getTotalResourceCount(): int
    {
        return $this->current_locations + 
               $this->current_departments + 
               $this->current_users + 
               $this->current_products + 
               $this->current_customers + 
               $this->current_employees;
    }

    public function getStorageGb(): float
    {
        return $this->monthly_storage_mb / 1024;
    }

    public function getFormattedStorage(): string
    {
        $gb = $this->getStorageGb();
        
        if ($gb >= 1024) {
            return number_format($gb / 1024, 2) . ' TB';
        } elseif ($gb >= 1) {
            return number_format($gb, 2) . ' GB';
        } else {
            return number_format($this->monthly_storage_mb, 2) . ' MB';
        }
    }

    public function incrementUsage(string $field, int $amount = 1): bool
    {
        return $this->increment($field, $amount);
    }

    public function decrementUsage(string $field, int $amount = 1): bool
    {
        return $this->decrement($field, $amount);
    }

    public function resetDailyCounts(): bool
    {
        return $this->update([
            'active_sessions' => 0,
            'concurrent_users' => 0,
            'average_response_time_ms' => null,
            'error_rate_percent' => null,
        ]);
    }

    public static function getOrCreateForToday($tenantId): self
    {
        return self::firstOrCreate(
            [
                'tenant_id' => $tenantId,
                'tracking_date' => Carbon::today(),
            ],
            [
                'tenant_id' => $tenantId,
                'tracking_date' => Carbon::today(),
            ]
        );
    }

    public static function getOrCreateForDate($tenantId, $date): self
    {
        return self::firstOrCreate(
            [
                'tenant_id' => $tenantId,
                'tracking_date' => $date,
            ],
            [
                'tenant_id' => $tenantId,
                'tracking_date' => $date,
            ]
        );
    }

    public static function updateOrCreateForToday($tenantId, array $data): self
    {
        return self::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'tracking_date' => Carbon::today(),
            ],
            $data
        );
    }

    public function calculateUtilizationPercentage(int $planLimit, int $currentUsage): float
    {
        if ($planLimit <= 0) {
            return 0;
        }
        
        return min(100, ($currentUsage / $planLimit) * 100);
    }

    public function checkPlanLimits(array $planLimits): array
    {
        $exceeds = [];
        
        $limitsToCheck = [
            'current_shops' => $planLimits['shops'] ?? null,
            'current_users' => $planLimits['users'] ?? null,
            'current_products' => $planLimits['products'] ?? null,
            'monthly_storage_mb' => ($planLimits['storage_gb'] ?? 0) * 1024,
        ];
        
        foreach ($limitsToCheck as $field => $limit) {
            if ($limit !== null && $this->{$field} > $limit) {
                $exceeds[$field] = [
                    'limit' => $limit,
                    'current' => $this->{$field},
                    'excess' => $this->{$field} - $limit,
                ];
            }
        }
        
        $this->exceeds_plan_limits = !empty($exceeds);
        $this->save();
        
        return $exceeds;
    }
}