<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerGroup extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerGroupFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'discount_percentage',
        'is_default',
        'created_by',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class, 'group_id');
    }

    public function customerGroupCreater()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
