<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HelpdeskTask extends Model
{
    protected $fillable = ['ticket_id', 'created_by', 'assigned_to', 'title', 'description', 'status', 'due_at'];

    protected function casts(): array
    {
        return ['due_at' => 'datetime'];
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
