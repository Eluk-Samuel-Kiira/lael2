<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'description', 'is_active', 'created_by', 'tenant_id',
    ];

    public function categoryCreater()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

}
