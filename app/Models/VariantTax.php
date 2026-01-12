<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VariantTax extends Model
{
    /** @use HasFactory<\Database\Factories\TaxFactory> */
    use HasFactory;


    protected $fillable = [
        'tenant_id',
        'variant_id',
        'tax_id',
        'created_by',
    ];
}
