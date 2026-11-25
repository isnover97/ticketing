@extends('helpdesk.layout')
@section('title',auth()->user()->isUser() ? 'My Support Tickets' : ($department ? $department->name.' Dashboard' : 'Tickets & Dashboard'))
@section('subtitle','Track requests, review deadlines, and keep support moving.')
@section('actions')@if(auth()->user()->canAccess('tickets.create'))<a class="button" href="{{ route('helpdesk.create') }}">＋ Open a ticket</a>@endif @endsection
@section('content')
@php($departmentFilter = $department ? ['department_id' => $department->id] : [])
@unless(auth()->user()->isUser())
<section class="card">
    <form class="filters" method="get" action="{{ route('helpdesk.index') }}">
        <div>
            <label for="department">Department dashboard</label>
            <select id="department" name="department_id">
                @unless(auth()->user()->hasDepartmentTicketScope() && $departments->count() === 1)
                    <option value="">All accessible departments</option>
                @endunless
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" @selected($department?->id === $dept->id)>{{ $dept->name }}</option>
                @endforeach
            </select>
        </div>
        <button>View dashboard</button>
    </form>
</section>
@endunless
@if($dashboardSettings['show_stats'])<div class="stats">
@foreach($stats as $label=>$count)
@php($filter = match($label) {'Open'=>['status'=>'open'],'In progress'=>['status'=>'in_progress'],'Resolved'=>['status'=>'resolved'],'Closed'=>['status'=>'closed'],'Overdue'=>['overdue'=>1],default=>['assigned'=>'unassigned']})
<a class="stat {{ in_array($label,['Resolved','Closed']) ? 'stat-completed' : '' }}" href="{{ route('helpdesk.index',$filter + $departmentFilter) }}" aria-label="View {{ strtolower($label) }} tickets: {{ $count }}"><span>{{ $label }}</span><strong>{{ $count }}</strong></a>
@endforeach
</div>@endif
<section class="card"><form class="filters" method="get">@if($department)<input type="hidden" name="department_id" value="{{ $department->id }}">@endif<div><label for="search">Search</label><input id="search" name="search" value="{{ request('search') }}" placeholder="Subject, number, email"></div>
<div><label for="status">Status</label><select id="status" name="status"><option value="">All statuses</option>@foreach(['open','in_progress','resolved','closed'] as $value)<option @selected(request('status')===$value) value="{{ $value }}">{{ ucwords(str_replace('_',' ',$value)) }}</option>@endforeach</select></div>
<div><label for="priority">Priority</label><select id="priority" name="priority"><option value="">All priorities</option>@foreach(['low','medium','high','urgent'] as $value)<option @selected(request('priority')===$value) value="{{ $value }}">{{ ucfirst($value) }}</option>@endforeach</select></div>
@unless(auth()->user()->isUser())
<div><label for="assigned">Assignment</label><select id="assigned" name="assigned"><option value="">All assignments</option><option value="mine" @selected(request('assigned')==='mine')>Assigned to me</option><option value="unassigned" @selected(request('assigned')==='unassigned')>Unassigned</option></select></div>@endunless
<div><label for="queue">Saved queue</label><select id="queue" name="queue"><option value="">All tickets</option>@foreach($queues as $queue)<option value="{{ $queue->id }}" @selected((int)request('queue')===$queue->id)>{{ $queue->name }}</option>@endforeach</select></div>
<label class="check"><input type="checkbox" name="overdue" value="1" @checked(request('overdue'))> Overdue</label><button>Apply</button><a class="button secondary" href="{{ route('helpdesk.index',$departmentFilter) }}">Reset</a>
@if(auth()->user()->canAccess('tickets.export'))<a class="button secondary" href="{{ route('helpdesk.export',request()->query()) }}">Export CSV</a>@endif</form>
<div class="table-wrap"><table><thead><tr><th>Ticket</th><th>Requester</th><th>Status</th><th>Priority</th><th>Department / Assignee</th><th>Due</th></tr></thead><tbody>
@forelse($tickets as $ticket)<tr><td><a href="{{ route('helpdesk.ticket',$ticket) }}"><strong>{{ $ticket->title }}</strong></a><small>{{ $ticket->ticket_number }}</small></td><td>{{ $ticket->user->name }}<small>{{ $ticket->user->email }}</small></td><td><span class="badge {{ $ticket->status }}">{{ ucwords(str_replace('_',' ',$ticket->status)) }}</span></td><td><span class="badge {{ $ticket->priority }}">{{ ucfirst($ticket->priority) }}</span></td><td>{{ $ticket->department?->name ?? '—' }}<small>{{ $ticket->agent?->name ?? 'Unassigned' }}</small></td><td>@if($ticket->due_at)<span class="{{ $ticket->isOverdue() ? 'badge overdue' : '' }}">{{ $ticket->due_at->copy()->timezone($dashboardSettings['display_timezone'])->format('M d, H:i') }}</span>@else<span class="muted">No SLA</span>@endif</td></tr>@empty<tr><td colspan="7" class="empty">No tickets match this queue.</td></tr>@endforelse
</tbody></table></div>
<div class="pagination">{{ $tickets->links() }}</div></section>
@endsection








