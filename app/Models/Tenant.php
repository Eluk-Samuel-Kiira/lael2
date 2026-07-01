<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tenant extends Model
{
    use HasFactory;

    public $timestamps = true;

    protected $fillable = ['name', 'subdomain', 'status'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function configuration()
    {
        return $this->hasOne(TenantConfiguration::class, 'tenant_id');
    }

    public function usageTracking()
    {
        return $this->hasMany(TenantUsageTracking::class, 'tenant_id');
    }  

    public function adminUsers()
    {
        return $this->hasMany(User::class, 'tenant_id')
                    ->where('role_id', 1); // Assuming role_id 1 = admin
    }  
    
    public function settings()
    {
        return $this->hasMany(TenantSetting::class, 'tenant_id');
    }

    public function appSettings()
    {
        return $this->hasOne(Setting::class, 'tenant_id');
    }

    // Get latest usage tracking
    public function latestUsage()
    {
        return $this->hasOne(TenantUsageTracking::class, 'tenant_id')
                    ->latest('tracking_date');
    }

    public function currencies()
    {
        return $this->hasMany(Currency::class, 'tenant_id');
    }

    public function baseCurrency()
    {
        return $this->hasOne(Currency::class, 'tenant_id')->where('is_base_currency', 1);
    }
}