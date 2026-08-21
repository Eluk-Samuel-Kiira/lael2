<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasTenant;


class VariantTax extends Model
{
    /** @use HasFactory<\Database\Factories\TaxFactory> */
    use HasFactory, HasTenant;


    protected $fillable = [
        'tenant_id',
        'variant_id',
        'tax_id',
        'created_by',
    ];
}
