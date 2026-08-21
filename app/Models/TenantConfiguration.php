<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasTenant;


class TenantConfiguration extends Model
{
    use HasFactory, HasTenant;

    protected $primaryKey = 'config_id';

    protected $fillable = [
        'tenant_id',
        'currency_code',
        'timezone',
        'locale',
        'fiscal_year_start',
        'tax_calculation_method',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
