<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryAdjustments extends Model
{
    /** @use HasFactory<\Database\Factories\InventoryAdjustmentsFactory> */
    use HasFactory;

    protected $fillable = [
        'quantity_before',
        'quantity_after',
        'reason',
        'notes',
        'inventory_id',
        'created_by',
        'tenant_id',
    ];
}
