<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ticket_id',
        'user_id',
        'body',
        'is_internal',
    ];

    protected function casts(): array
    {
        return ['is_internal' => 'boolean'];
    }

    /**
     * Get the ticket this comment belongs to.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Get the user who posted the comment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
