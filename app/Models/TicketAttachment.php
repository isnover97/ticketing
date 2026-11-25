<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketAttachment extends Model
{
    protected $fillable = ['ticket_id', 'user_id', 'name', 'path', 'mime', 'size', 'is_internal'];

    protected function casts(): array
    {
        return ['is_internal' => 'boolean'];
    }

    protected $hidden = ['path'];

    public function isPreviewableImage(): bool
    {
        return in_array($this->mime, ['image/jpeg', 'image/png', 'image/gif'], true);
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}
