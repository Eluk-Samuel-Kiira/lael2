<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryItems extends Model
{
    /** @use HasFactory<\Database\Factories\InventoryItemsFactory> */
    use HasFactory;
    
    protected $fillable = [
        'quantity_on_hand',
        'quantity_allocated',
        'quantity_on_order',
        'reorder_point',
        'preferred_stock_level',
        'batch_number',
        'expiry_date',
        'variant_id',
        'location_id',
        'department_id',
        'created_by',
        'tenant_id',
    ];

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id', 'id');
    }

    public function departmentItem()
    {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    public function itemCreater()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function itemLocation()
    {
        return $this->belongsTo(Location::class, 'location_id', 'id');
    }
}
