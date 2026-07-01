<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    /** @use HasFactory<\Database\Factories\DepartmentFactory> */
    use HasFactory;
    protected $fillable = ['name', 'isActive', 'created_by', 'manager_id', 'tenant_id', 'location_id' ];

    public function departmentCreater()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function departmentManager()
    {
        return $this->belongsTo(User::class, 'manager_id', 'id');
    }

    public function departmentTenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    public function users()
    {
        return $this->belongsToMany(
            User::class,          // or User::class if you’re using User model
            'department_user',
            'department_id',
            'user_id'
        );
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'department_product', 'department_id', 'product_id');
    }

    
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

        public function scopeByLocation($query, $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    // Optional: Get active departments for a location
    public function scopeActiveAtLocation($query, $locationId)
    {
        return $query->where('location_id', $locationId)
                    ->where('isActive', true);
    }

}
