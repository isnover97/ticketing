@extends('helpdesk.layout')
@section('title',$ticket->ticket_number)
@section('subtitle',$ticket->title)
@section('actions')<a class="button secondary" href="{{ route('helpdesk.index') }}">← Ticket queue</a>@endsection
@section('content')
@if($ticket->locked_until?->isFuture())<div class="notice {{ (int)$ticket->locked_by===auth()->id() ? '' : 'danger' }}">{{ (int)$ticket->locked_by===auth()->id() ? 'You have locked this ticket' : 'Another staff member has locked this ticket' }} until {{ $ticket->locked_until->copy()->timezone($dashboardSettings['display_timezone'])->format('H:i') }}.</div>@endif
<div class="detail-grid"><div>
<section class="card"><h2>Conversation</h2><article class="thread-entry"><div class="thread-meta"><strong>{{ $ticket->user->name }}</strong><span>{{ $ticket->created_at->copy()->timezone($dashboardSettings['display_timezone'])->format('M d, Y H:i') }}</span></div><div class="prose">{{ $ticket->description }}</div></article>
@foreach($ticket->comments as $comment)<article class="thread-entry {{ $comment->is_internal ? 'internal' : '' }}"><div class="thread-meta"><strong>{{ $comment->user->name }} @if($comment->is_internal)<span class="badge">Internal note</span>@endif</strong><span>{{ $comment->created_at->copy()->timezone($dashboardSettings['display_timezone'])->format('M d, Y H:i') }}</span></div><div class="prose">{{ $comment->body }}</div></article>@endforeach
@if(auth()->user()->canAccess('tickets.comment') || auth()->user()->canAccess('tickets.notes'))
<form method="post" action="{{ route('helpdesk.reply',$ticket) }}">@csrf
@if($canned->isNotEmpty())
<div class="field">
    <label for="canned">Insert a canned response</label>
    <select id="canned">
        <option value="">Select response…</option>
        @foreach($canned as $label => $responses)
            <optgroup label="{{ $label }}">
                @foreach($responses as $response)
                    <option value="{{ $response->settings['body'] ?? '' }}">{{ $response->name }}</option>
                @endforeach
            </optgroup>
        @endforeach
    </select>
</div>
@endif
<div class="field"><label for="body">Message</label><textarea id="body" name="body" required maxlength="20000" rows="6">{{ old('body') }}</textarea></div>
@if(auth()->user()->canAccess('tickets.notes'))<div class="field"><label for="internal">Visibility</label><select id="internal" name="is_internal">@if(auth()->user()->canAccess('tickets.comment'))<option value="0">Public reply to requester</option>@endif<option value="1" @selected(old('is_internal')==1)>Internal note — staff only</option></select></div>@endif
<button>Post message</button></form>@endif</section>
<section class="card"><h2>Attachments</h2>
@forelse($ticket->attachments as $attachment)
    <div class="audit">
        @if($attachment->isPreviewableImage())
            @php($previewUrl = route('helpdesk.download', ['attachment' => $attachment, 'preview' => 1]))
            <a class="attachment-preview" href="{{ $previewUrl }}" target="_blank" rel="noopener">
                <img src="{{ $previewUrl }}" alt="View full-size {{ $attachment->name }}" loading="lazy">
            </a>
        @endif
        <a href="{{ route('helpdesk.download', $attachment) }}">{{ $attachment->name }}</a>
        <span class="muted">{{ number_format($attachment->size/1024,1) }} KB</span>
        @if($attachment->is_internal)<span class="badge">Internal</span>@endif
    </div>
@empty
    <p class="muted">No attachments.</p>
@endforelse
@if(auth()->user()->canAccess('tickets.comment') || auth()->user()->canAccess('tickets.notes'))<form style="margin-top:18px" method="post" enctype="multipart/form-data" action="{{ route('helpdesk.attachment',$ticket) }}">@csrf<div class="field"><label for="attachment">Add a file (up to 10 MB)</label><input id="attachment" type="file" name="file" required accept=".pdf,.png,.jpg,.jpeg,.gif,.txt,.csv,.doc,.docx,.xls,.xlsx,.zip"></div>@if(auth()->user()->canAccess('tickets.notes'))<label class="check field"><input type="checkbox" name="is_internal" value="1" @checked(!auth()->user()->canAccess('tickets.comment'))> Staff only</label>@endif<button class="secondary">Upload attachment</button></form>@endif</section>
@unless(auth()->user()->isUser())<section class="card"><h2>Linked tasks</h2>@forelse($tasks as $task)<div class="audit"><strong>{{ $task->title }}</strong> <span class="badge {{ $task->status }}">{{ str_replace('_',' ',$task->status) }}</span><small>{{ $task->assignee?->name ?? 'Unassigned' }}</small></div>@empty<p class="muted">No linked tasks.</p>@endforelse @if(auth()->user()->canAccess('tasks.manage'))<a class="button secondary small" href="{{ route('helpdesk.tasks',['ticket_id'=>$ticket->id]) }}">Manage tasks</a>@endif</section>
<section class="card"><h2>Ticket history</h2>@forelse($events as $event)<div class="audit">{{ $event->description }}<small>{{ $event->user?->name ?? 'System' }} · {{ $event->created_at->copy()->timezone($dashboardSettings['display_timezone'])->format('M d, H:i') }}</small></div>@empty<p class="muted">No recorded events yet.</p>@endforelse</section>@endunless
</div><aside>
<section class="card"><h2>Ticket details</h2><dl class="properties"><dt>Status</dt><dd><span class="badge {{ $ticket->status }}">{{ ucwords(str_replace('_',' ',$ticket->status)) }}</span></dd><dt>Priority</dt><dd>{{ ucfirst($ticket->priority) }}</dd><dt>Requester</dt><dd>{{ $ticket->user->name }}<br>{{ $ticket->user->email }}</dd><dt>Category</dt><dd>{{ $ticket->category?->name }}</dd><dt>Help topic</dt><dd>{{ $ticket->helpTopic?->name ?? 'General support' }}</dd><dt>Department</dt><dd>{{ $ticket->department?->name ?? '—' }}</dd><dt>Team</dt><dd>{{ $ticket->team?->name ?? '—' }}</dd><dt>Assignee</dt><dd>{{ $ticket->agent?->name ?? 'Unassigned' }}</dd><dt>SLA plan</dt><dd>{{ $ticket->sla?->name ?? '—' }}</dd><dt>Due date</dt><dd class="{{ $ticket->isOverdue() ? 'badge overdue' : '' }}">{{ $ticket->due_at?->copy()->timezone($dashboardSettings['display_timezone'])->format('M d, Y H:i') ?? '—' }}</dd>@foreach($ticket->custom_fields ?? [] as $key=>$value)<dt>{{ $fields->get($key)?->name ?? $key }}</dt><dd>{{ $value }}</dd>@endforeach</dl></section>
@if(auth()->user()->canAccess('tickets.status'))<section class="card"><h3>Change status</h3><form method="post" action="{{ route('helpdesk.update',$ticket) }}">@csrf @method('PATCH')<input type="hidden" name="action" value="status"><div class="field"><select name="status" aria-label="Ticket status">@foreach(['open','in_progress','resolved','closed'] as $status)<option @selected($ticket->status===$status) value="{{ $status }}">{{ ucwords(str_replace('_',' ',$status)) }}</option>@endforeach</select></div><button class="small">Save status</button></form></section>@endif
@if(auth()->user()->canAccess('tickets.assign'))<section class="card"><h3>Assign ticket</h3><form method="post" action="{{ route('helpdesk.update',$ticket) }}">@csrf @method('PATCH')<input type="hidden" name="action" value="assign"><div class="field"><label for="agent">System developer</label><select id="agent" name="agent_id"><option value="">Unassigned</option>@foreach($agents as $agent)<option value="{{ $agent->id }}" @selected($ticket->agent_id===$agent->id)>{{ $agent->name }}</option>@endforeach</select></div><div class="field"><label for="team">Team</label><select id="team" name="team_id"><option value="">No team</option>@foreach($teams as $team)<option value="{{ $team->id }}" @selected($ticket->team_id===$team->id)>{{ $team->name }}</option>@endforeach</select></div><button class="small">Assign</button></form></section>@endif
@if(auth()->user()->canAccess('tickets.transfer'))<section class="card"><h3>Transfer department</h3><form method="post" action="{{ route('helpdesk.update',$ticket) }}">@csrf @method('PATCH')<input type="hidden" name="action" value="transfer"><div class="field"><select name="department_id" aria-label="Destination department"><option value="">No department</option>@foreach($departments as $dept)<option value="{{ $dept->id }}" @selected($ticket->department_id===$dept->id)>{{ $dept->name }}</option>@endforeach</select></div><button class="small">Transfer</button></form></section>@endif
@if(!auth()->user()->isUser() && (auth()->user()->canAccess('tickets.comment') || auth()->user()->canAccess('tickets.notes')))<section class="card"><h3>Collision avoidance</h3><p class="muted">Reserve this ticket for five minutes while responding.</p><form method="post" action="{{ route('helpdesk.update',$ticket) }}">@csrf @method('PATCH')<input type="hidden" name="action" value="{{ (int)$ticket->locked_by===auth()->id() && $ticket->locked_until?->isFuture() ? 'unlock' : 'lock' }}"><button class="secondary small">{{ (int)$ticket->locked_by===auth()->id() && $ticket->locked_until?->isFuture() ? 'Release lock' : 'Lock ticket' }}</button></form></section>@endif
</aside></div>@endsection
@push('scripts')<script>document.getElementById('canned')?.addEventListener('change',e=>{const input=document.getElementById('body');if(e.target.value){input.value+=(input.value?'\n\n':'')+e.target.value;input.focus();e.target.value='';}});</script>@endpush

