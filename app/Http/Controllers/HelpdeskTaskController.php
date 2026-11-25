<?php

namespace App\Http\Controllers;

use App\Models\HelpdeskTask;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class HelpdeskTaskController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['status' => 'nullable|in:open,in_progress,closed']);

        return view('helpdesk.tasks', [
            'tasks' => HelpdeskTask::with(['ticket', 'assignee'])->where(fn ($q) => $q->whereNull('ticket_id')->orWhereHas('ticket', fn ($t) => $t->visibleTo($request->user())))->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))->latest()->paginate(25)->withQueryString(),
            'tickets' => Ticket::visibleTo($request->user())->whereNotIn('status', ['closed', 'resolved'])->latest()->limit(300)->get(['id', 'ticket_number', 'title']),
            'agents' => User::whereIn('role', ['agent', 'admin'])->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['title' => 'required|string|max:255', 'description' => 'nullable|string|max:20000', 'ticket_id' => 'nullable|exists:tickets,id', 'due_at' => 'nullable|date',
            'assigned_to' => ['nullable', Rule::exists('users', 'id')->whereIn('role', ['admin', 'agent'])->where('is_active', true)]]);
        DB::transaction(function () use ($request, $data) {
            $ticket = ! empty($data['ticket_id']) ? Ticket::whereKey($data['ticket_id'])->lockForUpdate()->firstOrFail() : null;
            if ($ticket) {
                app(TicketWorkflow::class)->assertVisible($ticket, $request->user());
            }
            if ($ticket && in_array($ticket->status, ['closed', 'resolved'])) {
                throw ValidationException::withMessages(['ticket_id' => 'Reopen the ticket before adding a task.']);
            }
            HelpdeskTask::create(array_merge($data, ['created_by' => $request->user()->id, 'status' => 'open']));
            app(TicketWorkflow::class)->event($ticket, $request->user(), 'task_created', 'Task created: '.$data['title']);
        });

        return back()->with('status', 'Task created.');
    }

    public function update(Request $request, HelpdeskTask $task)
    {
        $data = $request->validate(['status' => 'required|in:open,in_progress,closed']);
        DB::transaction(function () use ($request, $task, $data) {
            $ticket = $task->ticket_id ? Ticket::whereKey($task->ticket_id)->lockForUpdate()->firstOrFail() : null;
            if ($ticket) {
                app(TicketWorkflow::class)->assertVisible($ticket, $request->user());
            }
            if ($ticket && in_array($ticket->status, ['closed', 'resolved']) && $data['status'] !== 'closed') {
                throw ValidationException::withMessages(['status' => 'Reopen the ticket before reopening this task.']);
            }
            $task->update($data);
            app(TicketWorkflow::class)->event($ticket, $request->user(), 'task_updated', 'Task '.$task->title.': '.$data['status']);
        });

        return back()->with('status','Task updated.');
    }
}
