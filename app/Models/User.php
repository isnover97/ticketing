<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    protected $attributes = ['is_active' => true, 'ticket_scope' => 'all'];

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'permissions',
        'is_active',
        'organization_id',
        'ticket_scope', 'department_ids', 'access_profile_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'permissions' => 'array',
            'is_active' => 'boolean',
            'department_ids' => 'array',
        ];
    }

    /**
     * Check if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function hasDepartmentTicketScope(): bool
    {
        return ! $this->isUser() && ($this->ticket_scope === 'departments'
            || ($this->isAdmin() && ! empty($this->department_ids)));
    }

    public function canManageSystem(): bool
    {
        return $this->isAdmin() && ! $this->hasDepartmentTicketScope();
    }

    public const PERMISSIONS = [
        'tickets.view' => 'View tickets and dashboard',
        'tickets.create' => 'Create tickets',
        'tickets.comment' => 'Comment on tickets',
        'tickets.status' => 'Change ticket status (staff only)',
        'tickets.assign' => 'Assign tickets (staff only)',
        'analytics.view' => 'View analytics (staff only)',
        'tickets.notes' => 'Post internal notes (staff only)',
        'tickets.transfer' => 'Transfer tickets between departments (staff only)',
        'tickets.export' => 'Export ticket queues (staff only)',
        'tasks.manage' => 'Manage internal tasks (staff only)',
        'knowledge.manage' => 'Manage knowledge base and canned responses (staff only)',
        'directory.view' => 'View user and organization directory (staff only)',
        'configuration.manage' => 'Configure help topics, teams, SLAs and routing (staff only)',
    ];

    public static function defaultPermissions(string $role): array
    {
        return $role === 'user'
            ? ['tickets.view', 'tickets.create', 'tickets.comment']
            : array_keys(self::PERMISSIONS);
    }

    public function effectivePermissions(): array
    {
        if (! $this->is_active) {
            return [];
        }
        if ($this->isAdmin()) {
            return array_keys(self::PERMISSIONS);
        }
        $profile = $this->access_profile_id ? HelpdeskRecord::ofKind('roles')->active()->find($this->access_profile_id) : null;
        $permissions = $this->permissions ?? ($this->access_profile_id ? ($profile?->settings['permissions'] ?? []) : self::defaultPermissions($this->role));
        if (in_array('analytics.view', $permissions, true)) {
            $permissions[] = 'tickets.view';
        }

        return array_values(array_unique(array_intersect($permissions, self::defaultPermissions($this->role))));
    }

    public function canAccess(string $permission): bool
    {
        return in_array($permission, $this->effectivePermissions(), true);
    }

    public function sendPasswordResetNotification($token): void
    {
        $url = \App\Support\HelpdeskEmailUrl::route('password.reset', ['token' => $token, 'email' => $this->email]);
        HelpdeskMessage::create(['message_key' => (string) Str::uuid(), 'direction' => 'out',
            'recipient' => $this->email, 'subject' => 'Reset your PREI HelpDesk password',
            'body' => "A password reset was requested for your helpdesk account.\n\nOpen this link to choose a new password:\n".$url."\n\nIf you did not request this, ignore this message.", 'status' => 'pending']);
    }

    /**
     * Check if the user is an agent.
     */
    public function isAgent(): bool
    {
        return $this->role === 'agent';
    }

    /**
     * Check if the user is a standard client user.
     */
    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    /**
     * Get the tickets created by the user.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'user_id');
    }

    /**
     * Get the tickets assigned to the user as agent.
     */
    public function assignedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'agent_id');
    }

    /**
     * Get the comments posted by the user.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}
