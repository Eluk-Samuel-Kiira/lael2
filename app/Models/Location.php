<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    /** @use HasFactory<\Database\Factories\LocationFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'address',
        'is_primary',
        'created_by',
        'manager_id',
        'is_active',
        'currency_id',
    ];

    // Relationships


    public function departments()
    {
        return $this->hasMany(Department::class);
    }

    // Optional: Get active departments
    public function activeDepartments()
    {
        return $this->hasMany(Department::class)->where('isActive', true);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function locationCreater()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function locationManager()
    {
        return $this->belongsTo(User::class, 'manager_id', 'id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'location_product');
    }

    /**
     * Get the currency for this location
     */
    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }



}
