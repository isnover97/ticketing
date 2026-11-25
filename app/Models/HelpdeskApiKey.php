<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HelpdeskApiKey extends Model
{
    protected $fillable = ['name', 'key_hash', 'user_id', 'scopes', 'allowed_ip', 'expires_at', 'last_used_at', 'is_active'];

    protected $hidden = ['key_hash'];

    protected function casts(): array
    {
        return ['scopes' => 'array', 'expires_at' => 'datetime', 'last_used_at' => 'datetime', 'is_active' => 'boolean'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
