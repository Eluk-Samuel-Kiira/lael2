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
    ];

    // Relationships
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

}
