<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{
    /** @use HasFactory<\Database\Factories\TaxFactory> */
    use HasFactory;
    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'rate',
        'type',
        'is_active',
        'created_by',
    ];

    public function getRateAttribute($value)
    {
        if ($this->type === 'fixed') {
            return formatCurrency($value);
        }
        
        return $value;
    }

    // Mutator for rate - convert to USD only when type is 'fixed'
    public function setRateAttribute($value)
    {
        if ($this->type === 'fixed') {
            $this->attributes['rate'] = toUSD($value);
        } else {
            $this->attributes['rate'] = $value; // Store raw value for percentages
        }
    }

    public function taxCreater()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
