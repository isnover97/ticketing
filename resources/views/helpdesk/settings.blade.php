@extends('helpdesk.layout')
@section('title','Settings')
@section('subtitle','Manage helpdesk and dashboard preferences.')
@section('content')
<section class="card max-form"><form method="post" action="{{ route('helpdesk.settings.save') }}">@csrf
<div class="field"><label>Helpdesk name</label><input name="name" required maxlength="80" value="{{ old('name',$settings['name']) }}"></div>
<div class="field"><label>Support contact email</label><input name="support_email" type="email" required value="{{ old('support_email',$settings['support_email']) }}"><small>Displayed on sign-in. Configure SMTP in Email &amp; Delivery.</small></div>
<div class="field"><label for="display_timezone">Display timezone</label><select id="display_timezone" name="display_timezone">@foreach(\DateTimeZone::listIdentifiers() as $zone)<option value="{{ $zone }}" @selected(old('display_timezone',$settings['display_timezone'])===$zone)>{{ $zone }}</option>@endforeach</select><small>Used for ticket dates. Business schedules retain their configured timezone.</small></div><div class="field"><label>Tickets per page</label><select name="page_size">@foreach([10,25,50,100] as $size)<option value="{{ $size }}" @selected((int)old('page_size',$settings['page_size'])===$size)>{{ $size }}</option>@endforeach</select></div>
<div class="field"><label>Dashboard summary cards</label><select name="show_stats"><option value="1" @selected(old('show_stats',$settings['show_stats']))>Show</option><option value="0" @selected(!old('show_stats',$settings['show_stats']))>Hide</option></select></div>
<div class="field"><label>Default ticket priority</label><select name="default_priority">@foreach(['low','medium','high','urgent'] as $priority)<option value="{{ $priority }}" @selected(old('default_priority',$settings['default_priority'])===$priority)>{{ ucfirst($priority) }}</option>@endforeach</select></div><h2>Ticket number series</h2>
<div class="field"><label for="number_mode">Numbering mode</label><select id="number_mode" name="number_mode"><option value="random" @selected(old('number_mode',$settings['number_mode'])==='random')>Random suffix</option><option value="sequential" @selected(old('number_mode',$settings['number_mode'])==='sequential')>Sequential series</option></select></div>
<div class="field"><label for="number_prefix">Prefix</label><input id="number_prefix" name="number_prefix" maxlength="20" pattern="[A-Za-z0-9_-]*" value="{{ old('number_prefix',$settings['number_prefix']) }}"><small>Letters, numbers, hyphens and underscores. May be blank.</small></div>
<div class="field"><label for="number_date">Date segment</label><select id="number_date" name="number_date">@foreach(['Ymd'=>'Year, month, day (20261008)','Ym'=>'Year and month (202610)','Y'=>'Year (2026)','none'=>'No date'] as $format=>$label)<option value="{{ $format }}" @selected(old('number_date',$settings['number_date'])===$format)>{{ $label }}</option>@endforeach</select></div>
<div class="field"><label for="number_padding">Sequence digits</label><input type="number" id="number_padding" name="number_padding" min="1" max="12" required value="{{ old('number_padding',$settings['number_padding']) }}"></div>
<div class="field"><label for="next_number">Change next sequence number (optional)</label><input type="number" id="next_number" name="next_number" min="1" max="999999999999" value="{{ old('next_number') }}" placeholder="{{ $nextNumber }}"><small>Current next number: {{ $nextNumber }}. Leave blank to continue the series. Existing numbers are preserved; duplicates are skipped. The sequence continues across dates.</small></div>
<p>Preview: <strong id="series-preview" aria-live="polite"></strong></p><button>Save settings</button></form></section>
<section class="card max-form"><h2>Administration</h2><a href="{{ route('admin.users.index') }}">Users &amp; access</a> · <a href="{{ route('helpdesk.email') }}">Email &amp; Delivery</a> · <a href="{{ route('helpdesk.audit') }}">Audit History</a></section>
@endsection

@push('scripts')
<script>
(()=>{
 const ids=['number_mode','number_prefix','number_date','number_padding','next_number'];
 const dates={Ymd:@json(now()->format('Ymd')),Ym:@json(now()->format('Ym')),Y:@json(now()->format('Y')),none:''};
 function preview(){const v=id=>document.getElementById(id).value;const suffix=v('number_mode')==='random'?'XXXXXXXXXX':String(v('next_number')||@json($nextNumber)).padStart(Math.min(12,Math.max(1,Number(v('number_padding'))||1)),'0');document.getElementById('series-preview').textContent=[v('number_prefix'),dates[v('number_date')],suffix].filter(Boolean).join('-');}
 ids.forEach(id=>document.getElementById(id).addEventListener('input',preview));preview();
})();
</script>
@endpush

