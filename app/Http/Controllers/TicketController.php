<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use App\Services\HelpdeskMail;
use App\Services\TicketWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TicketController extends Controller
{
    public function __construct(private TicketWorkflow $workflow) {}

    public function index(Request $request)
    {
        return response()->json(Ticket::visibleTo($request->user())->with([
            'user', 'agent', 'category', 'department', 'team', 'sla',
            'comments' => fn ($q) => $q->when($request->user()->isUser(), fn ($q) => $q->where('is_internal', false))->with('user'),
        ])->latest('updated_at')->get());
    }

    public function getCategories()
    {
        return response()->json(Category::all());
    }

    public function getAgents(Request $request)
    {
        abort_unless($request->user()->canAccess('tickets.view') || $request->user()->canAccess('tickets.create') || $request->user()->canAccess('tickets.assign'), 403);

        return response()->json(User::whereIn('role', ['agent', 'admin'])->where('is_active', true)->get(['id', 'name']));
    }

    public function store(Request $request)
    {
        $ticket = $this->workflow->create($request);
        app(HelpdeskMail::class)->opened($ticket);

        return response()->json($ticket->load(['user', 'agent', 'category', 'comments']));
    }

    public function storeComment(Request $request, Ticket $ticket)
    {
        $comment = $this->workflow->reply($request, $ticket);
        if (! $comment->is_internal && ! $request->user()->isUser()) {
            app(HelpdeskMail::class)->replied($ticket, $comment->body);
        }

        return response()->json($comment->load('user'));
    }

    public function updateStatus(Request $request, Ticket $ticket)
    {
        $this->workflow->assertVisible($ticket, $request->user());
        $request->validate(['status' => 'required|in:open,in_progress,resolved,closed']);
        $this->workflow->status($ticket, $request->user(), $request->input('status'));

        return response()->json($ticket->fresh()->load(['user', 'agent', 'category', 'comments']));
    }

    public function assignAgent(Request $request, Ticket $ticket)
    {
        $this->workflow->assertVisible($ticket, $request->user());
        abort_unless($request->user()->canAccess('tickets.assign'), 403);
        $request->validate(['agent_id' => ['nullable', Rule::exists('users', 'id')->where('is_active', true)->whereIn('role', ['admin', 'agent'])]]);
        DB::transaction(function () use ($ticket, $request) {
            $locked = Ticket::whereKey($ticket->id)->lockForUpdate()->firstOrFail();
            $this->workflow->assertUnlocked($locked, $request->user());
            $locked->update(['agent_id' => $request->input('agent_id')]);
            if ($locked->wasChanged('agent_id')) {
                app(HelpdeskMail::class)->assigned($locked);
            }
            $this->workflow->event($locked, $request->user(), 'assigned', 'System developer assignment updated.');
        });

        return response()->json($ticket->fresh()->load(['user', 'agent', 'category', 'comments']));
    }
}
