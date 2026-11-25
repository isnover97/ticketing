@extends('helpdesk.layout')
@section('title',match($mode){'register'=>'Create a customer account','forgot'=>'Forgot password',default=>'Reset password'})
@section('subtitle','Secure access to your support requests.')
@section('content')<section class="card max-form"><form method="post" action="{{ route(match($mode){'register'=>'register.store','forgot'=>'password.email',default=>'password.update'}) }}">@csrf
@if($mode==='register')<div class="field"><label for="name">Full name</label><input id="name" name="name" autocomplete="name" maxlength="255" value="{{ old('name') }}" required></div>@endif
@if($mode==='reset')<input type="hidden" name="token" value="{{ $token }}">@endif
<div class="field"><label for="email">Email address</label><input id="email" type="email" name="email" autocomplete="email" value="{{ old('email',$email ?? '') }}" required></div>
@if($mode!=='forgot')<div class="field"><label for="password">Password (at least 12 characters)</label><input id="password" type="password" name="password" minlength="12" maxlength="128" autocomplete="new-password" required></div><div class="field"><label for="password_confirmation">Confirm password</label><input id="password_confirmation" type="password" name="password_confirmation" minlength="12" maxlength="128" autocomplete="new-password" required></div>@endif
<button>{{ match($mode){'register'=>'Create account','forgot'=>'Request reset email',default=>'Save new password'} }}</button><p><a href="{{ route('login') }}">Back to sign in</a></p>@if($mode==='register')<p class="muted">If you have previously submitted a guest request with this email, use <a href="{{ route('password.request') }}">Forgot password</a> to claim access to those tickets through your mailbox.</p>@endif</form></section>@endsection
