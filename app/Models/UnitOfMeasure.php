<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UnitOfMeasure extends Model
{
    
    use HasFactory;

    protected $fillable = ['name', 'isActive', 'symbol', 'created_by', 'tenant_id',];

    
    public function UOMCreater()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->tenant_id)) {
                $model->tenant_id = auth()->check() ? auth()->user()->tenant_id : 1;
            }
        });
    }
}
