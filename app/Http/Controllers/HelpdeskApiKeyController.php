<?php

namespace App\Http\Controllers;

use App\Models\HelpdeskApiKey;
use App\Models\User;
use App\Services\TicketWorkflow;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HelpdeskApiKeyController extends Controller
{
    public function index()
    {
        return view('helpdesk.api-keys', ['keys' => HelpdeskApiKey::with('user')->latest()->paginate(20), 'agents' => User::whereIn('role', ['admin', 'agent'])->where('is_active', true)->orderBy('name')->get(['id', 'name'])]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:255', 'user_id' => ['required', Rule::exists('users', 'id')->whereIn('role', ['admin', 'agent'])->where('is_active', true)],
            'scopes' => 'required|array|min:1', 'scopes.*' => 'required|in:read,create', 'allowed_ip' => 'nullable|ip', 'expires_at' => 'nullable|date|after:now']);
        $token = bin2hex(random_bytes(32));
        $data['key_hash'] = hash('sha256', $token);
        $data['is_active'] = true;
        HelpdeskApiKey::create($data);
        app(TicketWorkflow::class)->event(null, $request->user(), 'api_key_created', 'API key created: '.$data['name']);

        return back()->with('api_token', $token)->with('status', 'API key created. Copy the token now; it will not be shown again.');
    }

    public function revoke(Request $request, HelpdeskApiKey $key)
    {
        $key->update(['is_active' => false]);
        app(TicketWorkflow::class)->event(null, $request->user(), 'api_key_revoked', 'API key revoked: '.$key->name);

        return back()->with('status','API key revoked.');
    }
}
