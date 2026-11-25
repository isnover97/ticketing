<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ModuleAccess
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        $user = $request->user();
        abort_unless($user && $user->is_active, 403, 'Your account is inactive.');
        abort_unless($permission === 'users.manage' ? $user->canManageSystem() : $user->canAccess($permission), 403, 'You do not have access to this module.');
        return $next($request);
    }
}
