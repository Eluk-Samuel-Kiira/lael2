<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryTransactions extends Model
{
    /** @use HasFactory<\Database\Factories\InventoryTransactionsFactory> */
    use HasFactory;
    
    protected $fillable = [
        'quantity',
        'reference_id',
        'reference_type',
        'type',
        'notes',
        'inventory_id',
        'created_by',
        'tenant_id',
    ];

    public function InventoryItems()
    {
        return $this->belongsTo(InventoryItems::class, 'inventory_id', 'id');
    }
}
