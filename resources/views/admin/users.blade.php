<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>User Management · PREI HelpDesk</title>
    <style>
        *{box-sizing:border-box}body{margin:0;background:#f3f6fb;color:#182337;font:15px/1.5 system-ui,sans-serif}header{background:#fff;border-bottom:1px solid #dfe5ee;padding:20px max(24px,calc((100vw - 1200px)/2));display:flex;align-items:center;justify-content:space-between;gap:20px}header strong{font-size:20px}a{color:#254fc3}main{max-width:1200px;margin:32px auto;padding:0 24px}.intro{margin-bottom:24px}h1{margin:0;font-size:28px}h2{font-size:20px;margin:0 0 18px}.muted{color:#64748b}.grid{display:grid;grid-template-columns:minmax(300px,420px) minmax(0,1fr);gap:24px;align-items:start}.card{background:#fff;padding:24px;border:1px solid #dfe5ee;border-radius:14px}.field{margin:0 0 16px}label{display:block;font-weight:600;margin-bottom:6px}input:not([type=checkbox]),select{width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:7px;font:inherit}input:focus,select:focus{outline:2px solid #7fa5ff;outline-offset:1px}button,.button{display:inline-block;background:#294fc4;color:white;border:0;border-radius:7px;padding:11px 16px;font:600 14px system-ui;cursor:pointer;text-decoration:none}.notice{padding:14px 18px;border-radius:8px;margin-bottom:22px;background:#e6f6ed;color:#14532d}.error{background:#fff0f0;color:#991b1b}fieldset{border:1px solid #dfe5ee;border-radius:8px;margin:20px 0;padding:14px}legend{font-weight:700}.permission{display:flex;gap:9px;align-items:start;font-weight:400;margin:12px 0}.permission input{margin-top:5px}small{display:block}.table-wrap{overflow:auto}table{width:100%;border-collapse:collapse;text-align:left;white-space:nowrap}th,td{padding:13px 10px;border-bottom:1px solid #edf0f5}th{font-size:12px;text-transform:uppercase;color:#64748b}td small{color:#64748b}.badge{padding:3px 8px;border-radius:20px;background:#e6f6ed;color:#14532d;font-size:12px}.inactive{background:#fff0f0;color:#991b1b}.search{display:flex;gap:8px;margin-bottom:20px}.pagination{margin-top:20px}.pagination svg{width:18px;height:18px}.pagination nav>div{margin:10px 0}.pagination a,.pagination span[aria-current]{margin:0 5px}.full{max-width:620px;margin:auto}@media(max-width:900px){.grid{grid-template-columns:1fr}header{padding:18px 24px}main{margin:24px auto}}
    </style>
@include('partials.branding-head')</head>
<body>
<header><a class="prei-brand" href="{{ url('/helpdesk') }}">@include('partials.brand')</a><a href="{{ url('/') }}">← Back to portal</a></header>
<main>
    <div class="intro"><h1>User Management</h1><p class="muted">Create accounts and manage access to helpdesk modules.</p></div>
    @if(session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="notice error" role="alert"><strong>Please check the following:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="{{ $editing ? 'full' : 'grid' }}">
        <section class="card">
            <h2>{{ $editing ? 'Edit account' : 'Create account' }}</h2>
            <form method="post" action="{{ $editing ? route('admin.users.update', $editing) : route('admin.users.store') }}">
                @csrf
                @if($editing) @method('PUT') @endif
                <div class="field"><label for="name">Full name</label><input id="name" name="name" required maxlength="255" autocomplete="name" value="{{ old('name', $editing?->name) }}"></div>
                <div class="field"><label for="email">Email address</label><input id="email" type="email" name="email" required maxlength="255" autocomplete="off" value="{{ old('email', $editing?->email) }}"></div>
                <div class="field"><label for="role">Role</label><select id="role" name="role">@foreach(['user'=>'Client','agent'=>'System Developer','admin'=>'Administrator'] as $value=>$label)<option value="{{ $value }}" @selected(old('role', $editing?->role ?? 'user') === $value)>{{ $label }}</option>@endforeach</select></div>
                <div class="field"><label for="active">Account status</label><select id="active" name="is_active"><option value="1" @selected((string)old('is_active', $editing?->is_active ?? 1) === '1')>Active</option><option value="0" @selected((string)old('is_active', $editing ? (int)$editing->is_active : 1) === '0')>Inactive</option></select></div>
                <div class="field"><label for="password">{{ $editing ? 'New password (optional)' : 'Password' }}</label><input id="password" type="password" name="password" autocomplete="new-password" minlength="12" maxlength="128" @required(!$editing)><small class="muted">At least 12 characters. {{ $editing ? 'Leave blank to keep the current password.' : '' }}</small></div>
                <div class="field"><label for="password_confirmation">Confirm password</label><input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" minlength="12" maxlength="128" @required(!$editing)></div>
                @include('admin.user-access')
                <fieldset><legend>Module access</legend><small class="muted" id="permissionHelp">Clients can access only their own tickets. Administrators have full access, including user management. Analytics requires ticket viewing.</small>
                    @php($selected = old('permissions', session()->hasOldInput() ? [] : ($editing ? ($editing->permissions ?? \App\Models\User::defaultPermissions($editing->role)) : \App\Models\User::defaultPermissions('user'))))
                    @foreach(\App\Models\User::PERMISSIONS as $key=>$label)
                    <label class="permission"><input type="checkbox" name="permissions[]" value="{{ $key }}" @checked(in_array($key, $selected, true))><span>{{ $label }}</span></label>
                    @endforeach
                </fieldset>
                <button type="submit">{{ $editing ? 'Save changes' : 'Create user' }}</button>
                @if($editing)<a style="margin-left:12px" href="{{ route('admin.users.index') }}">Cancel</a>@endif
            </form>
        </section>
        @if($users)
        <section class="card"><h2>Users</h2><form class="search" method="get" action="{{ route('admin.users.index') }}"><input name="search" aria-label="Search users by name or email" placeholder="Search name or email" value="{{ $search }}"><button type="submit">Search</button>@if($search)<a href="{{ route('admin.users.index') }}">Clear</a>@endif</form>
            <div class="table-wrap"><table><thead><tr><th>User</th><th>Role</th><th>Status</th><th>Access</th><th></th></tr></thead><tbody>
                @forelse($users as $user)<tr><td>{{ $user->name }}<small>{{ $user->email }}</small></td><td>{{ ['admin'=>'Administrator','agent'=>'System Developer','user'=>'Client'][$user->role] }}</td><td><span class="badge {{ $user->is_active ? '' : 'inactive' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span></td><td>{{ $user->isAdmin() ? 'Full access' : count($user->effectivePermissions()).' permissions' }}</td><td><a href="{{ route('admin.users.edit', $user) }}">Edit<span style="position:absolute;width:1px;height:1px;overflow:hidden"> {{ $user->name }}</span></a></td></tr>@empty<tr><td colspan="5">No users found.</td></tr>@endforelse
            </tbody></table></div><div class="pagination">{{ $users->links() }}</div>
        </section>
        @endif
    </div>
</main>
<script>
const role = document.getElementById('role');
const boxes = [...document.querySelectorAll('input[name="permissions[]"]')];
function syncPermissions(reset) {
    boxes.forEach(box => {
        const staffOnly = !['tickets.view','tickets.create','tickets.comment'].includes(box.value);
        box.disabled = role.value === 'admin' || (role.value === 'user' && staffOnly);
        if (reset) box.checked = role.value !== 'user' || !staffOnly;
        if (role.value === 'admin') box.checked = true;
        if (role.value === 'user' && staffOnly) box.checked = false;
    });
}
role.addEventListener('change', () => syncPermissions(true));
syncPermissions(false);
boxes.forEach(box => box.addEventListener('change', () => {
    const viewing = boxes.find(b => b.value === 'tickets.view');
    const analytics = boxes.find(b => b.value === 'analytics.view');
    if (box === analytics && analytics.checked) viewing.checked = true;
    if (box === viewing && !viewing.checked) analytics.checked = false;
}));
</script>
</body>
</html>

