<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ticket_number',
        'user_id',
        'agent_id',
        'category_id',
        'title',
        'description',
        'status',
        'priority',
        'department_id', 'team_id', 'sla_id', 'help_topic_id', 'due_at', 'custom_fields', 'locked_by', 'locked_until',
    ];

    protected function casts(): array
    {
        return ['due_at' => 'datetime', 'locked_until' => 'datetime', 'custom_fields' => 'array'];
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function team()
    {
        return $this->belongsTo(HelpdeskRecord::class, 'team_id');
    }

    public function sla()
    {
        return $this->belongsTo(HelpdeskRecord::class, 'sla_id');
    }

    public function helpTopic()
    {
        return $this->belongsTo(HelpdeskRecord::class, 'help_topic_id');
    }

    public function tasks()
    {
        return $this->hasMany(HelpdeskTask::class);
    }

    public function events()
    {
        return $this->hasMany(TicketEvent::class);
    }

    public function attachments()
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function scopeVisibleTo($query, User $user)
    {
        if (! $user->canAccess('tickets.view')) {
            return $query->whereRaw('1 = 0');
        }
        if ($user->isUser()) {
            return $query->where('user_id', $user->id);
        }
        if ($user->hasDepartmentTicketScope()) {
            return $query->whereIn('department_id', $user->department_ids ?? []);
        }
        if ($user->isAdmin()) {
            return $query;
        }
        if ($user->ticket_scope === 'assigned') {
            $teams = HelpdeskRecord::ofKind('teams')->active()->get()->filter(fn ($t) => in_array((int) $user->id, array_map('intval', $t->settings['members'] ?? []), true))->pluck('id');

            return $query->where(fn ($q) => $q->where('agent_id', $user->id)->orWhereIn('team_id', $teams));
        }
        return $query;
    }

    public function isOverdue(): bool
    {
        return $this->due_at && $this->due_at->isPast() && ! in_array($this->status, ['closed', 'resolved'], true);
    }

    /**
     * Get the client user who created the ticket.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the IT agent assigned to the ticket.
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    /**
     * Get the category of the ticket.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the comments for the ticket.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}
