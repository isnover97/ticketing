<!DOCTYPE html><html lang="en" @unless($showSidebar ?? true) class="sidebar-collapsed" @endunless><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>@yield('title','Helpdesk') · PREI</title>
@if($showSidebar ?? true)<script>if(matchMedia('(max-width: 750px)').matches)document.documentElement.classList.add('sidebar-collapsed');try{if(!matchMedia('(max-width: 750px)').matches && localStorage.getItem('prei-sidebar-collapsed')==='true')document.documentElement.classList.add('sidebar-collapsed');}catch(e){}</script>@endif<link rel="stylesheet" href="{{ asset('css/helpdesk.css') }}">@include('partials.branding-head')@if($showSidebar ?? true)<link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">@endif</head><body @if($showSidebar ?? true) class="workspace-with-sidebar" @endif>
<header class="topbar"><div class="brand-controls">@if($showSidebar ?? true)<button type="button" id="sidebar-toggle" class="secondary sidebar-toggle" aria-controls="workspace-sidebar" aria-expanded="true" aria-label="Minimize menu"><span aria-hidden="true">☰</span></button>@endif<a class="brand prei-brand" href="{{ url('/') }}">
@include('partials.brand')</a></div><div class="top-actions"><a href="{{ route('knowledge.index') }}">Knowledge Base</a>@auth<span>{{ auth()->user()->name }}</span><form method="post" action="{{ route('helpdesk.logout') }}">@csrf<button class="button secondary small">Sign out</button></form>@else<a class="button small" href="{{ route('login') }}">Sign in</a>@endauth</div></header>
<div class="shell">@if($showSidebar ?? true)@include('helpdesk.sidebar')@endif
<main class="@yield('main-class')"><div class="page-heading"><div><div class="eyebrow">PREI SUPPORT</div><h1>@yield('title','Helpdesk')</h1><p class="muted">@yield('subtitle')</p></div>@yield('actions')</div>
@if(session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="notice danger" role="alert"><strong>Please check your request:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@yield('content')<p class="footer-note">Displayed times: {{ $dashboardSettings['display_timezone'] }}. Business schedules use their configured timezone.</p></main></div>
@if($showSidebar ?? true)
<script src="{{ asset('js/sidebar.js') }}"></script>
@endif
@stack('scripts')
</body></html>
