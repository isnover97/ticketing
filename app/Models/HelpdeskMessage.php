<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HelpdeskMessage extends Model
{
    protected $fillable = ['ticket_id', 'message_key', 'direction', 'recipient', 'subject', 'body', 'status', 'attempts', 'error'];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}
