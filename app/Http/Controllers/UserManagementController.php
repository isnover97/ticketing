<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TicketWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $users = User::query()->when($search !== '', fn ($query) => $query->where(function ($q) use ($search) {
            $q->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%');
        }))->orderBy('name')->paginate(20)->withQueryString();

        return view('admin.users', ['users' => $users, 'editing' => null, 'search' => $search]);
    }

    public function edit(User $user)
    {
        return view('admin.users', ['users' => null, 'editing' => $user, 'search' => '']);
    }

    private function validated(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user?->id)],
            'role' => ['required', Rule::in(['admin', 'agent', 'user'])],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:12', 'max:128', 'confirmed'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', 'distinct', Rule::in(array_keys(User::PERMISSIONS))],
            'is_active' => ['required', 'boolean'],
            'ticket_scope' => ['sometimes', Rule::in(['all', 'assigned', 'departments'])],
            'department_ids' => ['nullable', 'array'],
            'department_ids.*' => ['integer', Rule::exists('departments', 'id')],
            'access_profile_id' => ['nullable', Rule::exists('helpdesk_records', 'id')->where('kind', 'roles')->where('is_active', true)],
            'organization_id' => ['nullable', Rule::exists('helpdesk_records', 'id')->where('kind', 'organizations')],
        ]);
        $data['email'] = strtolower(trim($data['email']));
        $data['permissions'] = array_values(array_intersect($data['permissions'] ?? [], User::defaultPermissions($data['role'])));
        if (in_array('analytics.view', $data['permissions'], true) && ! in_array('tickets.view', $data['permissions'], true)) {
            $data['permissions'][] = 'tickets.view';
        }
        if ($data['role'] === 'admin') {
            $data['permissions'] = null;
        }
        if (! empty($data['access_profile_id'])) {
            $data['permissions'] = null;
        }
        $data['ticket_scope'] = $data['ticket_scope'] ?? 'all';
        $data['department_ids'] = array_map('intval', $data['department_ids'] ?? []);
        if (empty($data['password'])) {
            unset($data['password']);
        }

        return $data;
    }

    public function store(Request $request)
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);
        $user = User::create($this->validated($request));
        app(TicketWorkflow::class)->event(null, $request->user(), 'user_created', 'Account created: '.$user->email);

        return redirect()->route('admin.users.index')->with('status', 'User account created successfully.');
    }

    public function update(Request $request, User $user)
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);
        $data = $this->validated($request, $user);
        if ($user->id === $request->user()->id && ($data['role'] !== 'admin' || ! $data['is_active'])) {
            throw ValidationException::withMessages(['role' => 'You cannot deactivate or remove administrator access from your own account.']);
        }
        DB::transaction(function () use ($user, $data) {
            $user->update($data);
            app(TicketWorkflow::class)->event(null, auth()->user(), 'user_updated', 'Account access updated: '.$user->email);
            // Revoke existing sessions after a password reset or deactivation.
            if (isset($data['password']) || ! $data['is_active']) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
                $user->forceFill(['remember_token' => null])->save();
            }
        });

        return redirect()->route('admin.users.index')->with('status', 'User account updated successfully.');
    }
}
