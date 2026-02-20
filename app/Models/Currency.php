<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'symbol_position',
        'decimal_places',
        'exchange_rate',
        'created_by',
        'is_active',
        'is_base_currency',
        'tenant_id',
    ];

    protected $casts = [
        'exchange_rate' => 'decimal:8',
        'is_active' => 'boolean',
        'is_base_currency' => 'boolean',
        'decimal_places' => 'integer',
    ];

    public function currencyCreater()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

  

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->tenant_id)) {
                $model->tenant_id = auth()->check() ? auth()->user()->tenant_id : 1;
            }
        });

        static::saving(function ($model) {
            // Ensure only one base currency per tenant
            if ($model->is_base_currency) {
                static::where('tenant_id', $model->tenant_id)
                    ->where('id', '!=', $model->id)
                    ->update(['is_base_currency' => false]);
            }
        });
    }

    /**
     * Get the multiplier for smallest unit based on decimal places
     */
    public function getMultiplier(): int
    {
        return (int) pow(10, $this->decimal_places ?? 2);
    }

    /**
     * Format amount with currency symbol
     */
    public function format(float $amount): string
    {
        $formatted = number_format($amount, $this->decimal_places ?? 2, '.', ',');
        
        if ($this->symbol_position === 'before') {
            return $this->symbol . $formatted;
        }
        
        return $formatted . ' ' . $this->symbol;
    }
}