<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = ['name', 'description', 'settings', 'is_active'];

    protected function casts(): array
    {
        return ['settings' => 'array', 'is_active' => 'boolean'];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
