<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Sign in · PREI HelpDesk</title>
@include('partials.branding-head')
<style>
*{box-sizing:border-box}body{margin:0;min-height:100vh;min-height:100dvh;display:flex;flex-direction:column;font-size:16px;line-height:1.5}header{min-height:82px;padding:20px 32px;display:flex;align-items:center;justify-content:space-between;gap:20px;background:linear-gradient(135deg,#fff,#f3f5ff);border-bottom:1px solid var(--border)}main{flex:1;display:grid;place-items:center;padding:32px;background:radial-gradient(circle at 10% 20%,#2563eb0a,transparent 30%),var(--bg)}.login-card{width:100%;max-width:440px;background:#fff;border:1px solid var(--border);border-radius:12px;padding:32px;box-shadow:0 10px 15px -3px #00000014,0 4px 6px -4px #00000014}h1{margin:0 0 2px;font-size:24px;line-height:1.3}.subtitle{margin:0 0 22px;color:var(--text-secondary)}.field{margin-bottom:16px}label{display:block;margin-bottom:6px;font-size:14px;font-weight:500}input{width:100%;padding:10px 12px;font-size:15px;border:1px solid var(--border);line-height:1.3}button{width:100%;padding:10px 16px;margin-top:10px;border:0;color:#fff;font-size:16px;font-weight:600;cursor:pointer}button:disabled{opacity:.7;cursor:wait}.links{margin:18px 0 0;font-size:15px}.error{background:#fee2e2;color:#991b1b;padding:12px;border:1px solid #fca5a5;border-radius:8px;margin-bottom:16px;font-size:14px}.status{color:#166534}footer{text-align:center;padding:16px 24px;font-size:14px;color:var(--text-secondary)}footer a{color:inherit}[hidden]{display:none!important}@media(max-width:600px){header{padding:18px 20px;flex-wrap:wrap}.prei-brand{font-size:18px}header>a:last-child{font-size:13px}main{padding:24px 16px}.login-card{padding:24px}.links{font-size:14px}}
</style>
</head>
<body>
<header><a class="prei-brand" href="{{ url('/') }}">@include('partials.brand')</a><a href="{{ route('support.create') }}">Submit Request Form →</a></header>
<main><section class="login-card" aria-labelledby="login-title">
<h1 id="login-title">Staff &amp; User Portal</h1><p class="subtitle">Sign in to track your support tickets.</p>
@if(session('status'))<p class="status" role="status">{{ session('status') }}</p>@endif
<div id="loginError" class="error" role="alert" hidden></div>
<form id="loginForm" action="{{ url('/api/login') }}" method="post">@csrf
<div class="field"><label for="loginEmail">Email Address</label><input id="loginEmail" type="email" name="email" autocomplete="username" required placeholder="name@example.com"></div>
<div class="field"><label for="loginPassword">Password</label><input id="loginPassword" type="password" name="password" autocomplete="current-password" required placeholder="••••••••"></div>
<button id="loginSubmit" type="submit">Sign In</button>
</form><p class="links"><a href="{{ route('password.request') }}">Forgot password?</a> · <a href="{{ route('register') }}">Create customer account</a></p>
</section></main>
<footer>© {{ date('Y') }} PREI HelpDesk — Need help? Contact IT at <a href="mailto:{{ $dashboardSettings['support_email'] }}">{{ $dashboardSettings['support_email'] }}</a></footer>
<script>
const form=document.getElementById('loginForm');
const button=document.getElementById('loginSubmit');
const error=document.getElementById('loginError');
let submitting=false;
form.addEventListener('submit',async event=>{
 event.preventDefault();if(submitting)return;
 submitting=true;button.disabled=true;button.textContent='Signing in…';error.hidden=true;
 let redirecting=false;
 try{
  const response=await fetch(form.action,{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':form.elements._token.value},body:JSON.stringify({email:form.elements.email.value.trim(),password:form.elements.password.value})});
  const data=await response.json().catch(()=>({}));
  if(!response.ok)throw new Error(response.status===419?'Your session expired. Refresh the page and sign in again.':(data.error||data.message||'Unable to sign in. Please try again.'));
  redirecting=true;window.location.assign(data.redirect_url||'{{ route('helpdesk.index') }}');
 }catch(failure){error.textContent=failure.message||'Unable to connect. Please try again.';error.hidden=false;}
 finally{if(!redirecting){submitting=false;button.disabled=false;button.textContent='Sign In';}}
});
</script>
</body></html>

