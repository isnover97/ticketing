<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\HelpdeskCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    public static function workspaceUrl(User $user): string
    {
        if ($user->canAccess('tickets.view')) {
            return route('helpdesk.index');
        }
        if ($user->canAccess('tasks.manage')) {
            return route('helpdesk.tasks');
        }
        if ($user->canAccess('tickets.create')) {
            return route('helpdesk.create');
        }
        if ($user->canAccess('directory.view')) {
            return route('helpdesk.directory');
        }
        if ($user->canAccess('analytics.view')) {
            return route('helpdesk.reports');
        }
        foreach (HelpdeskCatalog::modules() as $kind => $module) {
            if ($user->canAccess($module[1])) {
                return route('helpdesk.manage', $kind);
            }
        }

        return route('knowledge.index');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $key = 'login:'.hash('sha256', strtolower($request->email).'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json(['error' => 'Too many attempts. Try again in '.RateLimiter::availableIn($key).' seconds.'], 429);
        }
        $user = User::where('email', strtolower(trim($request->email)))->first();

        if (! $user || ! Hash::check($request->password, $user->password) || ! $user->is_active) {
            RateLimiter::hit($key, 60);

            return response()->json(['error' => 'Invalid email or password.'], 401);
        }

        RateLimiter::clear($key);
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return response()->json([
            'csrf_token' => csrf_token(),
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'permissions' => $user->effectivePermissions(),
            'redirect_url' => self::workspaceUrl($user),
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Signed out successfully.', 'csrf_token' => csrf_token()]);
    }

    public function me(Request $request)
    {
        return response()->json(array_merge($request->user()->only(['id', 'name', 'email', 'role']), [
            'permissions' => $request->user()->effectivePermissions(),
            'redirect_url' => self::workspaceUrl($request->user()),
        ]));
    }
}
