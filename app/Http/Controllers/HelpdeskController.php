<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Department;
use App\Models\HelpdeskRecord;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketEvent;
use App\Models\User;
use App\Services\HelpdeskMail;
use App\Services\TicketWorkflow;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class HelpdeskController extends Controller
{
    private const ATTACHMENT_RULES = 'file|max:10240|mimes:pdf,png,jpg,jpeg,gif,txt,csv,doc,docx,xls,xlsx,zip';

    public function __construct(private TicketWorkflow $workflow) {}

    private function query(Request $request)
    {
        $request->validate(['search' => 'nullable|string|max:255', 'status' => 'nullable|in:open,in_progress,resolved,closed', 'priority' => 'nullable|in:low,medium,high,urgent', 'assigned' => 'nullable|in:mine,unassigned', 'department_id' => 'nullable|integer', 'queue' => 'nullable|integer']);
        $filters = $request->only(['status', 'priority', 'assigned', 'department_id', 'overdue']);
        if ($request->filled('queue')) {
            $queue = HelpdeskRecord::ofKind('queues')->active()->findOrFail($request->integer('queue'));
            $filters = array_merge($queue->settings ?? [], array_filter($filters, fn ($v) => $v !== null && $v !== ''));
        }
        $query = Ticket::visibleTo($request->user());
        if ($request->filled('search')) {
            $term = '%'.$request->input('search').'%';
            $query->where(fn ($q) => $q->where('title', 'like', $term)->orWhere('ticket_number', 'like', $term)->orWhereHas('user', fn ($u) => $u->where('email', 'like', $term)));
        }
        foreach (['status', 'priority', 'department_id'] as $key) {
            if (! empty($filters[$key])) {
                $query->where($key, $filters[$key]);
            }
        }
        if (($filters['assigned'] ?? '') === 'mine') {
            $query->where('agent_id', $request->user()->id);
        }
        if (($filters['assigned'] ?? '') === 'unassigned') {
            $query->whereNull('agent_id');
        }
        if (! empty($filters['overdue'])) {
            $query->where('due_at', '<', now())->whereNotIn('status', ['closed', 'resolved']);
        }

        return $query;
    }

    public function index(Request $request)
    {
        $tickets = $this->query($request);
        $user = $request->user();
        $departments = Department::query()->active()->orderBy('name');
        if ($user->hasDepartmentTicketScope()) {
            $departments->whereIn('id', $user->department_ids ?? []);
        }
        $departments = $departments->get();
        $department = $user->hasDepartmentTicketScope() && $departments->count() === 1
            ? $departments->first()
            : null;
        if (! $user->isUser() && $request->filled('department_id')) {
            $department = $departments->firstWhere('id', $request->integer('department_id'));
            abort_unless($department, 404);
        }

        $base = Ticket::visibleTo($user);
        if ($department) {
            $base->where('department_id', $department->id);
        }
        $stats = [
            'Open' => (clone $base)->where('status', 'open')->count(),
            'In progress' => (clone $base)->where('status', 'in_progress')->count(),
            'Resolved' => (clone $base)->where('status', 'resolved')->count(),
            'Closed' => (clone $base)->where('status', 'closed')->count(),
            'Overdue' => (clone $base)->where('due_at', '<', now())->whereNotIn('status', ['closed', 'resolved'])->count(),
            'Unassigned' => (clone $base)->whereNull('agent_id')->whereNotIn('status', ['closed', 'resolved'])->count(),
        ];

        return view('helpdesk.tickets', [
            'tickets' => $tickets->with(['user', 'agent', 'department'])->latest('updated_at')->paginate((int) DashboardSettingsController::settings()['page_size'])->withQueryString(),
            'stats' => $stats, 'queues' => HelpdeskRecord::ofKind('queues')->active()->orderBy('name')->get(),
            'departments' => $departments,
            'department' => $department,
        ]);
    }

    public function create(Request $request)
    {
        if ($request->user()) {
            abort_unless($request->user()->canAccess('tickets.create'), 403);
        }

        return view('helpdesk.create', [
            'departments' => Department::query()->active()->orderBy('name')->get(),
            'categories' => Category::where('is_active', true)->with('departments')->orderBy('name')->get(),
            'topics' => HelpdeskRecord::ofKind('topics')->active()->orderBy('name')->get(),
            'fields' => HelpdeskRecord::availableFields()->sortBy('name'),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate(['file' => 'nullable|'.self::ATTACHMENT_RULES]);
        $file = $request->file('file');
        $path = null;
        try {
            $ticket = DB::transaction(function () use ($request, $file, &$path) {
                $ticket = $this->workflow->create($request, enforcePortalRouting: true);
                if ($file) {
                    $path = $file->store('ticket-attachments', 'local');
                    abort_unless($path, 500, 'Could not store attachment.');
                    $this->recordAttachment($ticket, $file, $path, $request->user() ?? $ticket->user, false);
                }

                return $ticket;
            });
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $e;
        }
        app(HelpdeskMail::class)->opened($ticket);
        if (! $request->user()) {
            return redirect()->route('support.create')->with('status', 'Your request has been received. Ticket number: '.$ticket->ticket_number.'. Contact support to set up portal access.');
        }

        return Ticket::visibleTo($request->user())->whereKey($ticket->id)->exists()
            ? redirect()->route('helpdesk.ticket', $ticket)->with('status', 'Ticket created successfully.')
            : redirect()->route('helpdesk.create')->with('status', 'Ticket created: '.$ticket->ticket_number);
    }

    public function show(Request $request, Ticket $ticket)
    {
        $this->workflow->assertVisible($ticket, $request->user());
        $ticket->load(['user', 'agent', 'category', 'department', 'team', 'sla', 'helpTopic',
            'comments' => fn ($q) => $q->when($request->user()->isUser(), fn ($q) => $q->where('is_internal', false))->with('user')->orderBy('id'),
            'attachments' => fn ($q) => $q->when($request->user()->isUser(), fn ($q) => $q->where('is_internal', false)),
        ]);

        return view('helpdesk.ticket', [
            'ticket' => $ticket,
            'agents' => User::whereIn('role', ['admin', 'agent'])->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'departments' => Department::query()->active()->get(),
            'teams' => HelpdeskRecord::ofKind('teams')->active()->get(),
            'canned' => $request->user()->isUser() ? collect() : \App\Support\CannedResponses::forTicket($ticket),
            'events' => $request->user()->isUser() ? collect() : $ticket->events()->with('user')->latest()->limit(100)->get(),
            'tasks' => $request->user()->isUser() ? collect() : $ticket->tasks()->with('assignee')->get(),
            'fields' => HelpdeskRecord::ofKind('fields')->get()->keyBy(fn ($f) => $f->settings['key'] ?? ''),
        ]);
    }

    public function reply(Request $request, Ticket $ticket)
    {
        $comment = $this->workflow->reply($request, $ticket);
        if (! $comment->is_internal && ! $request->user()->isUser()) {
            app(HelpdeskMail::class)->replied($ticket, $comment->body);
        }

        return back()->with('status', $comment->is_internal ? 'Internal note saved.' : 'Reply posted.');
    }

    public function update(Request $request, Ticket $ticket)
    {
        $this->workflow->assertVisible($ticket, $request->user());
        $data = $request->validate([
            'action' => 'required|in:status,assign,transfer,lock,unlock',
            'status' => 'required_if:action,status|in:open,in_progress,resolved,closed',
            'agent_id' => ['nullable', Rule::exists('users', 'id')->where('is_active', true)->whereIn('role', ['admin', 'agent'])],
            'department_id' => ['nullable', Rule::exists('departments', 'id')->where('is_active', true)],
            'team_id' => ['nullable', Rule::exists('helpdesk_records', 'id')->where('kind', 'teams')->where('is_active', true)],
        ]);
        $user = $request->user();
        if ($data['action'] === 'status') {
            $this->workflow->status($ticket, $user, $data['status']);
        } else {
            DB::transaction(function () use ($data, $ticket, $user) {
                $ticket = Ticket::whereKey($ticket->id)->lockForUpdate()->firstOrFail();
                $this->workflow->assertUnlocked($ticket, $user);
                if ($data['action'] === 'assign') {
                    abort_unless($user->canAccess('tickets.assign'), 403);
                    $ticket->update(['agent_id' => $data['agent_id'] ?? null, 'team_id' => $data['team_id'] ?? null]);
                    if ($ticket->wasChanged(['agent_id', 'team_id'])) {
                        app(HelpdeskMail::class)->assigned($ticket);
                    }
                    $this->workflow->event($ticket, $user, 'assigned', 'Assignment updated.');
                } elseif ($data['action'] === 'transfer') {
                    abort_unless($user->canAccess('tickets.transfer'), 403);
                    $ticket->update(['department_id' => $data['department_id'] ?? null]);
                    $this->workflow->event($ticket, $user, 'transferred', 'Transferred to '.($ticket->department?->name ?? 'no department').'.');
                } else {
                    abort_unless(! $user->isUser() && ($user->canAccess('tickets.comment') || $user->canAccess('tickets.notes')), 403);
                    $lock = $data['action'] === 'lock';
                    $ticket->update(['locked_by' => $lock ? $user->id : null, 'locked_until' => $lock ? now()->addMinutes(5) : null]);
                    $this->workflow->event($ticket, $user, $lock ? 'locked' : 'unlocked', $lock ? 'Ticket locked for five minutes.' : 'Ticket lock released.');
                }
            });
        }

        return back()->with('status', 'Ticket updated.');
    }

    public function attachment(Request $request, Ticket $ticket)
    {
        $this->workflow->assertVisible($ticket, $request->user());
        $request->validate(['file' => 'required|'.self::ATTACHMENT_RULES, 'is_internal' => 'sometimes|boolean']);
        $internal = $request->boolean('is_internal');
        abort_unless($request->user()->canAccess($internal ? 'tickets.notes' : 'tickets.comment'), 403);
        $this->workflow->assertUnlocked($ticket, $request->user());
        $file = $request->file('file');
        $path = $file->store('ticket-attachments', 'local');
        abort_unless($path, 500, 'Could not store attachment.');
        try {
            DB::transaction(fn () => $this->recordAttachment($ticket, $file, $path, $request->user(), $internal));
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }

        return back()->with('status', 'Attachment uploaded.');
    }

    private function recordAttachment(Ticket $ticket, UploadedFile $file, string $path, User $uploader, bool $internal): void
    {
        $ticket->attachments()->create([
            'user_id' => $uploader->id,
            'name' => mb_substr(basename($file->getClientOriginalName()), 0, 255),
            'path' => $path,
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'is_internal' => $internal,
        ]);
        $this->workflow->event($ticket, $uploader, 'attachment', 'Attachment uploaded.');
    }

    public function download(Request $request, TicketAttachment $attachment)
    {
        $this->workflow->assertVisible($attachment->ticket, $request->user());
        abort_if($attachment->is_internal && $request->user()->isUser(), 403);
        $disk = Storage::disk('local');
        $path = $attachment->getRawOriginal('path');
        abort_unless($disk->exists($path), 404);

        $headers = ['X-Content-Type-Options' => 'nosniff'];
        if ($request->boolean('preview') && $attachment->isPreviewableImage()) {
            return $disk->response($path, $attachment->name, $headers + [
                'Content-Type' => $attachment->mime,
                'Cache-Control' => 'private, no-store',
                'Content-Security-Policy' => 'sandbox',
            ]);
        }

        return $disk->download($path, $attachment->name, $headers);
    }

    public function bulk(Request $request)
    {
        $data = $request->validate(['ids' => 'required|array|min:1|max:100', 'ids.*' => 'required|integer|exists:tickets,id', 'status' => 'required|in:open,in_progress,resolved,closed']);
        DB::transaction(function () use ($request, $data) {
            foreach (array_unique($data['ids']) as $id) {
                $ticket = Ticket::findOrFail($id);
                $this->workflow->assertVisible($ticket, $request->user());
                $this->workflow->status($ticket, $request->user(), $data['status']);
            }
        });

        return back()->with('status', 'Selected tickets updated.');
    }

    public function export(Request $request)
    {
        $query = $this->query($request)->with(['user', 'agent', 'department'])->orderBy('id');

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Number', 'Subject', 'Requester', 'Status', 'Priority', 'Department', 'Assignee', 'Due']);
            foreach ($query->cursor() as $t) {
                $row = [$t->ticket_number, $t->title, $t->user->email, $t->status, $t->priority, $t->department?->name, $t->agent?->name, $t->due_at?->toIso8601String()];
                fputcsv($out, array_map(fn ($v) => preg_match('/^[=+@\-\t\r\n]/', (string) $v) ? "'".$v : (string) $v, $row));
            }
            fclose($out);
        }, 'tickets-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function directory(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        return view('helpdesk.directory', [
            'users' => User::when($search, fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%')))->orderBy('name')->paginate((int) DashboardSettingsController::settings()['page_size'])->withQueryString(),
            'organizations' => HelpdeskRecord::ofKind('organizations')->get()->keyBy('id'),
        ]);
    }

    public function audit()
    {
        return view('helpdesk.audit', ['events' => TicketEvent::with(['user', 'ticket'])->latest()->paginate(50)]);
    }

    public function reports(Request $request)
    {
        $request->validate(['from' => 'nullable|date_format:Y-m-d', 'to' => 'nullable|date_format:Y-m-d|after_or_equal:from']);
        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to = $request->input('to', now()->toDateString());
        $base = Ticket::visibleTo($request->user())->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to);

        return view('helpdesk.reports', [
            'from' => $from, 'to' => $to, 'total' => (clone $base)->count(),
            'statuses' => (clone $base)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status'),
            'priorities' => (clone $base)->selectRaw('priority, COUNT(*) as total')->groupBy('priority')->pluck('total', 'priority'),
            'overdue' => (clone $base)->where('due_at', '<', now())->whereNotIn('status', ['closed', 'resolved'])->count(),
            'agents' => User::whereIn('role', ['admin', 'agent'])->get()->map(fn ($u) => ['name' => $u->name, 'assigned' => (clone $base)->where('agent_id', $u->id)->count(), 'resolved' => (clone $base)->where('agent_id', $u->id)->whereIn('status', ['resolved', 'closed'])->count()]),
        ]);
    }
}


