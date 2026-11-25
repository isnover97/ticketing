@extends('helpdesk.layout')
@section('title', $module[0])
@section('subtitle','Configure records used by your support workflow. Disable a record to preserve its history.')
@section('content')
<div class="grid"><section class="card"><h2>{{ $editing ? 'Edit record' : 'Add record' }}</h2>
<form method="post" action="{{ $editing ? route('helpdesk.manage.update',[$kind,$editing]) : route('helpdesk.manage.store',$kind) }}">@csrf @if($editing) @method('PUT') @endif
<div class="field"><label for="name">Name</label><input id="name" name="name" required maxlength="255" value="{{ old('name',$editing?->name) }}"></div>
<div class="field"><label for="description">Description</label><textarea id="description" name="description">{{ old('description',$editing?->description) }}</textarea></div>
<div class="field"><label for="is_active">Status</label><select name="is_active" id="is_active"><option value="1" @selected((int)old('is_active',$editing?->is_active ?? 1)===1)>{{ $kind === 'articles' ? 'Published' : 'Active' }}</option><option value="0" @selected((int)old('is_active',$editing?->is_active ?? 1)===0)>{{ $kind === 'articles' ? 'Draft / unpublished' : 'Disabled' }}</option></select></div>
@foreach($module[2] as $key=>$field)
@php($value=old('settings.'.$key,$kind === 'categories' ? $editing?->departments->modelKeys() : ($editing?->settings[$key] ?? null)))
<div class="field"><label for="setting-{{ $key }}">{{ $field[0] }}</label>
@if($field[1] === 'permissions')
@foreach(\App\Models\User::PERMISSIONS as $permission=>$label)<label class="check"><input type="checkbox" name="settings[{{ $key }}][]" value="{{ $permission }}" @checked(in_array($permission,$value ?? [],true))> {{ $label }}</label>@endforeach
@elseif($field[1] === 'weekdays')
<select multiple size="7" id="setting-{{ $key }}" name="settings[{{ $key }}][]">@foreach([1=>'Monday',2=>'Tuesday',3=>'Wednesday',4=>'Thursday',5=>'Friday',6=>'Saturday',7=>'Sunday'] as $day=>$label)<option value="{{ $day }}" @selected(in_array($day,$value ?? [1,2,3,4,5]))>{{ $label }}</option>@endforeach</select>
@elseif($field[1] === 'checkbox')<input id="setting-{{ $key }}" type="checkbox" name="settings[{{ $key }}]" value="1" @checked($value)>
@elseif($field[1] === 'textarea')<textarea id="setting-{{ $key }}" name="settings[{{ $key }}]">{{ $value }}</textarea>
@elseif(in_array($field[1],['record','record_many','agent','category','select','members']))
<select id="setting-{{ $key }}" name="settings[{{ $key }}]{{ in_array($field[1],['members','record_many']) ? '[]' : '' }}" @if(in_array($field[1],['members','record_many'])) multiple size="6" @endif>
@if(!in_array($field[1],['members','record_many']))<option value="">— Select / default —</option>@endif
@if($field[1]==='select') @foreach($field[2] as $option)<option value="{{ $option }}" @selected($value===$option)>{{ ucwords(str_replace('_',' ',$option)) }}</option>@endforeach
@else
@php($choices=match($field[1]){'record','record_many'=>$options[$field[2]] ?? collect(),'category'=>$categories,default=>$agents})
@foreach($choices as $choice)<option value="{{ $choice->id }}" @selected(is_array($value) ? in_array($choice->id,$value) : (string)$value===(string)$choice->id)>{{ $choice->name }}</option>@endforeach
@endif</select>
@else<input id="setting-{{ $key }}" name="settings[{{ $key }}]" type="{{ $field[1] }}" @if($field[1]==='number') min="0.25" max="87600" step="0.25" @endif value="{{ $value }}">@endif
</div>@endforeach
<button>{{ $editing ? 'Save changes' : 'Create record' }}</button>@if($editing)<a class="button secondary" href="{{ route('helpdesk.manage',$kind) }}">Cancel</a>@endif
</form></section>
<section class="card"><h2>{{ $module[0] }}</h2><div class="table-wrap"><table><thead><tr><th>Name</th><th>Status</th><th></th></tr></thead><tbody>@forelse($records as $record)<tr><td><strong>{{ $record->name }}</strong><small>{{ \Illuminate\Support\Str::limit($record->description,130) }}</small>@if($kind === 'categories')<small>{{ $record->departments->pluck('name')->join(', ') ?: 'No departments assigned' }}</small>@endif @if($kind==='queues')<a href="{{ route('helpdesk.index',['queue'=>$record->id]) }}">Open queue →</a>@endif</td><td><span class="badge {{ $record->is_active ? 'resolved' : '' }}">{{ $record->is_active ? ($kind==='articles' ? 'Published' : 'Active') : 'Disabled' }}</span></td><td><a href="{{ route('helpdesk.manage',[$kind,'edit'=>$record->id]) }}">Edit</a></td></tr>@empty<tr><td colspan="3" class="empty">No records yet. Create the first one using the form.</td></tr>@endforelse</tbody></table></div><div class="pagination">{{ $records->links() }}</div>
<p class="footer-note">SLA targets use elapsed time. Ticket filters run in creation order. Custom fields can apply to all topics or one topic.</p></section></div>
@endsection
