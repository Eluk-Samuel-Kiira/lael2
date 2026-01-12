<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tenant extends Model
{
    use HasFactory;

    protected $primaryKey = 'tenant_id';
    public $timestamps = false;

    protected $fillable = ['name', 'subdomain', 'status'];

    public function configuration()
    {
        return $this->hasOne(TenantConfiguration::class, 'tenant_id');
    }
}
