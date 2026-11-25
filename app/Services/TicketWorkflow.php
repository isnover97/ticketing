<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Category;
use App\Models\Department;
use App\Models\HelpdeskRecord;
use App\Models\Ticket;
use App\Models\TicketEvent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TicketWorkflow
{
    public function assertVisible(Ticket $ticket, User $user): void
    {
        abort_unless($user->is_active && $user->canAccess('tickets.view'), 403);
        abort_unless(Ticket::visibleTo($user)->whereKey($ticket->id)->exists(), 403);
    }

    public function assertUnlocked(Ticket $ticket, User $user): void
    {
        if ($user->isUser()) {
            return;
        }
        if ($ticket->locked_until?->isFuture() && (int) $ticket->locked_by !== (int) $user->id) {
            throw ValidationException::withMessages(['ticket' => 'Another staff member has locked this ticket. Try again after '.$ticket->locked_until->format('H:i').'.']);
        }
    }

    public function event(?Ticket $ticket, ?User $user, string $action, string $description): void
    {
        TicketEvent::create(['ticket_id' => $ticket?->id, 'user_id' => $user?->id, 'action' => $action, 'description' => $description]);
    }

    public function create(Request $request, bool $enforcePortalRouting = false): Ticket
    {
        if ($request->user()) {
            abort_unless($request->user()->canAccess('tickets.create'), 403);
        }
        $data = $request->validate([
            'title' => 'required|string|max:255', 'description' => 'required|string|max:20000',
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->when($enforcePortalRouting, fn ($rule) => $rule->where('is_active', true))],
            'priority' => 'required|in:low,medium,high,urgent',
            'requester_name' => 'nullable|string|max:255', 'requester_email' => 'nullable|email|max:255',
            'help_topic_id' => ['nullable', Rule::exists('helpdesk_records', 'id')->where('kind', 'topics')->where('is_active', true)],
            'agent_id' => ['nullable', Rule::exists('users', 'id')->where('is_active', true)->whereIn('role', ['admin', 'agent'])],
            'department_id' => [$enforcePortalRouting ? 'required' : 'nullable', 'integer', Rule::exists('departments', 'id')->where('is_active', true)],
            'custom_fields' => 'nullable|array',
        ]);
        $actor = $request->user();
        $email = $actor?->isUser() ? $actor->email : strtolower(trim($data['requester_email'] ?? ''));
        if (! $email) {
            throw ValidationException::withMessages(['requester_email' => 'A requester email address is required.']);
        }
        $topic = ! empty($data['help_topic_id']) ? HelpdeskRecord::find($data['help_topic_id']) : null;
        $routing = $topic?->settings ?? [];
        if ($enforcePortalRouting && $topic && (int) ($routing['category_id'] ?? 0) !== (int) $data['category_id']) {
            throw ValidationException::withMessages(['help_topic_id' => 'Select a help topic for the chosen category.']);
        }
        if (isset($data['department_id'])) {
            $departmentId = (int) $data['department_id'];
            $categoryId = (int) $data['category_id'];
            $categoryAvailable = $enforcePortalRouting
                ? Category::whereKey($categoryId)->whereHas('departments', fn ($query) => $query->whereKey($departmentId))->exists()
                : HelpdeskRecord::ofKind('topics')->active()->get()->contains(
                    fn ($record) => (int) ($record->settings['department_id'] ?? 0) === $departmentId
                        && (int) ($record->settings['category_id'] ?? 0) === $categoryId
                );
            if (! $categoryAvailable) {
                throw ValidationException::withMessages(['category_id' => 'Select a category for the chosen department.']);
            }
            if ($topic && ((int) ($routing['department_id'] ?? 0) !== $departmentId
                || (isset($routing['category_id']) && (int) $routing['category_id'] !== $categoryId))) {
                throw ValidationException::withMessages(['help_topic_id' => 'Select a help topic for the chosen department and category.']);
            }
            $routing['department_id'] = $departmentId;
        }
        $priority = $routing['priority'] ?? $data['priority'];
        $data['category_id'] = $routing['category_id'] ?? $data['category_id'];
        $fields = HelpdeskRecord::fieldsForTopic($topic);
        $rules = [];
        $labels = [];
        foreach ($fields as $field) {
            $settings = $field->settings;
            $key = 'custom_fields.'.$settings['key'];
            $rule = [! empty($settings['required']) ? 'required' : 'nullable'];
            $type = $settings['type'] ?? 'text';
            $rule[] = $type === 'number' ? 'numeric' : ($type === 'date' ? 'date_format:Y-m-d' : 'string');
            if (! in_array($type, ['number', 'date'])) {
                $rule[] = 'max:5000';
            }
            if ($type === 'select') {
                $rule[] = Rule::in($field->selectOptions());
            }
            $rules[$key] = $rule;
            $labels[$key] = $field->name;
        }
        validator($request->all(), $rules, [], $labels)->validate();
        foreach (HelpdeskRecord::ofKind('filters')->active()->orderBy('id')->get() as $filter) {
            $s = $filter->settings;
            $actual = (string) ($s['field'] === 'email' ? $email : ($data[$s['field']] ?? ''));
            $matched = ($s['match'] ?? 'contains') === 'equals' ? strcasecmp($actual, (string) $s['value']) === 0 : str_contains(strtolower($actual), strtolower((string) $s['value']));
            if (! $matched) {
                continue;
            }
            if (! empty($s['reject'])) {
                throw ValidationException::withMessages(['title' => 'This request was rejected by a ticket filter. Please contact support.']);
            }
            foreach (['department_id', 'team_id', 'sla_id', 'agent_id'] as $key) {
                if (! empty($s[$key])) {
                    $routing[$key] = $s[$key];
                }
            }
            if (! empty($s['priority'])) {
                $priority = $s['priority'];
            }
            if (! empty($s['stop'])) {
                break;
            }
        }
        if ($actor?->canAccess('tickets.assign') && ! empty($data['agent_id'])) {
            $routing['agent_id'] = $data['agent_id'];
        }
        $department = ! empty($routing['department_id']) ? Department::query()->active()->find($routing['department_id']) : null;
        if ($department) {
            foreach (['team_id', 'sla_id'] as $key) {
                if (empty($routing[$key])) {
                    $routing[$key] = $department->settings[$key] ?? null;
                }
            }
        }
        $resolved = ['department_id' => $department?->id];
        foreach (['team_id' => 'teams', 'sla_id' => 'sla'] as $key => $kind) {
            $resolved[$key] = ! empty($routing[$key]) ? HelpdeskRecord::ofKind($kind)->active()->find($routing[$key])?->id : null;
        }
        $agent = ! empty($routing['agent_id']) ? User::whereIn('role', ['admin', 'agent'])->where('is_active', true)->find($routing['agent_id']) : null;
        $sla = $resolved['sla_id'] ? HelpdeskRecord::find($resolved['sla_id']) : null;
        $custom = [];
        foreach ($fields as $field) {
            $custom[$field->settings['key']] = $request->input('custom_fields.'.$field->settings['key']);
        }

        return DB::transaction(function () use ($data, $actor, $email, $topic, $resolved, $agent, $sla, $priority, $custom) {
            $user = User::firstOrCreate(['email' => $email], ['name' => $data['requester_name'] ?? $email, 'password' => Str::random(40), 'role' => 'user']);
            if (! $user->organization_id) {
                $domain = strtolower(Str::after($email, '@'));
                $org = HelpdeskRecord::ofKind('organizations')->active()->get()->first(fn ($o) => strtolower($o->settings['domain'] ?? '') === $domain);
                if ($org) {
                    $user->update(['organization_id' => $org->id]);
                }
            }
            $ticket = Ticket::create(array_merge($resolved, [
                'ticket_number' => app(TicketNumberSeries::class)->next(),
                'user_id' => $user->id, 'agent_id' => $agent?->id, 'category_id' => $data['category_id'],
                'title' => $data['title'], 'description' => $data['description'], 'status' => 'open', 'priority' => $priority,
                'help_topic_id' => $topic?->id, 'due_at' => $sla ? app(BusinessClock::class)->due($sla) : null,
                'custom_fields' => $custom,
            ]));
            $this->event($ticket, $actor, 'created', 'Ticket opened'.($topic ? ' via '.$topic->name : '').'.');

            return $ticket;
        });
    }

    public function reply(Request $request, Ticket $ticket): Comment
    {
        $user = $request->user();
        $this->assertVisible($ticket, $user);
        $data = $request->validate(['body' => 'required|string|max:20000', 'is_internal' => 'sometimes|boolean']);
        $internal = (bool) ($data['is_internal'] ?? false);
        abort_unless($user->canAccess($internal ? 'tickets.notes' : 'tickets.comment'), 403);

        return DB::transaction(function () use ($ticket, $user, $data, $internal) {
            $locked = Ticket::whereKey($ticket->id)->lockForUpdate()->firstOrFail();
            $this->assertUnlocked($locked, $user);
            $comment = $locked->comments()->create(['user_id' => $user->id, 'body' => $data['body'], 'is_internal' => $internal]);
            $locked->touch();
            $this->event($locked, $user, $internal ? 'internal_note' : 'reply', $internal ? 'Internal note added.' : 'Reply added.');

            return $comment;
        });
    }

    public function status(Ticket $ticket, User $user, string $status): void
    {
        abort_unless($user->canAccess('tickets.status'), 403);
        DB::transaction(function () use ($ticket, $user, $status) {
            $locked = Ticket::whereKey($ticket->id)->lockForUpdate()->firstOrFail();
            $this->assertUnlocked($locked, $user);
            if (in_array($status, ['resolved', 'closed']) && $locked->tasks()->where('status', '!=', 'closed')->exists()) {
                throw ValidationException::withMessages(['status' => 'Complete all linked tasks before resolving or closing this ticket.']);
            }
            $old = $locked->status;
            $locked->update(['status' => $status]);
            app(HelpdeskMail::class)->statusChanged($locked, $old);
            $this->event($locked, $user, 'status', "Status changed from {$old} to {$status}.");
        });
    }
}

