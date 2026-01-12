<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'group_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'birth_date',
        'tax_number',
        'address',
        'city',
        'state',
        'postal_code',
        'country_code',
        'notes',
        'created_by',
        'accepts_marketing',
        'is_active',
    ];

    // Relationships
    public function tenant() { return $this->belongsTo(Tenant::class); }
    public function group() { return $this->belongsTo(CustomerGroup::class, 'group_id'); }
    
    public function customerCreater()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
